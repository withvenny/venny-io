<?php

declare(strict_types=1);

namespace Venny\Cartridges\StripePayments;

use Venny\Cartridges\StripePayments\Exceptions\ConfigurationException;

final class Config
{
    public function __construct(
        private readonly string $secretKey,
        private readonly ?string $publishableKey = null,
        private readonly ?string $webhookSecret = null,
        private readonly ?string $apiVersion = null,
    ) {
        $this->validate();
    }

    public static function fromEnvironment(): self
    {
        return new self(
            secretKey: trim((string) getenv('v_STRIPE_SECRET_KEY')),
            publishableKey: self::nullableEnv('v_STRIPE_PUBLISHABLE_KEY'),
            webhookSecret: self::nullableEnv('v_STRIPE_WEBHOOK_SECRET'),
            apiVersion: self::nullableEnv('v_STRIPE_API_VERSION'),
        );
    }

    public function secretKey(): string
    {
        return $this->secretKey;
    }

    public function publishableKey(): ?string
    {
        return $this->publishableKey;
    }

    public function webhookSecret(): ?string
    {
        return $this->webhookSecret;
    }

    public function apiVersion(): ?string
    {
        return $this->apiVersion;
    }

    public function hasWebhookSecret(): bool
    {
        return $this->webhookSecret !== null;
    }

    public function mode(): string
    {
        if (str_starts_with($this->secretKey, 'sk_test_')) {
            return 'test';
        }

        if (str_starts_with($this->secretKey, 'sk_live_')) {
            return 'live';
        }

        return 'unknown';
    }

    private function validate(): void
    {
        if ($this->secretKey === '') {
            throw new ConfigurationException('v_STRIPE_SECRET_KEY is required.');
        }

        if (
            !str_starts_with($this->secretKey, 'sk_test_') &&
            !str_starts_with($this->secretKey, 'sk_live_') &&
            !str_starts_with($this->secretKey, 'rk_test_') &&
            !str_starts_with($this->secretKey, 'rk_live_')
        ) {
            throw new ConfigurationException(
                'v_STRIPE_SECRET_KEY does not use a recognized Stripe secret or restricted-key prefix.'
            );
        }

        if (
            $this->webhookSecret !== null &&
            !str_starts_with($this->webhookSecret, 'whsec_')
        ) {
            throw new ConfigurationException(
                'v_STRIPE_WEBHOOK_SECRET must use the Stripe whsec_ prefix.'
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
