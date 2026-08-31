<?php

declare(strict_types=1);

return [
    'manifest_version' => '2.0.0',
    'name' => 'int_anthropic_claude',
    'type' => 'integration',
    'provider' => 'Anthropic',
    'domain' => 'anthropic_claude',
    'version' => '2.0.0',
    'description' => 'Venny I/O Anthropic Claude API cartridge for Messages, streaming, token counting, tool use, structured outputs, prompt caching, files, and provider diagnostics.',
    'tool' => 'Claude API',
    'tool_url' => 'https://platform.claude.com/docs/en/api/overview',
    'php' => '>=8.2',
    'requires' => [
        'app_venny_platform',
    ],
    'dependencies' => [
        'php_extensions' => [],
        'composer' => [
            'anthropic-ai/sdk' => '^0.44.0',
            'guzzlehttp/guzzle' => '^7.0',
        ],
        'npm' => [],
    ],
    'configuration' => [
        'v_ANTHROPIC_API_KEY',
        'v_ANTHROPIC_DEFAULT_MODEL',
        'v_ANTHROPIC_MAX_TOKENS',
    ],
    'capabilities' => [
        'messages',
        'stream_messages',
        'count_tokens',
        'tool_use',
        'structured_outputs',
        'prompt_caching',
        'files',
        'provider_diagnostics',
        'health_check',
    ],
    'documentation' => [
        'https://platform.claude.com/docs/en/api/overview',
        'https://platform.claude.com/docs/en/api/messages',
        'https://platform.claude.com/docs/en/api/php',
        'https://github.com/anthropics/anthropic-sdk-php',
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
        'Venny\\Cartridges\\AnthropicClaude\\' => __DIR__ . '/src',
    ],
];
