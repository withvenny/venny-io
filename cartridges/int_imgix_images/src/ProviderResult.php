<?php

declare(strict_types=1);

namespace Venny\Cartridges\ImgixImages;

final class ProviderResult
{
    public function __construct(
        private readonly bool $success,
        private readonly string $operation,
        private readonly array $data = [],
        private readonly array $metadata = [],
    ) {
    }

    public static function ok(
        string $operation,
        array $data = [],
        array $metadata = []
    ): self {
        return new self(true, $operation, $data, $metadata);
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function provider(): string
    {
        return 'imgix';
    }

    public function tool(): string
    {
        return 'image_url_api';
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'provider' => $this->provider(),
            'tool' => $this->tool(),
            'operation' => $this->operation,
            'provider_id' => null,
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];
    }
}
