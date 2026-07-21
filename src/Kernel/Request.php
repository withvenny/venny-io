<?php

declare(strict_types=1);

namespace VennyIO\Kernel;

use VennyIO\Support\Response;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly array $server,
        private readonly string $rawBody
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = preg_replace('#/+#', '/', $path) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        return new self($method, $path, $_GET, $_POST, $_SERVER, file_get_contents('php://input') ?: '');
    }

    public function effectiveMethod(): string
    {
        if ($this->method !== 'POST') {
            return $this->method;
        }

        $input = $this->input();
        $override = strtoupper((string) ($input['_method'] ?? ''));

        return in_array($override, ['PATCH', 'DELETE'], true) ? $override : $this->method;
    }

    public function input(): array
    {
        $body = [];

        if ($this->post !== []) {
            $body = $this->post;
        } else {
            $contentType = strtolower((string) (
                $this->server['CONTENT_TYPE']
                ?? $this->server['HTTP_CONTENT_TYPE']
                ?? $this->server['REDIRECT_HTTP_CONTENT_TYPE']
                ?? ''
            ));

            $trimmed = trim($this->rawBody);

            if ($trimmed !== '' && (str_contains($contentType, 'application/json') || str_starts_with($trimmed, '{') || str_starts_with($trimmed, '['))) {
                $decoded = json_decode($trimmed, true);
                $body = is_array($decoded) ? $decoded : [];
            } elseif ($trimmed !== '') {
                $parsed = [];
                parse_str($trimmed, $parsed);
                $body = is_array($parsed) ? $parsed : [];
            }
        }

        // Dev convenience: allow query-string Params for POST/PATCH/DELETE too,
        // but let body values win when both are present.
        return array_merge($this->query, $body);
    }

    public function requireSetupToken(): void
    {
        $expected = getenv('APP_SETUP_TOKEN') ?: '';

        if (trim($expected) === '') {
            Response::json(500, false, 'setup token is not configured', []);
            exit;
        }

        $provided = $this->server['HTTP_X_SETUP_TOKEN'] ?? '';

        if (!is_string($provided) || trim($provided) === '') {
            $provided = (string) ($this->post['setup_token'] ?? '');
        }

        if (trim($provided) === '' || !hash_equals($expected, trim($provided))) {
            Response::json(401, false, 'setup token is invalid', []);
            exit;
        }
    }
}
