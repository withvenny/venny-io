<?php

declare(strict_types=1);

namespace VennyIO\Support;

/**
 * Hashing helpers for high-entropy server-generated tokens.
 *
 * User passwords should still use password_hash()/password_verify(). API keys,
 * refresh tokens, and reset token secrets are randomly generated and already
 * high entropy, so a deterministic cryptographic hash avoids expensive bcrypt
 * work on every request without making practical brute force easier.
 */
final class TokenHash
{
    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function verify(string $token, string $storedHash): bool
    {
        $storedHash = trim($storedHash);
        if ($storedHash === '') {
            return false;
        }

        if (self::isSha256($storedHash)) {
            return hash_equals($storedHash, hash('sha256', $token));
        }

        if (self::isSha512($storedHash)) {
            return hash_equals($storedHash, hash('sha512', $token));
        }

        $info = password_get_info($storedHash);
        if (($info['algo'] ?? 0) !== 0) {
            return password_verify($token, $storedHash);
        }

        return false;
    }

    public static function isFastHash(string $storedHash): bool
    {
        $storedHash = trim($storedHash);
        return self::isSha256($storedHash) || self::isSha512($storedHash);
    }

    public static function isPasswordHash(string $storedHash): bool
    {
        $info = password_get_info(trim($storedHash));
        return ($info['algo'] ?? 0) !== 0;
    }

    private static function isSha256(string $storedHash): bool
    {
        return preg_match('/^[a-f0-9]{64}$/i', trim($storedHash)) === 1;
    }

    private static function isSha512(string $storedHash): bool
    {
        return preg_match('/^[a-f0-9]{128}$/i', trim($storedHash)) === 1;
    }
}
