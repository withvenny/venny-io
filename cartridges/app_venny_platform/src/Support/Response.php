<?php

declare(strict_types=1);

namespace VennyIO\Support;

final class Response
{
    public static function json(int $httpStatus, bool $success, string $message, array $data = []): void
    {
        http_response_code($httpStatus);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        echo json_encode([
            'status' => (string) $httpStatus,
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
