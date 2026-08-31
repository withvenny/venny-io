<?php

declare(strict_types=1);

use Venny\Cartridges\AwsS3\Config;
use Venny\Cartridges\AwsS3\Provider;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

try {
    return (new Provider(Config::fromEnvironment()))
        ->healthCheck()
        ->toArray();
} catch (Throwable $exception) {
    return [
        'success' => false,
        'provider' => 'aws',
        'tool' => 's3',
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
