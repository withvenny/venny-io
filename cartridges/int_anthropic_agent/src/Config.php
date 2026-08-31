<?php

declare(strict_types=1);

namespace Venny\Cartridges\AnthropicAgentSdk;

use Venny\Cartridges\AnthropicAgentSdk\Exceptions\ConfigurationException;

final class Config
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $nodeBinary = 'node',
        private readonly ?string $workingDirectory = null,
        private readonly ?string $defaultModel = null,
        private readonly string $permissionMode = 'default',
        private readonly int $maxTurns = 8,
    ) {
        $this->validate();
    }

    public static function fromEnvironment(): self
    {
        $node = self::nullableEnv('v_CLAUDE_AGENT_NODE_BINARY') ?? 'node';
        $maxTurns = self::nullableEnv('v_CLAUDE_AGENT_MAX_TURNS');

        return new self(
            apiKey: trim((string) getenv('v_ANTHROPIC_API_KEY')),
            nodeBinary: $node,
            workingDirectory: self::nullableEnv('v_CLAUDE_AGENT_WORKING_DIRECTORY'),
            defaultModel: self::nullableEnv('v_CLAUDE_AGENT_DEFAULT_MODEL'),
            permissionMode: self::nullableEnv('v_CLAUDE_AGENT_PERMISSION_MODE') ?? 'default',
            maxTurns: $maxTurns !== null ? (int) $maxTurns : 8,
        );
    }

    public function apiKey(): string { return $this->apiKey; }
    public function nodeBinary(): string { return $this->nodeBinary; }
    public function workingDirectory(): ?string { return $this->workingDirectory; }
    public function defaultModel(): ?string { return $this->defaultModel; }
    public function permissionMode(): string { return $this->permissionMode; }
    public function maxTurns(): int { return $this->maxTurns; }

    private function validate(): void
    {
        if ($this->apiKey === '') {
            throw new ConfigurationException('v_ANTHROPIC_API_KEY is required.');
        }

        if (trim($this->nodeBinary) === '') {
            throw new ConfigurationException('v_CLAUDE_AGENT_NODE_BINARY cannot be empty.');
        }

        if ($this->maxTurns < 1) {
            throw new ConfigurationException('v_CLAUDE_AGENT_MAX_TURNS must be at least 1.');
        }

        if ($this->permissionMode === 'bypassPermissions') {
            throw new ConfigurationException(
                'bypassPermissions is disabled by int_anthropic_agent_sdk v1.'
            );
        }

        if (
            $this->workingDirectory !== null &&
            (!is_dir($this->workingDirectory) || !is_readable($this->workingDirectory))
        ) {
            throw new ConfigurationException(
                'v_CLAUDE_AGENT_WORKING_DIRECTORY must be a readable directory.'
            );
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
