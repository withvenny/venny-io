<?php

declare(strict_types=1);

namespace VennyIO\Support;

final class Ids
{
    private const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    private const BASE = 62;
    private const TIME_CHARS = 8;
    private const DEFAULT_RANDOM_CHARS = 16;

    /**
     * Keep this stable after launch. Changing it shifts timestamp recovery.
     */
    private const EPOCH_MS = 1704067200000; // 2024-01-01T00:00:00.000Z

    private const REGISTRY = [
        'app' => ['prefix' => 'app', 'size' => self::DEFAULT_RANDOM_CHARS],
        'key' => ['prefix' => 'key', 'size' => self::DEFAULT_RANDOM_CHARS],
        'session' => ['prefix' => 'session', 'size' => self::DEFAULT_RANDOM_CHARS],
        'window' => ['prefix' => 'window', 'size' => self::DEFAULT_RANDOM_CHARS],
        'person' => ['prefix' => 'person', 'size' => self::DEFAULT_RANDOM_CHARS],
        'user' => ['prefix' => 'user', 'size' => self::DEFAULT_RANDOM_CHARS],
        'profile' => ['prefix' => 'profile', 'size' => self::DEFAULT_RANDOM_CHARS],
        'asset' => ['prefix' => 'asset', 'size' => self::DEFAULT_RANDOM_CHARS],
        'content' => ['prefix' => 'content', 'size' => self::DEFAULT_RANDOM_CHARS],
        'contact' => ['prefix' => 'contact', 'size' => self::DEFAULT_RANDOM_CHARS],
        'company' => ['prefix' => 'company', 'size' => self::DEFAULT_RANDOM_CHARS],
        'deal' => ['prefix' => 'deal', 'size' => self::DEFAULT_RANDOM_CHARS],
        'pipeline' => ['prefix' => 'pipeline', 'size' => self::DEFAULT_RANDOM_CHARS],
        'stage' => ['prefix' => 'stage', 'size' => self::DEFAULT_RANDOM_CHARS],
        'activity' => ['prefix' => 'activity', 'size' => self::DEFAULT_RANDOM_CHARS],
        'task' => ['prefix' => 'task', 'size' => self::DEFAULT_RANDOM_CHARS],
        'note' => ['prefix' => 'note', 'size' => self::DEFAULT_RANDOM_CHARS],
        'tag' => ['prefix' => 'tag', 'size' => self::DEFAULT_RANDOM_CHARS],
        'communication' => ['prefix' => 'communication', 'size' => self::DEFAULT_RANDOM_CHARS],
        'delivery' => ['prefix' => 'delivery', 'size' => self::DEFAULT_RANDOM_CHARS],
        'thread' => ['prefix' => 'thread', 'size' => self::DEFAULT_RANDOM_CHARS],
        'message' => ['prefix' => 'message', 'size' => self::DEFAULT_RANDOM_CHARS],
        'post' => ['prefix' => 'post', 'size' => self::DEFAULT_RANDOM_CHARS],
        'followship' => ['prefix' => 'followship', 'size' => self::DEFAULT_RANDOM_CHARS],
        'group' => ['prefix' => 'group', 'size' => self::DEFAULT_RANDOM_CHARS],
        'acknowledgement' => ['prefix' => 'acknowledgement', 'size' => self::DEFAULT_RANDOM_CHARS],
        'comment' => ['prefix' => 'comment', 'size' => self::DEFAULT_RANDOM_CHARS],
        'catalog' => ['prefix' => 'catalog', 'size' => self::DEFAULT_RANDOM_CHARS],
        'category' => ['prefix' => 'category', 'size' => self::DEFAULT_RANDOM_CHARS],
        'product' => ['prefix' => 'product', 'size' => self::DEFAULT_RANDOM_CHARS],
        'item' => ['prefix' => 'item', 'size' => self::DEFAULT_RANDOM_CHARS],
        'transaction' => ['prefix' => 'transaction', 'size' => self::DEFAULT_RANDOM_CHARS],
        'order' => ['prefix' => 'order', 'size' => self::DEFAULT_RANDOM_CHARS],
        'coupon' => ['prefix' => 'coupon', 'size' => self::DEFAULT_RANDOM_CHARS],
        'customer' => ['prefix' => 'customer', 'size' => self::DEFAULT_RANDOM_CHARS],
        'installation' => ['prefix' => 'installation', 'size' => self::DEFAULT_RANDOM_CHARS],
        'step' => ['prefix' => 'step', 'size' => self::DEFAULT_RANDOM_CHARS],
    ];

