<?php

declare(strict_types=1);

namespace Venny\Cartridges\AnthropicClaude;

final class ProviderResult
{
    public function __construct(
        private readonly bool $success,
        private readonly string $operation,
        private readonly ?string $providerId = null,
        private readonly array $data = [],
        private readonly array $metadata = [],
    ) {}

    public static function ok(
        string $operation,
        ?string $providerId = null,
        array $data = [],
        array $metadata = []
    ): self {
        return new self(true, $operation, $providerId, $data, $metadata);
    }

    public function success(): bool { return $this->success; }
    public function provider(): string { return 'anthropic'; }
    public function tool(): string { return 'claude'; }
    public function operation(): string { return $this->operation; }
    public function providerId(): ?string { return $this->providerId; }
    public function data(): array { return $this->data; }
    public function metadata(): array { return $this->metadata; }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'provider' => $this->provider(),
            'tool' => $this->tool(),
            'operation' => $this->operation,
            'provider_id' => $this->providerId,
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];
    }
}
