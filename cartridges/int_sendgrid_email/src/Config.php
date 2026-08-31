<?php

declare(strict_types=1);

namespace Venny\Cartridges\SendGridEmail;

use Venny\Cartridges\SendGridEmail\Exceptions\ConfigurationException;

final class Config
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $fromEmail,
        private readonly ?string $fromName = null,
        private readonly ?string $replyToEmail = null,
        private readonly ?string $replyToName = null,
        private readonly ?string $webhookPublicKey = null,
    ) {
        $this->validate();
    }

    public static function fromEnvironment(): self
    {
        return new self(
            apiKey: trim((string) getenv('v_SENDGRID_API_KEY')),
            fromEmail: trim((string) getenv('v_SENDGRID_FROM_EMAIL')),
            fromName: self::nullableEnv('v_SENDGRID_FROM_NAME'),
            replyToEmail: self::nullableEnv('v_SENDGRID_REPLY_TO_EMAIL'),
            replyToName: self::nullableEnv('v_SENDGRID_REPLY_TO_NAME'),
            webhookPublicKey: self::nullableEnv('v_SENDGRID_WEBHOOK_PUBLIC_KEY'),
        );
    }

    public function apiKey(): string { return $this->apiKey; }
    public function fromEmail(): string { return $this->fromEmail; }
    public function fromName(): ?string { return $this->fromName; }
    public function replyToEmail(): ?string { return $this->replyToEmail; }
    public function replyToName(): ?string { return $this->replyToName; }
    public function webhookPublicKey(): ?string { return $this->webhookPublicKey; }

    public function hasWebhookPublicKey(): bool
    {
        return $this->webhookPublicKey !== null;
    }

    private function validate(): void
    {
        if ($this->apiKey === '') {
            throw new ConfigurationException('v_SENDGRID_API_KEY is required.');
        }

        if (!str_starts_with($this->apiKey, 'SG.')) {
            throw new ConfigurationException(
                'v_SENDGRID_API_KEY does not use the expected SendGrid SG. prefix.'
            );
        }

        if (!filter_var($this->fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new ConfigurationException(
                'v_SENDGRID_FROM_EMAIL must be a valid email address.'
            );
        }

        if (
            $this->replyToEmail !== null &&
            !filter_var($this->replyToEmail, FILTER_VALIDATE_EMAIL)
        ) {
            throw new ConfigurationException(
                'v_SENDGRID_REPLY_TO_EMAIL must be a valid email address.'
            );
        }
    }

    private static function nullableEnv(string $name): ?string
    {
        $value = getenv($name);

        if ($value === false || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }
}
