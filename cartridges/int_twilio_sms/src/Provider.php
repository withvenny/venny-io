<?php

declare(strict_types=1);

namespace Venny\Cartridges\TwilioSms;

use Throwable;
use Twilio\Exceptions\RestException;
use Twilio\Security\RequestValidator;
use Venny\Cartridges\TwilioSms\Exceptions\AuthenticationException;
use Venny\Cartridges\TwilioSms\Exceptions\ProviderException;
use Venny\Cartridges\TwilioSms\Exceptions\RateLimitException;
use Venny\Cartridges\TwilioSms\Exceptions\ValidationException;
use Venny\Cartridges\TwilioSms\Exceptions\WebhookException;

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
        $to = $this->validatePhone((string) ($message['to'] ?? ''), 'to');

        $params = [];

        if (isset($message['body'])) {
            $params['body'] = (string) $message['body'];
        }

        $mediaUrls = $message['media_urls'] ?? [];
        if ($mediaUrls !== []) {
            if (!is_array($mediaUrls)) {
                throw new ValidationException('media_urls must be an array.');
            }

            foreach ($mediaUrls as $url) {
                if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                    throw new ValidationException('Every media URL must be a valid URL.');
                }
            }

            $params['mediaUrl'] = array_values($mediaUrls);
        }

        if (!isset($params['body']) && !isset($params['mediaUrl'])) {
            throw new ValidationException(
                'Twilio message requires a body or at least one media URL.'
            );
        }

        $explicitMessagingService = $message['messaging_service_sid'] ?? null;
        $explicitFrom = $message['from'] ?? null;

        if ($explicitMessagingService !== null && $explicitFrom !== null) {
            throw new ValidationException(
                'Provide either messaging_service_sid or from, not both.'
            );
        }

        if ($explicitMessagingService !== null) {
            $sid = trim((string) $explicitMessagingService);
            if (!str_starts_with($sid, 'MG')) {
                throw new ValidationException('Messaging Service SID must use MG prefix.');
            }
            $params['messagingServiceSid'] = $sid;
        } elseif ($explicitFrom !== null) {
            $params['from'] = $this->validatePhone((string) $explicitFrom, 'from');
        } elseif ($this->config->hasMessagingService()) {
            $params['messagingServiceSid'] = $this->config->messagingServiceSid();
        } else {
            $params['from'] = $this->config->fromNumber();
        }

        $callback = $message['status_callback'] ?? $this->config->statusCallbackUrl();
        if ($callback !== null) {
            if (!filter_var((string) $callback, FILTER_VALIDATE_URL)) {
                throw new ValidationException('status_callback must be a valid URL.');
            }
            $params['statusCallback'] = (string) $callback;
        }

        if (isset($message['validity_period'])) {
            $params['validityPeriod'] = (int) $message['validity_period'];
        }

        if (isset($message['max_price'])) {
            $params['maxPrice'] = (string) $message['max_price'];
        }

        if (isset($message['provide_feedback'])) {
            $params['provideFeedback'] = (bool) $message['provide_feedback'];
        }

        if (isset($message['send_at'])) {
            $sendAt = $message['send_at'];
            if ($sendAt instanceof \DateTimeInterface) {
                $params['sendAt'] = $sendAt;
            } else {
                try {
                    $params['sendAt'] = new \DateTimeImmutable((string) $sendAt);
                } catch (Throwable $e) {
                    throw new ValidationException('send_at must be a valid date/time.', 0, $e);
                }
            }

            $params['scheduleType'] = (string) ($message['schedule_type'] ?? 'fixed');
        }

        try {
            $resource = $this->client->twilio()->messages->create($to, $params);

            return ProviderResult::ok(
                'send',
                $resource->sid ?? null,
                [
                    'sid' => $resource->sid ?? null,
                    'status' => $resource->status ?? null,
                    'to' => $resource->to ?? $to,
                    'from' => $resource->from ?? null,
                    'messaging_service_sid' => $resource->messagingServiceSid ?? null,
                    'date_created' => $resource->dateCreated?->format(DATE_ATOM),
                    'date_sent' => $resource->dateSent?->format(DATE_ATOM),
                    'error_code' => $resource->errorCode ?? null,
                    'error_message' => $resource->errorMessage ?? null,
                    'num_segments' => $resource->numSegments ?? null,
                    'price' => $resource->price ?? null,
                    'price_unit' => $resource->priceUnit ?? null,
                ]
            );
        } catch (RestException $e) {
            throw $this->mapRestException($e);
        } catch (ProviderException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ProviderException(
                'Unexpected Twilio SMS provider failure.',
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function retrieveMessage(string $messageSid): ProviderResult
    {
        $messageSid = $this->validateMessageSid($messageSid);

        try {
            $resource = $this->client->twilio()
                ->messages($messageSid)
                ->fetch();

            return ProviderResult::ok(
                'retrieve_message',
                $resource->sid ?? $messageSid,
                [
                    'sid' => $resource->sid ?? $messageSid,
                    'status' => $resource->status ?? null,
                    'to' => $resource->to ?? null,
                    'from' => $resource->from ?? null,
                    'messaging_service_sid' => $resource->messagingServiceSid ?? null,
                    'date_created' => $resource->dateCreated?->format(DATE_ATOM),
                    'date_sent' => $resource->dateSent?->format(DATE_ATOM),
                    'date_updated' => $resource->dateUpdated?->format(DATE_ATOM),
                    'error_code' => $resource->errorCode ?? null,
                    'error_message' => $resource->errorMessage ?? null,
                ]
            );
        } catch (RestException $e) {
            throw $this->mapRestException($e);
        }
    }

    public function cancelScheduledMessage(string $messageSid): ProviderResult
    {
        $messageSid = $this->validateMessageSid($messageSid);

        try {
            $resource = $this->client->twilio()
                ->messages($messageSid)
                ->update(['status' => 'canceled']);

            return ProviderResult::ok(
                'cancel_scheduled_message',
                $resource->sid ?? $messageSid,
                [
                    'sid' => $resource->sid ?? $messageSid,
                    'status' => $resource->status ?? null,
                ]
            );
        } catch (RestException $e) {
            throw $this->mapRestException($e);
        }
    }

    public function validateWebhook(
        string $url,
        array $params,
        string $signature
    ): ProviderResult {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new WebhookException('Webhook URL must be valid.');
        }

        if (trim($signature) === '') {
            throw new WebhookException('X-Twilio-Signature is required.');
        }

        try {
            $validator = new RequestValidator($this->config->authToken());
            $valid = $validator->validate($signature, $url, $params);

            if (!$valid) {
                throw new WebhookException(
                    'Twilio webhook signature validation failed.'
                );
            }

            return ProviderResult::ok(
                'validate_webhook',
                isset($params['MessageSid']) ? (string) $params['MessageSid'] : null,
                [
                    'valid' => true,
                    'params' => $params,
                ]
            );
        } catch (WebhookException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new WebhookException(
                'Unable to validate Twilio webhook.',
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function healthCheck(): ProviderResult
    {
        try {
            $account = $this->client->twilio()
                ->api->v2010
                ->accounts($this->config->accountSid())
                ->fetch();

            return ProviderResult::ok(
                'health_check',
                $account->sid ?? $this->config->accountSid(),
                [
                    'configured' => true,
                    'account_sid' => $account->sid ?? $this->config->accountSid(),
                    'account_status' => $account->status ?? null,
                    'messaging_service_configured' => $this->config->hasMessagingService(),
                    'from_number_configured' => $this->config->fromNumber() !== null,
                    'status_callback_configured' => $this->config->statusCallbackUrl() !== null,
                ]
            );
        } catch (RestException $e) {
            throw $this->mapRestException($e);
        }
    }

    private function validatePhone(string $value, string $field): string
    {
        $value = trim($value);

        if (!preg_match('/^\+[1-9]\d{7,14}$/', $value)) {
            throw new ValidationException(
                sprintf('%s must be a valid E.164 phone number.', $field)
            );
        }

        return $value;
    }

    private function validateMessageSid(string $sid): string
    {
        $sid = trim($sid);

        if ($sid === '' || !str_starts_with($sid, 'SM')) {
            throw new ValidationException(
                'Twilio Message SID is required and must use the SM prefix.'
            );
        }

        return $sid;
    }

    private function mapRestException(RestException $e): ProviderException
    {
        $context = [
            'status_code' => $e->getStatusCode(),
            'twilio_code' => $e->getCode(),
        ];

        $status = $e->getStatusCode();

        if ($status === 401 || $status === 403) {
            return new AuthenticationException(
                'Twilio authentication or authorization failed.',
                (int) $e->getCode(),
                $e,
                $context
            );
        }

        if ($status === 429) {
            return new RateLimitException(
                'Twilio rate limit exceeded.',
                (int) $e->getCode(),
                $e,
                $context
            );
        }

        if ($status >= 400 && $status < 500) {
            return new ValidationException(
                'Twilio rejected the messaging request.',
                (int) $e->getCode(),
                $e,
                $context
            );
        }

        return new ProviderException(
            'Twilio messaging request failed.',
            (int) $e->getCode(),
            $e,
            $context
        );
    }
}
