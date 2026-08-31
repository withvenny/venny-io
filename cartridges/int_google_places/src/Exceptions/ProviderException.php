<?php

declare(strict_types=1);

namespace Venny\Cartridges\GooglePlaces\Exceptions;

use RuntimeException;
use Throwable;

class ProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null,
        private readonly array $providerContext = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function providerContext(): array
    {
        return $this->providerContext;
    }

    public static function fromHttpResponse(int $status, array $body): self
    {
        $error = is_array($body['error'] ?? null) ? $body['error'] : [];
        $message = (string) ($error['message'] ?? 'Google Places API request failed.');
        $providerStatus = (string) ($error['status'] ?? '');

        $context = [
            'status_code' => $status,
            'google_status' => $providerStatus ?: null,
            'google_code' => $error['code'] ?? null,
        ];

        if ($status === 401 || $status === 403) {
            return new AuthenticationException($message, $status, null, $context);
        }

        if ($status === 429 || $providerStatus === 'RESOURCE_EXHAUSTED') {
            return new QuotaException($message, $status, null, $context);
        }

        if ($status === 400) {
            return new ValidationException($message, $status, null, $context);
        }

        return new self($message, $status, null, $context);
    }
}
