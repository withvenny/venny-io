<?php

declare(strict_types=1);

return [
    'manifest_version' => '2.0.0',
    'name' => 'int_imgix_images',
    'type' => 'integration',
    'provider' => 'Imgix',
    'domain' => 'imgix_images',
    'version' => '2.0.0',
    'description' => 'Venny I/O integration cartridge for generating Imgix image transformation URLs and responsive srcsets from vanilla PHP.',
    'tool' => 'Image URL API',
    'tool_url' => 'https://www.imgix.com/api',
    'php' => '>=8.2',
    'requires' => [
        'app_venny_platform',
    ],
    'dependencies' => [
        'php_extensions' => [],
        'composer' => [
            'imgix/imgix-php' => '^4.1',
        ],
        'npm' => [],
    ],
    'configuration' => [
        'v_IMGIX_DOMAIN',
        'v_IMGIX_SECURE_URL_TOKEN',
        'v_IMGIX_USE_HTTPS',
        'v_IMGIX_INCLUDE_LIBRARY_PARAM',
    ],
    'capabilities' => [
        'build_url',
        'build_signed_url',
        'build_srcset',
        'resize',
        'thumbnail',
        'configuration_validation',
        'health_check',
    ],
    'documentation' => [
        'https://github.com/imgix/imgix-php',
        'https://www.imgix.com/api',
        'https://github.com/imgix/imgix-blueprint',
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
        'Venny\\Cartridges\\ImgixImages\\' => __DIR__ . '/src',
    ],
];
