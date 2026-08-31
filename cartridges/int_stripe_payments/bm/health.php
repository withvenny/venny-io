<?php

declare(strict_types=1);

use Venny\Cartridges\StripePayments\Config;
use Venny\Cartridges\StripePayments\Provider;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

try {
    $provider = new Provider(Config::fromEnvironment());

    return $provider->healthCheck()->toArray();
} catch (Throwable $exception) {
    $context = method_exists($exception, 'providerContext')
        ? $exception->providerContext()
        : [];

    return [
        'success' => false,
        'provider' => 'stripe',
        'tool' => 'payments',
        'operation' => 'health_check',
        'provider_id' => null,
        'data' => [
            'configured' => false,
        ],
        'metadata' => [
            'error_class' => $exception::class,
            'error_message' => $exception->getMessage(),
            'provider_context' => $context,
        ],
    ];
}
