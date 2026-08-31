<?php

declare(strict_types=1);

namespace Venny\Cartridges\GoogleLocations;

use Venny\Cartridges\GoogleLocations\Exceptions\ConfigurationException;

final class Config
{
    public const ENDPOINT = 'https://maps.googleapis.com/maps/api/geocode/json';

    public function __construct(
        private readonly string $serverKey,
        private readonly ?string $language = null,
        private readonly ?string $region = null,
    ) {
        $this->validate();
    }

    public static function fromEnvironment(): self
    {
        return new self(
            serverKey: trim((string) getenv('v_GOOGLE_MAPS_SERVER_KEY')),
            language: self::nullableEnv('v_GOOGLE_GEOCODING_LANGUAGE'),
            region: self::nullableEnv('v_GOOGLE_GEOCODING_REGION'),
        );
    }

    public function serverKey(): string { return $this->serverKey; }
    public function language(): ?string { return $this->language; }
    public function region(): ?string { return $this->region; }

    private function validate(): void
    {
        if ($this->serverKey === '') {
            throw new ConfigurationException('v_GOOGLE_MAPS_SERVER_KEY is required.');
        }

        if (!str_starts_with($this->serverKey, 'AIza')) {
            throw new ConfigurationException(
                'v_GOOGLE_MAPS_SERVER_KEY does not use the expected Google API key prefix.'
            );
        }

        if ($this->language !== null && !preg_match('/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})?$/', $this->language)) {
            throw new ConfigurationException(
                'v_GOOGLE_GEOCODING_LANGUAGE is not a valid language code format.'
            );
        }

        if ($this->region !== null && !preg_match('/^[A-Za-z]{2}$/', $this->region)) {
            throw new ConfigurationException(
                'v_GOOGLE_GEOCODING_REGION must be a two-character region code.'
            );
        }

        if (!extension_loaded('curl')) {
            throw new ConfigurationException('PHP cURL extension is required.');
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
