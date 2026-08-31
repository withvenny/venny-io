<?php

declare(strict_types=1);

use Venny\Cartridges\ImgixImages\Config;
use Venny\Cartridges\ImgixImages\Provider;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

try {
    $provider = new Provider(Config::fromEnvironment());

    return $provider->healthCheck()->toArray();
} catch (Throwable $exception) {
    return [
        'success' => false,
        'provider' => 'imgix',
        'tool' => 'image_url_api',
        'operation' => 'health_check',
        'data' => [
            'configured' => false,
        ],
        'metadata' => [
            'error_class' => $exception::class,
            'error_message' => $exception->getMessage(),
        ],
    ];
}
