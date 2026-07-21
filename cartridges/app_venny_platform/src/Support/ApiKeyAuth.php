<?php

declare(strict_types=1);

namespace VennyIO\Support;

use PDO;

final class ApiKeyAuth
{
    /**
     * Runtime auth standard:
     *   Authorization: Bearer {raw_api_key}
     *
     * Current model:
     *   keys.key_hash stores password_hash($rawKey, PASSWORD_DEFAULT)
     *   verification uses password_verify().
     *
     * Transitional support:
     *   Legacy sha256/sha512 hashes are accepted only when the Bearer token
     *   matches the stored key_prefix. When a legacy hash is accepted, it is
     *   upgraded in-place to password_hash().
     */
    public static function require(PDO $db): array
    {
        $provided = self::providedBearerToken();

        if ($provided === '') {
            Response::json(401, false, 'bearer token is missing', []);
            exit;
        }

        $keys = self::candidateKeys($db, $provided);

        foreach ($keys as $key) {
            $storedHash = (string) ($key['key_hash'] ?? '');

            if (self::matchesPasswordHash($provided, $storedHash)) {
                self::markUsed($db, (string) $key['key_id']);
                unset($key['key_hash']);
                return $key;
            }

            if (self::matchesLegacyHash($provided, $storedHash)) {
                self::upgradeLegacyHash($db, (string) $key['key_id'], $provided);
                self::markUsed($db, (string) $key['key_id']);
                unset($key['key_hash']);
                return $key;
            }
        }

        Response::json(401, false, 'bearer token is invalid', []);
        exit;
    }

    private static function providedBearerToken(): string
    {
        $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (is_string($authorization) && preg_match('/^Bearer\s+(.+)$/i', trim($authorization), $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * Use key_prefix to avoid scanning every active key and running
     * password_verify() repeatedly. key_prefix is VARCHAR(20), but older rows
     * may use shorter prefixes, so we test a small set of possible starts.
     */
    private static function candidateKeys(PDO $db, string $provided): array
    {
        $prefixes = self::prefixCandidates($provided);
        $placeholders = [];
        $params = [];

        foreach ($prefixes as $index => $prefix) {
            $name = ':prefix' . $index;
            $placeholders[] = $name;
            $params[$name] = $prefix;
        }

        $sql = "
            SELECT
                k.key_id,
                k.key_name,
                k.key_hash,
                k.key_prefix,
                k.key_ratelimit,
                k.key_windowsize,
                k.key_expires,
                k.created_by_user_id,
                k.created_for_app_id AS app_id,
                k.event_id,
                k.process_id,
                k.access,
                k.status,
                k.active,
                a.app_slug
            FROM keys k
            INNER JOIN apps a ON a.app_id = k.created_for_app_id
            WHERE k.key_prefix IN (" . implode(', ', $placeholders) . ")
              AND k.active = 1
              AND k.status = 'active'
              AND (k.key_expires IS NULL OR k.key_expires > now())
              AND a.active = 1
              AND a.status = 'active'
            ORDER BY k.time_started DESC
            LIMIT 10
        ";

        $stmt = $db->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    private static function prefixCandidates(string $provided): array
    {
        $provided = trim($provided);
        $length = strlen($provided);
        $candidateLengths = [20, 19, 18, 17, 16, 15, 14, 13, 12, 11, 10];
        $prefixes = [];

        foreach ($candidateLengths as $candidateLength) {
            if ($length >= $candidateLength) {
                $prefixes[] = substr($provided, 0, $candidateLength);
            }
        }

        return array_values(array_unique($prefixes));
    }

    private static function matchesPasswordHash(string $provided, string $storedHash): bool
    {
        if ($storedHash === '') {
            return false;
        }

        $info = password_get_info($storedHash);
        if (($info['algo'] ?? 0) === 0) {
            return false;
        }

        return password_verify($provided, $storedHash);
    }

    private static function matchesLegacyHash(string $provided, string $storedHash): bool
    {
        if ($storedHash === '') {
            return false;
        }

        return hash_equals($storedHash, hash('sha256', $provided))
            || hash_equals($storedHash, hash('sha512', $provided));
    }

    private static function upgradeLegacyHash(PDO $db, string $keyId, string $provided): void
    {
        $stmt = $db->prepare('
            UPDATE keys
            SET key_hash = :key_hash,
                key_prefix = :key_prefix,
                time_updated = now()
            WHERE key_id = :key_id
        ');

        $stmt->execute([
            ':key_hash' => password_hash($provided, PASSWORD_DEFAULT),
            ':key_prefix' => substr($provided, 0, 20),
            ':key_id' => $keyId,
        ]);
    }

    private static function markUsed(PDO $db, string $keyId): void
    {
        $stmt = $db->prepare('UPDATE keys SET key_lastused = now(), time_updated = now() WHERE key_id = :key_id');
        $stmt->execute([':key_id' => $keyId]);
    }
}
