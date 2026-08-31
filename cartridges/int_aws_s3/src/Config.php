<?php

declare(strict_types=1);

namespace Venny\Cartridges\AwsS3;

use Venny\Cartridges\AwsS3\Exceptions\ConfigurationException;

final class Config
{
    public function __construct(
        private readonly string $region,
        private readonly string $bucket,
        private readonly ?string $accessKeyId = null,
        private readonly ?string $secretAccessKey = null,
        private readonly ?string $sessionToken = null,
        private readonly string $prefix = '',
        private readonly ?string $endpoint = null,
        private readonly bool $pathStyle = false,
    ) {
        $this->validate();
    }

    public static function fromEnvironment(): self
    {
        return new self(
            region: trim((string) getenv('v_AWS_REGION')),
            bucket: trim((string) getenv('v_AWS_S3_BUCKET')),
            accessKeyId: self::nullableEnv('v_AWS_ACCESS_KEY_ID'),
            secretAccessKey: self::nullableEnv('v_AWS_SECRET_ACCESS_KEY'),
            sessionToken: self::nullableEnv('v_AWS_SESSION_TOKEN'),
            prefix: self::normalizePrefix(self::nullableEnv('v_AWS_S3_PREFIX') ?? ''),
            endpoint: self::nullableEnv('v_AWS_S3_ENDPOINT'),
            pathStyle: self::envBool('v_AWS_S3_PATH_STYLE', false),
        );
    }

    public function region(): string { return $this->region; }
    public function bucket(): string { return $this->bucket; }
    public function accessKeyId(): ?string { return $this->accessKeyId; }
    public function secretAccessKey(): ?string { return $this->secretAccessKey; }
    public function sessionToken(): ?string { return $this->sessionToken; }
    public function prefix(): string { return $this->prefix; }
    public function endpoint(): ?string { return $this->endpoint; }
    public function pathStyle(): bool { return $this->pathStyle; }

    public function hasStaticCredentials(): bool
    {
        return $this->accessKeyId !== null && $this->secretAccessKey !== null;
    }

    public function key(string $logicalKey): string
    {
        $logicalKey = ltrim(trim($logicalKey), '/');

        if ($logicalKey === '') {
            throw new ConfigurationException('S3 object key is required.');
        }

        return $this->prefix . $logicalKey;
    }

    private function validate(): void
    {
        if ($this->region === '') {
            throw new ConfigurationException('v_AWS_REGION is required.');
        }

        if ($this->bucket === '') {
            throw new ConfigurationException('v_AWS_S3_BUCKET is required.');
        }

        $hasAccess = $this->accessKeyId !== null;
        $hasSecret = $this->secretAccessKey !== null;

        if ($hasAccess xor $hasSecret) {
            throw new ConfigurationException(
                'v_AWS_ACCESS_KEY_ID and v_AWS_SECRET_ACCESS_KEY must be provided together.'
            );
        }

        if ($this->sessionToken !== null && !$this->hasStaticCredentials()) {
            throw new ConfigurationException(
                'v_AWS_SESSION_TOKEN requires explicit access and secret keys in this configuration.'
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

    private static function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix, " \t\n\r\0\x0B/");
        return $prefix === '' ? '' : $prefix . '/';
    }

    private static function envBool(string $name, bool $default): bool
    {
        $value = getenv($name);
        if ($value === false || trim((string) $value) === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($parsed === null) {
            throw new ConfigurationException("$name must be a boolean value.");
        }

        return $parsed;
    }
}
