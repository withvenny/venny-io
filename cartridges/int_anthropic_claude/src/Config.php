<?php

declare(strict_types=1);

namespace Venny\Cartridges\AnthropicClaude;

use Venny\Cartridges\AnthropicClaude\Exceptions\ConfigurationException;

final class Config
{
    public function __construct(
        private readonly string $apiKey,
        private readonly ?string $defaultModel = null,
        private readonly int $maxTokens = 4096,
    ) {
        $this->validate();
    }

    public static function fromEnvironment(): self
    {
        $maxTokens = getenv('v_ANTHROPIC_MAX_TOKENS');

        return new self(
            apiKey: trim((string) getenv('v_ANTHROPIC_API_KEY')),
            defaultModel: self::nullableEnv('v_ANTHROPIC_DEFAULT_MODEL'),
            maxTokens: $maxTokens !== false && trim((string) $maxTokens) !== ''
                ? (int) $maxTokens
                : 4096,
        );
    }

    public function apiKey(): string { return $this->apiKey; }
    public function defaultModel(): ?string { return $this->defaultModel; }
    public function maxTokens(): int { return $this->maxTokens; }

    private function validate(): void
    {
        if ($this->apiKey === '') {
            throw new ConfigurationException('v_ANTHROPIC_API_KEY is required.');
        }

        if ($this->maxTokens < 1) {
            throw new ConfigurationException('v_ANTHROPIC_MAX_TOKENS must be at least 1.');
        }
    }

    private static function nullableEnv(string $name): ?string
    {
        $value = getenv($name);
        if ($value === false || trim((string) $value) === '') {
            return null;
        }
        return trim((string) $value);
    }
}
