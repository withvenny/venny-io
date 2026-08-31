<?php

declare(strict_types=1);

$exists = static function (string $key): bool {
    $value = getenv($key);
    return $value !== false && trim((string) $value) !== '';
};

return [
    'cartridge' => 'int_anthropic_agent_sdk',
    'configured' => $exists('v_ANTHROPIC_API_KEY'),
    'values' => [
        'v_ANTHROPIC_API_KEY' => $exists('v_ANTHROPIC_API_KEY') ? 'configured' : null,
        'v_CLAUDE_AGENT_NODE_BINARY' => getenv('v_CLAUDE_AGENT_NODE_BINARY') ?: 'node',
        'v_CLAUDE_AGENT_WORKING_DIRECTORY' => getenv('v_CLAUDE_AGENT_WORKING_DIRECTORY') ?: null,
        'v_CLAUDE_AGENT_DEFAULT_MODEL' => getenv('v_CLAUDE_AGENT_DEFAULT_MODEL') ?: null,
        'v_CLAUDE_AGENT_PERMISSION_MODE' => getenv('v_CLAUDE_AGENT_PERMISSION_MODE') ?: 'default',
        'v_CLAUDE_AGENT_MAX_TURNS' => getenv('v_CLAUDE_AGENT_MAX_TURNS') ?: '8',
    ],
    'missing_required' => $exists('v_ANTHROPIC_API_KEY') ? [] : ['v_ANTHROPIC_API_KEY'],
    'security_defaults' => [
        'tools' => [],
        'setting_sources' => [],
        'persist_session' => false,
        'permission_bypass_allowed' => false,
    ],
];
