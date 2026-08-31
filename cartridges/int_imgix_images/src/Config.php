<?php

declare(strict_types=1);

namespace Venny\Cartridges\ImgixImages;

use Venny\Cartridges\ImgixImages\Exceptions\ConfigurationException;

final class Config
{
    public function __construct(
        private readonly string $domain,
        private readonly ?string $secureUrlToken = null,
        private readonly bool $useHttps = true,
        private readonly bool $includeLibraryParam = true,
    ) {
        $this->validate();
    }

    public static function fromEnvironment(): self
    {
        $domain = trim((string) getenv('v_IMGIX_DOMAIN'));
        $token = getenv('v_IMGIX_SECURE_URL_TOKEN');

        return new self(
            domain: $domain,
            secureUrlToken: $token === false || trim($token) === '' ? null : trim($token),
            useHttps: self::envBool('v_IMGIX_USE_HTTPS', true),
            includeLibraryParam: self::envBool('v_IMGIX_INCLUDE_LIBRARY_PARAM', true),
        );
    }

    public function domain(): string
    {
        return $this->domain;
    }

    public function secureUrlToken(): ?string
    {
        return $this->secureUrlToken;
    }

    public function useHttps(): bool
    {
        return $this->useHttps;
    }

    public function includeLibraryParam(): bool
    {
        return $this->includeLibraryParam;
    }

    public function hasSecureUrlToken(): bool
    {
        return $this->secureUrlToken !== null;
    }

    private function validate(): void
    {
        if ($this->domain === '') {
            throw new ConfigurationException('v_IMGIX_DOMAIN is required.');
        }

        if (str_contains($this->domain, '://')) {
            throw new ConfigurationException('v_IMGIX_DOMAIN must not include a URL scheme.');
        }

        if (str_contains($this->domain, '/')) {
            throw new ConfigurationException('v_IMGIX_DOMAIN must not include a path or trailing slash.');
        }

        if (!filter_var('https://' . $this->domain, FILTER_VALIDATE_URL)) {
            throw new ConfigurationException('v_IMGIX_DOMAIN is not a valid domain.');
        }
    }

    private static function envBool(string $name, bool $default): bool
    {
        $value = getenv($name);

        if ($value === false || trim($value) === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($parsed === null) {
            throw new ConfigurationException(sprintf(
                '%s must be a boolean value such as true, false, 1, 0, yes, or no.',
                $name
            ));
        }

        return $parsed;
    }
}
