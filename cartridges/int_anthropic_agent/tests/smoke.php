<?php

declare(strict_types=1);

use Venny\Cartridges\AnthropicAgentSdk\Config;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

$config = new Config(
    apiKey: 'sk-ant-example-only',
    nodeBinary: 'node',
    defaultModel: 'claude-sonnet-5',
    permissionMode: 'default',
    maxTurns: 4,
);

assert($config->maxTurns() === 4);
assert($config->permissionMode() === 'default');

echo "int_anthropic_agent_sdk local configuration smoke tests passed.\n";
