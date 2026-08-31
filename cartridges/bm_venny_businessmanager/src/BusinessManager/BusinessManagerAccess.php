<?php

declare(strict_types=1);

namespace VennyIO\BusinessManager;

final class BusinessManagerAccess
{
    public const ENV_KEY = 'v_BUSINESS_MANAGER_PASSPHRASE';

    private const COOKIE_NAME = 'vbm_access';
    private const COOKIE_PATH = '/business-manager';
    private const TOKEN_VERSION = 'v1';

    public function configured(): bool
    {
        return $this->configurationError() === null;
    }

    public function configurationError(): ?string
    {
        $passphrase = $this->configuredPassphrase();

        if ($passphrase === '') {
            return self::ENV_KEY . ' is not configured.';
        }

        if (!$this->isValidFiveWordPassphrase($passphrase)) {
            return self::ENV_KEY . ' must contain exactly five whitespace-separated words, with no word longer than five characters.';
        }

        return null;
    }

    public function verify(string $candidate): bool
    {
        if (!$this->configured()) {
            return false;
        }

        $candidate = $this->normalizePassphrase($candidate);
        if (!$this->isValidFiveWordPassphrase($candidate)) {
            return false;
        }

        return hash_equals($this->configuredPassphrase(), $candidate);
    }

    public function isAuthorized(): bool
    {
        if (!$this->configured()) {
            return false;
        }

        $cookie = $_COOKIE[self::COOKIE_NAME] ?? '';
        if (!is_string($cookie) || $cookie === '') {
            return false;
        }

        $parts = explode('.', $cookie, 3);
        if (count($parts) !== 3 || $parts[0] !== self::TOKEN_VERSION) {
            return false;
        }

        [, $payload, $signature] = $parts;
        if ($payload === '' || $signature === '') {
            return false;
        }

        $expectedSignature = $this->sign($payload);
        return hash_equals($expectedSignature, $signature);
    }


    public function csrfToken(string $action): string
    {
        if (!$this->isAuthorized()) {
            return '';
        }

        $cookie = $_COOKIE[self::COOKIE_NAME] ?? '';
        if (!is_string($cookie) || $cookie === '') {
            return '';
        }

        $secret = hash('sha256', 'venny-business-manager-csrf|' . $this->configuredPassphrase(), true);
        $token = hash_hmac('sha256', $action . '|' . $cookie, $secret, true);
        return $this->base64UrlEncode($token);
    }

    public function verifyCsrfToken(string $action, string $candidate): bool
    {
        if ($candidate === '') {
            return false;
        }

        $expected = $this->csrfToken($action);
        return $expected !== '' && hash_equals($expected, $candidate);
    }

    public function grant(): void
    {
        if (!$this->configured()) {
            return;
        }

        $payload = $this->base64UrlEncode(random_bytes(24));
        $value = self::TOKEN_VERSION . '.' . $payload . '.' . $this->sign($payload);

        setcookie(self::COOKIE_NAME, $value, [
            'expires' => 0,
            'path' => self::COOKIE_PATH,
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        // Make the new grant visible during this request as well.
        $_COOKIE[self::COOKIE_NAME] = $value;
    }

    public function revoke(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => self::COOKIE_PATH,
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        unset($_COOKIE[self::COOKIE_NAME]);
    }

    public function normalizeSubmittedPassphrase(string $passphrase): string
    {
        return $this->normalizePassphrase($passphrase);
    }

    private function configuredPassphrase(): string
    {
        $value = getenv(self::ENV_KEY);
        return is_string($value) ? $this->normalizePassphrase($value) : '';
    }

    private function normalizePassphrase(string $passphrase): string
    {
        $parts = preg_split('/\s+/u', trim($passphrase)) ?: [];
        return implode(' ', array_values(array_filter($parts, static fn (string $part): bool => $part !== '')));
    }

    private function isValidFiveWordPassphrase(string $passphrase): bool
    {
        if ($passphrase === '') {
            return false;
        }

        $parts = explode(' ', $passphrase);
        if (count($parts) !== 5) {
            return false;
        }

        foreach ($parts as $part) {
            if ($part === '' || strlen($part) > 5) {
                return false;
            }
        }

        return true;
    }

    private function sign(string $payload): string
    {
        $secret = hash('sha256', 'venny-business-manager|' . $this->configuredPassphrase(), true);
        $signature = hash_hmac('sha256', self::TOKEN_VERSION . '.' . $payload, $secret, true);
        return $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function isHttps(): bool
    {
        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));

        return $forwardedProto === 'https' || $https === 'on' || $https === '1';
    }
}