    public static function generate(string $entity): string
    {
        if (!isset(self::REGISTRY[$entity])) {
            throw new \InvalidArgumentException("Unknown ID entity: {$entity}");
        }

        $prefix = self::REGISTRY[$entity]['prefix'];
        $randomSize = self::REGISTRY[$entity]['size'];

        return $prefix . '_' . self::timePart() . self::randomPart($randomSize);
    }

    public static function is(string $entity, string $id): bool
    {
        if (!isset(self::REGISTRY[$entity])) {
            return false;
        }

        $prefix = self::REGISTRY[$entity]['prefix'];
        $size = self::TIME_CHARS + self::REGISTRY[$entity]['size'];

        return (bool) preg_match('/^' . preg_quote($prefix, '/') . '_[0-9A-Za-z]{' . $size . '}$/', $id);
    }

    public static function isValid(string $id): bool
    {
        foreach (array_keys(self::REGISTRY) as $entity) {
            if (self::is($entity, $id)) {
                return true;
            }
        }

        return false;
    }

    public static function prefixOf(string $id): ?string
    {
        $parts = explode('_', $id, 2);
        return count($parts) === 2 ? $parts[0] : null;
    }

    public static function entityOf(string $id): ?string
    {
        $prefix = self::prefixOf($id);

        foreach (self::REGISTRY as $entity => $config) {
            if ($config['prefix'] === $prefix && self::is($entity, $id)) {
                return $entity;
            }
        }

        return null;
    }

    public static function timestampOf(string $id): ?\DateTimeImmutable
    {
        if (!self::isValid($id)) {
            return null;
        }

        $body = explode('_', $id, 2)[1];
        $timePart = substr($body, 0, self::TIME_CHARS);
        $ms = self::decodeBase62($timePart) + self::EPOCH_MS;

        return (new \DateTimeImmutable('@' . intdiv($ms, 1000)))
            ->setTimezone(new \DateTimeZone('UTC'));
    }

    private static function timePart(): string
    {
        $nowMs = (int) floor(microtime(true) * 1000);
        $delta = max(0, $nowMs - self::EPOCH_MS);

        return self::encodeBase62($delta, self::TIME_CHARS);
    }

    private static function randomPart(int $length): string
    {
        $output = '';
        $alphabetLength = strlen(self::ALPHABET);

        while (strlen($output) < $length) {
            $byte = ord(random_bytes(1));
            $limit = intdiv(256, $alphabetLength) * $alphabetLength;

            if ($byte >= $limit) {
                continue;
            }

            $output .= self::ALPHABET[$byte % $alphabetLength];
        }

        return $output;
    }

    private static function encodeBase62(int $value, int $padToLength): string
    {
        if ($value === 0) {
            return str_pad('0', $padToLength, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        while ($value > 0) {
            $encoded = self::ALPHABET[$value % self::BASE] . $encoded;
            $value = intdiv($value, self::BASE);
        }

        return str_pad($encoded, $padToLength, '0', STR_PAD_LEFT);
    }

    private static function decodeBase62(string $value): int
    {
        $decoded = 0;

        foreach (str_split($value) as $char) {
            $position = strpos(self::ALPHABET, $char);

            if ($position === false) {
                throw new \InvalidArgumentException('Invalid base62 value.');
            }

            $decoded = ($decoded * self::BASE) + $position;
        }

        return $decoded;
    }
}
