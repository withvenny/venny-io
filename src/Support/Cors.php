<?php

declare(strict_types=1);

namespace VennyIO\Support;

final class Cors
{
    /**
     * Apply CORS headers for browser-hosted Venny I/O reference apps.
     *
     * Configure with CORS_ALLOWED_ORIGINS as a comma-separated list.
     * Defaults are intentionally narrow enough for current WithVenny work,
     * while still supporting local Vite development.
     */
    public static function apply(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (!is_string($origin) || trim($origin) === '') {
            return;
        }

        $origin = trim($origin);
        $allowedOrigins = self::allowedOrigins();

        if (!in_array($origin, $allowedOrigins, true)) {
            return;
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: false');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, X-Venny-Session-Id, X-Setup-Token, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }

    public static function handlePreflight(): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'OPTIONS') {
            return;
        }

        self::apply();
        http_response_code(204);
        exit;
    }

    /** @return array<int, string> */
    private static function allowedOrigins(): array
    {
        $configured = getenv('CORS_ALLOWED_ORIGINS') ?: '';
        $raw = trim((string) $configured);

        $defaults = [
            'https://www.withvenny.app',
            'https://withvenny.app',
            'https://app-withvenny-www.herokuapp.com',
            'http://localhost:5173',
            'http://localhost:3000',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:3000',
        ];

        if ($raw === '') {
            return $defaults;
        }

        $origins = array_map('trim', explode(',', $raw));
        $configured = array_values(array_filter($origins, static fn (string $value): bool => $value !== ''));

        return array_values(array_unique(array_merge($defaults, $configured)));
    }
}
