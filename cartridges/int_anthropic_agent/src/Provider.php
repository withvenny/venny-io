<?php

declare(strict_types=1);

namespace Venny\Cartridges\AnthropicAgentSdk;

use Venny\Cartridges\AnthropicAgentSdk\Exceptions\ProviderException;
use Venny\Cartridges\AnthropicAgentSdk\Exceptions\ValidationException;

final class Provider
{
    private readonly string $runner;

    public function __construct(private readonly Config $config)
    {
        $this->runner = dirname(__DIR__) . '/agent/runner.mjs';
    }

    public function query(string $prompt, array $options = []): ProviderResult
    {
        $prompt = trim($prompt);

        if ($prompt === '') {
            throw new ValidationException('Agent prompt is required.');
        }

        $options = $this->normalizeOptions($options);

        if (($options['permissionMode'] ?? null) === 'bypassPermissions') {
            throw new ValidationException(
                'bypassPermissions is disabled by int_anthropic_agent_sdk v1.'
            );
        }

        if (($options['allowDangerouslySkipPermissions'] ?? false) === true) {
            throw new ValidationException(
                'allowDangerouslySkipPermissions is disabled by int_anthropic_agent_sdk v1.'
            );
        }

        $payload = json_encode(
            ['prompt' => $prompt, 'options' => $options],
            JSON_THROW_ON_ERROR
        );

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = $_ENV;
        $env['ANTHROPIC_API_KEY'] = $this->config->apiKey();

        $process = proc_open(
            [$this->config->nodeBinary(), $this->runner],
            $descriptorSpec,
            $pipes,
            dirname(__DIR__) . '/agent',
            $env
        );

        if (!is_resource($process)) {
            throw new ProviderException('Unable to start Claude Agent SDK runtime.');
        }

        fwrite($pipes[0], $payload);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        try {
            $decoded = json_decode((string) $stdout, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ProviderException(
                'Claude Agent SDK runtime returned invalid JSON.',
                $exitCode,
                $e,
                [
                    'exit_code' => $exitCode,
                    'stderr' => $this->safeDiagnostic($stderr),
                ]
            );
        }

        if ($exitCode !== 0 || !($decoded['success'] ?? false)) {
            throw new ProviderException(
                (string) ($decoded['error'] ?? 'Claude Agent SDK query failed.'),
                $exitCode,
                null,
                [
                    'exit_code' => $exitCode,
                    'details' => $decoded['details'] ?? [],
                    'stderr' => $this->safeDiagnostic($stderr),
                ]
            );
        }

        $result = is_array($decoded['result'] ?? null)
            ? $decoded['result']
            : null;

        $providerId = null;
        if (is_array($result)) {
            foreach (['session_id', 'uuid', 'id'] as $field) {
                if (isset($result[$field]) && is_string($result[$field])) {
                    $providerId = $result[$field];
                    break;
                }
            }
        }

        return ProviderResult::ok(
            'query',
            $providerId,
            [
                'messages' => $decoded['messages'] ?? [],
                'result' => $result,
            ],
            [
                'runtime' => 'node',
                'permission_mode' => $options['permissionMode'] ?? 'default',
                'max_turns' => $options['maxTurns'] ?? $this->config->maxTurns(),
                'tools' => $options['tools'] ?? [],
            ]
        );
    }

    public function healthCheck(): ProviderResult
    {
        $nodeVersion = $this->commandVersion([$this->config->nodeBinary(), '--version']);
        $packageJson = dirname(__DIR__) . '/agent/package.json';
        $sdkPackage = dirname(__DIR__) . '/agent/node_modules/@anthropic-ai/claude-agent-sdk/package.json';

        return ProviderResult::ok(
            'health_check',
            null,
            [
                'configured' => true,
                'remote_test_performed' => false,
                'node_binary' => $this->config->nodeBinary(),
                'node_version' => $nodeVersion,
                'runner_exists' => is_file($this->runner),
                'package_json_exists' => is_file($packageJson),
                'agent_sdk_installed' => is_file($sdkPackage),
                'working_directory' => $this->config->workingDirectory(),
                'default_model' => $this->config->defaultModel(),
                'permission_mode' => $this->config->permissionMode(),
                'max_turns' => $this->config->maxTurns(),
                'safe_defaults' => [
                    'tools' => [],
                    'setting_sources' => [],
                    'persist_session' => false,
                    'permission_bypass_allowed' => false,
                ],
            ]
        );
    }

    private function normalizeOptions(array $options): array
    {
        $normalized = [
            'tools' => isset($options['tools']) && is_array($options['tools'])
                ? array_values($options['tools'])
                : [],
            'allowedTools' => isset($options['allowed_tools']) && is_array($options['allowed_tools'])
                ? array_values($options['allowed_tools'])
                : [],
            'disallowedTools' => isset($options['disallowed_tools']) && is_array($options['disallowed_tools'])
                ? array_values($options['disallowed_tools'])
                : [],
            'permissionMode' => (string) ($options['permission_mode'] ?? $this->config->permissionMode()),
            'settingSources' => isset($options['setting_sources']) && is_array($options['setting_sources'])
                ? array_values($options['setting_sources'])
                : [],
            'persistSession' => (bool) ($options['persist_session'] ?? false),
            'maxTurns' => (int) ($options['max_turns'] ?? $this->config->maxTurns()),
        ];

        if ($normalized['maxTurns'] < 1) {
            throw new ValidationException('max_turns must be at least 1.');
        }

        $model = $options['model'] ?? $this->config->defaultModel();
        if ($model !== null && trim((string) $model) !== '') {
            $normalized['model'] = (string) $model;
        }

        $cwd = $options['cwd'] ?? $this->config->workingDirectory();
        if ($cwd !== null && trim((string) $cwd) !== '') {
            $cwd = (string) $cwd;
            if (!is_dir($cwd) || !is_readable($cwd)) {
                throw new ValidationException('cwd must be a readable directory.');
            }
            $normalized['cwd'] = $cwd;
        }

        $passthrough = [
            'system_prompt' => 'systemPrompt',
            'include_partial_messages' => 'includePartialMessages',
            'mcp_servers' => 'mcpServers',
            'agents' => 'agents',
            'skills' => 'skills',
            'plugins' => 'plugins',
            'betas' => 'betas',
            'thinking' => 'thinking',
            'sandbox' => 'sandbox',
            'strict_mcp_config' => 'strictMcpConfig',
        ];

        foreach ($passthrough as $input => $sdk) {
            if (array_key_exists($input, $options)) {
                $normalized[$sdk] = $options[$input];
            }
        }

        return $normalized;
    }

    private function commandVersion(array $command): ?string
    {
        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            return null;
        }

        $stdout = trim((string) stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        return $exit === 0 ? $stdout : null;
    }

    private function safeDiagnostic(string|false $value): ?string
    {
        if ($value === false) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 4000);
    }
}
