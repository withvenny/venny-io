<?php

declare(strict_types=1);

final class KeyFactory
{
    public static function create(string $environment = 'test'): array
    {
        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $prefix = $environment === 'production' ? 'venny_live_' : 'venny_test_';
        $plain = $prefix . $secret;
        return [
            'key_id' => self::id('key'),
            'key_name' => 'Setup Wizard API Key',
            'key_prefix' => substr($plain, 0, 20),
            'api_key' => $plain,
            'key_hash' => hash('sha256', $plain),
            'last_four' => substr($plain, -4),
        ];
    }

    public static function id(string $prefix): string
    {
        return $prefix . '_' . bin2hex(random_bytes(12));
    }
}
