<?php

declare(strict_types=1);

namespace Venny\Cartridges\SendGridEmail;

use SendGrid\EventWebhook\EventWebhook;
use SendGrid\Mail\Mail;
use Throwable;
use Venny\Cartridges\SendGridEmail\Exceptions\AuthenticationException;
use Venny\Cartridges\SendGridEmail\Exceptions\ConfigurationException;
use Venny\Cartridges\SendGridEmail\Exceptions\ProviderException;
use Venny\Cartridges\SendGridEmail\Exceptions\RateLimitException;
use Venny\Cartridges\SendGridEmail\Exceptions\ValidationException;
use Venny\Cartridges\SendGridEmail\Exceptions\WebhookException;

final class Provider
{
    private readonly Client $client;

    public function __construct(
        private readonly Config $config,
        ?Client $client = null
    ) {
        $this->client = $client ?? new Client($config);
    }

    public function send(array $message): ProviderResult
    {
        $mail = $this->buildMail($message);

        try {
            $response = $this->client->sendGrid()->send($mail);
            $status = $response->statusCode();
            $headers = $response->headers();
            $body = $response->body();

            $messageId = $this->extractMessageId($headers);

            if ($status >= 200 && $status < 300) {
                return ProviderResult::ok(
                    'send',
                    $messageId,
                    [
                        'status_code' => $status,
                    ],
                    [
                        'headers' => $headers,
                        'body' => $body,
                    ]
                );
            }

            $context = [
                'status_code' => $status,
                'headers' => $headers,
                'body' => $body,
            ];

            if ($status === 401 || $status === 403) {
                throw new AuthenticationException(
                    'SendGrid authentication or authorization failed.',
                    $status,
                    null,
                    $context
                );
            }

            if ($status === 429) {
                throw new RateLimitException(
                    'SendGrid rate limit exceeded.',
                    $status,
                    null,
                    $context
                );
            }

            if ($status >= 400 && $status < 500) {
                throw new ValidationException(
                    'SendGrid rejected the email request.',
                    $status,
                    null,
                    $context
                );
            }

            throw new ProviderException(
                'SendGrid email request failed.',
                $status,
                null,
                $context
            );
        } catch (ProviderException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ProviderException(
                'Unexpected SendGrid provider failure.',
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function verifyEventWebhook(
        string $payload,
        string $signature,
        string $timestamp
    ): ProviderResult {
        if (!$this->config->hasWebhookPublicKey()) {
            throw new ConfigurationException(
                'v_SENDGRID_WEBHOOK_PUBLIC_KEY is required for Event Webhook verification.'
            );
        }

        if ($payload === '' || trim($signature) === '' || trim($timestamp) === '') {
            throw new WebhookException(
                'SendGrid webhook payload, signature, and timestamp are required.'
            );
        }

        try {
            $eventWebhook = new EventWebhook();
            $publicKey = $eventWebhook->convertPublicKeyToECDSA(
                $this->config->webhookPublicKey()
            );

            $verified = $eventWebhook->verifySignature(
                $publicKey,
                $payload,
                $signature,
                $timestamp
            );

            if (!$verified) {
                throw new WebhookException(
                    'SendGrid Event Webhook signature verification failed.'
                );
            }

            $events = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

            return ProviderResult::ok(
                'verify_event_webhook',
                null,
                [
                    'verified' => true,
                    'events' => $events,
                ],
                [
                    'timestamp' => $timestamp,
                ]
            );
        } catch (WebhookException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new WebhookException(
                'Unable to verify or parse SendGrid Event Webhook payload.',
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function healthCheck(): ProviderResult
    {
        return ProviderResult::ok(
            'health_check',
            null,
            [
                'configured' => true,
                'default_from_email' => $this->config->fromEmail(),
                'default_from_name' => $this->config->fromName(),
                'reply_to_configured' => $this->config->replyToEmail() !== null,
                'event_webhook_verification_configured' => $this->config->hasWebhookPublicKey(),
                'client_constructed' => true,
            ]
        );
    }

    private function buildMail(array $message): Mail
    {
        $mail = new Mail();

        $from = $message['from'] ?? [
            'email' => $this->config->fromEmail(),
            'name' => $this->config->fromName(),
        ];

        $fromEmail = $from['email'] ?? null;

        if (!is_string($fromEmail) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('A valid from email address is required.');
        }

        $mail->setFrom($fromEmail, $from['name'] ?? null);

        $recipients = $message['to'] ?? [];

        if (!is_array($recipients) || $recipients === []) {
            throw new ValidationException('At least one recipient is required.');
        }

        foreach ($recipients as $recipient) {
            $this->addRecipient($mail, 'to', $recipient);
        }

        foreach (($message['cc'] ?? []) as $recipient) {
            $this->addRecipient($mail, 'cc', $recipient);
        }

        foreach (($message['bcc'] ?? []) as $recipient) {
            $this->addRecipient($mail, 'bcc', $recipient);
        }

        $replyTo = $message['reply_to'] ?? null;

        if ($replyTo === null && $this->config->replyToEmail() !== null) {
            $replyTo = [
                'email' => $this->config->replyToEmail(),
                'name' => $this->config->replyToName(),
            ];
        }

        if (is_array($replyTo) && isset($replyTo['email'])) {
            if (!filter_var((string) $replyTo['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException('Reply-to email address is invalid.');
            }

            $mail->setReplyTo(
                (string) $replyTo['email'],
                $replyTo['name'] ?? null
            );
        }

        if (isset($message['subject'])) {
            $mail->setSubject((string) $message['subject']);
        }

        $templateId = isset($message['template_id'])
            ? trim((string) $message['template_id'])
            : '';

        if ($templateId !== '') {
            $mail->setTemplateId($templateId);

            if (isset($message['dynamic_template_data']) && is_array($message['dynamic_template_data'])) {
                $mail->addDynamicTemplateDatas($message['dynamic_template_data']);
            }
        } else {
            if (!isset($message['text']) && !isset($message['html'])) {
                throw new ValidationException(
                    'Email text, HTML, or a transactional template ID is required.'
                );
            }

            if (isset($message['text'])) {
                $mail->addContent('text/plain', (string) $message['text']);
            }

            if (isset($message['html'])) {
                $mail->addContent('text/html', (string) $message['html']);
            }
        }

        foreach (($message['attachments'] ?? []) as $attachment) {
            if (!is_array($attachment) || !isset($attachment['content'], $attachment['filename'])) {
                throw new ValidationException(
                    'Each attachment requires content and filename.'
                );
            }

            $mail->addAttachment(
                (string) $attachment['content'],
                $attachment['type'] ?? null,
                (string) $attachment['filename'],
                $attachment['disposition'] ?? null,
                $attachment['content_id'] ?? null
            );
        }

        foreach (($message['headers'] ?? []) as $name => $value) {
            $mail->addHeader((string) $name, (string) $value);
        }

        foreach (($message['custom_args'] ?? []) as $name => $value) {
            $mail->addCustomArg((string) $name, (string) $value);
        }

        foreach (($message['categories'] ?? []) as $category) {
            $mail->addCategory((string) $category);
        }

        if (isset($message['send_at'])) {
            $sendAt = (int) $message['send_at'];

            if ($sendAt <= time()) {
                throw new ValidationException('send_at must be a future Unix timestamp.');
            }

            $mail->setSendAt($sendAt);
        }

        $this->applyTrackingSettings($mail, $message['tracking_settings'] ?? []);

        return $mail;
    }

    private function addRecipient(Mail $mail, string $type, mixed $recipient): void
    {
        if (!is_array($recipient)) {
            throw new ValidationException('Recipient must be an array.');
        }

        $email = $recipient['email'] ?? null;
        $name = $recipient['name'] ?? null;

        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Recipient email address is invalid.');
        }

        match ($type) {
            'to' => $mail->addTo($email, $name),
            'cc' => $mail->addCc($email, $name),
            'bcc' => $mail->addBcc($email, $name),
            default => throw new ValidationException('Unsupported recipient type.'),
        };
    }

    private function applyTrackingSettings(Mail $mail, array $settings): void
    {
        if (isset($settings['click'])) {
            $enabled = (bool) $settings['click'];
            $mail->setClickTracking($enabled, $enabled);
        }

        if (isset($settings['open'])) {
            $mail->setOpenTracking((bool) $settings['open']);
        }

        if (isset($settings['subscription'])) {
            $subscription = $settings['subscription'];

            if (is_bool($subscription)) {
                $mail->setSubscriptionTracking($subscription);
            } elseif (is_array($subscription)) {
                $mail->setSubscriptionTracking(
                    (bool) ($subscription['enable'] ?? true),
                    $subscription['text'] ?? null,
                    $subscription['html'] ?? null
                );
            }
        }
    }

    private function extractMessageId(array $headers): ?string
    {
        foreach ($headers as $header) {
            if (!is_string($header)) {
                continue;
            }

            if (stripos($header, 'X-Message-Id:') === 0) {
                return trim(substr($header, strlen('X-Message-Id:')));
            }
        }

        return null;
    }
}
