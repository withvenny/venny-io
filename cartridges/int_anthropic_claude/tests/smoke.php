<?php

declare(strict_types=1);

use Venny\Cartridges\AnthropicClaude\Config;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

$config = new Config(
    apiKey: 'sk-ant-example-only',
    defaultModel: 'claude-sonnet-5',
    maxTokens: 1024
);

assert($config->defaultModel() === 'claude-sonnet-5');
assert($config->maxTokens() === 1024);

echo "int_anthropic_claude local configuration smoke tests passed.\n";
