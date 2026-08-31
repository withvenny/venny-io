<?php

declare(strict_types=1);

$exists = static function (string $key): bool {
    $value = getenv($key);
    return $value !== false && trim((string) $value) !== '';
};

return [
    'cartridge' => 'int_anthropic_claude',
    'configured' => $exists('v_ANTHROPIC_API_KEY'),
    'values' => [
        'v_ANTHROPIC_API_KEY' => $exists('v_ANTHROPIC_API_KEY') ? 'configured' : null,
        'v_ANTHROPIC_DEFAULT_MODEL' => getenv('v_ANTHROPIC_DEFAULT_MODEL') ?: null,
        'v_ANTHROPIC_MAX_TOKENS' => getenv('v_ANTHROPIC_MAX_TOKENS') ?: '4096',
    ],
    'missing_required' => $exists('v_ANTHROPIC_API_KEY') ? [] : ['v_ANTHROPIC_API_KEY'],
];
