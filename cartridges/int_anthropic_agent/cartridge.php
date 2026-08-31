<?php

declare(strict_types=1);

return [
    'manifest_version' => '2.0.0',
    'name' => 'int_anthropic_agent',
    'type' => 'integration',
    'provider' => 'Anthropic',
    'domain' => 'anthropic_agent',
    'version' => '2.0.0',
    'description' => 'Venny I/O bridge to Anthropic\'s official Claude Agent SDK for bounded agent queries, tool permissions, working-directory execution, and streamed agent events.',
    'tool' => 'Claude Agent SDK',
    'tool_url' => 'https://platform.claude.com/docs/en/agent-sdk/overview',
    'php' => '>=8.2',
    'requires' => [
        'app_venny_platform',
    ],
    'dependencies' => [
        'php_extensions' => [],
        'composer' => [],
        'npm' => [
            '@anthropic-ai/claude-agent-sdk' => '^0.3.250',
        ],
    ],
    'configuration' => [
        'v_ANTHROPIC_API_KEY',
        'v_CLAUDE_AGENT_NODE_BINARY',
        'v_CLAUDE_AGENT_WORKING_DIRECTORY',
        'v_CLAUDE_AGENT_DEFAULT_MODEL',
        'v_CLAUDE_AGENT_PERMISSION_MODE',
        'v_CLAUDE_AGENT_MAX_TURNS',
    ],
    'capabilities' => [
        'agent_query',
        'agent_event_stream',
        'working_directory',
        'built_in_tool_allowlist',
        'tool_permission_mode',
        'session_isolation_defaults',
        'configuration_validation',
        'health_check',
    ],
    'documentation' => [
        'https://platform.claude.com/docs/en/agent-sdk/overview',
        'https://github.com/anthropics/claude-agent-sdk-typescript',
        'https://www.npmjs.com/package/@anthropic-ai/claude-agent-sdk',
    ],
    'routes' => null,
    'sql' => [
        'schema' => null,
        'constraints' => null,
        'indexes' => null,
    ],
    'business_manager' => [
        'metadata' => __DIR__ . '/bm/metadata.php',
        'configuration' => __DIR__ . '/bm/configuration.php',
        'health' => __DIR__ . '/bm/health.php',
    ],
    'autoload' => [
        'Venny\\Cartridges\\AnthropicAgentSdk\\' => __DIR__ . '/src',
    ],
];
