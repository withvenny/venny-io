<?php

declare(strict_types=1);

use Venny\Cartridges\AnthropicAgentSdk\Config;
use Venny\Cartridges\AnthropicAgentSdk\Provider;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

try {
    return (new Provider(Config::fromEnvironment()))->healthCheck()->toArray();
} catch (Throwable $exception) {
    return [
        'success' => false,
        'provider' => 'anthropic',
        'tool' => 'agent_sdk',
        'operation' => 'health_check',
        'provider_id' => null,
        'data' => ['configured' => false],
        'metadata' => [
            'error_class' => $exception::class,
            'error_message' => $exception->getMessage(),
            'provider_context' => method_exists($exception, 'providerContext')
                ? $exception->providerContext()
                : [],
        ],
    ];
}
