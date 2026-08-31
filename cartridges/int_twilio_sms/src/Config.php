<?php

declare(strict_types=1);

namespace Venny\Cartridges\TwilioSms;

use Venny\Cartridges\TwilioSms\Exceptions\ConfigurationException;

final class Config
{
    public function __construct(
        private readonly string $accountSid,
        private readonly string $authToken,
        private readonly ?string $messagingServiceSid = null,
        private readonly ?string $fromNumber = null,
        private readonly ?string $statusCallbackUrl = null,
    ) {
        $this->validate();
    }

    public static function fromEnvironment(): self
    {
        return new self(
            accountSid: trim((string) getenv('v_TWILIO_ACCOUNT_SID')),
            authToken: trim((string) getenv('v_TWILIO_AUTH_TOKEN')),
            messagingServiceSid: self::nullableEnv('v_TWILIO_MESSAGING_SERVICE_SID'),
            fromNumber: self::nullableEnv('v_TWILIO_FROM_NUMBER'),
            statusCallbackUrl: self::nullableEnv('v_TWILIO_STATUS_CALLBACK_URL'),
        );
    }

    public function accountSid(): string { return $this->accountSid; }
    public function authToken(): string { return $this->authToken; }
    public function messagingServiceSid(): ?string { return $this->messagingServiceSid; }
    public function fromNumber(): ?string { return $this->fromNumber; }
    public function statusCallbackUrl(): ?string { return $this->statusCallbackUrl; }

    public function hasMessagingService(): bool
    {
        return $this->messagingServiceSid !== null;
    }

    private function validate(): void
    {
        if ($this->accountSid === '' || !str_starts_with($this->accountSid, 'AC')) {
            throw new ConfigurationException(
                'v_TWILIO_ACCOUNT_SID is required and must use the Twilio AC prefix.'
            );
        }

        if ($this->authToken === '') {
            throw new ConfigurationException('v_TWILIO_AUTH_TOKEN is required.');
        }

        if (
            $this->messagingServiceSid !== null &&
            !str_starts_with($this->messagingServiceSid, 'MG')
        ) {
            throw new ConfigurationException(
                'v_TWILIO_MESSAGING_SERVICE_SID must use the Twilio MG prefix.'
            );
        }

        if (
            $this->fromNumber !== null &&
            !preg_match('/^\+[1-9]\d{7,14}$/', $this->fromNumber)
        ) {
            throw new ConfigurationException(
                'v_TWILIO_FROM_NUMBER must be in E.164 format.'
            );
        }

        if (
            $this->statusCallbackUrl !== null &&
            !filter_var($this->statusCallbackUrl, FILTER_VALIDATE_URL)
        ) {
            throw new ConfigurationException(
                'v_TWILIO_STATUS_CALLBACK_URL must be a valid URL.'
            );
        }

        if ($this->messagingServiceSid === null && $this->fromNumber === null) {
            throw new ConfigurationException(
                'Configure v_TWILIO_MESSAGING_SERVICE_SID or v_TWILIO_FROM_NUMBER.'
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
