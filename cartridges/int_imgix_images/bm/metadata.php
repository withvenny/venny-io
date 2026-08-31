<?php

declare(strict_types=1);

return [
    'name' => 'int_imgix_images',
    'display_name' => 'Imgix Images',
    'version' => '1.0.0',
    'type' => 'integration',
    'provider' => 'Imgix',
    'tool' => 'Image URL API',
    'description' => 'Generates Imgix transformation URLs, signed URLs, and responsive srcsets for Venny I/O.',
    'purpose' => 'Provide Venny I/O application cartridges with a reusable server-side interface for Imgix image URL generation and responsive image delivery without coupling application code directly to the Imgix PHP SDK.',
    'tool_url' => 'https://www.imgix.com/api',
    'documentation' => [
        'https://www.imgix.com/api',
        'https://github.com/imgix/imgix-php',
        'https://github.com/imgix/imgix-blueprint',
    ],
    'configuration' => [
        [
            'key' => 'v_IMGIX_DOMAIN',
            'required' => true,
            'secret' => false,
        ],
        [
            'key' => 'v_IMGIX_SECURE_URL_TOKEN',
            'required' => false,
            'secret' => true,
        ],
        [
            'key' => 'v_IMGIX_USE_HTTPS',
            'required' => false,
            'secret' => false,
        ],
        [
            'key' => 'v_IMGIX_INCLUDE_LIBRARY_PARAM',
            'required' => false,
            'secret' => false,
        ],
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
    'dependencies' => [
        'php' => '>=8.1',
        'composer' => [
            'imgix/imgix-php' => '^4.1',
        ],
    ],
    'health_check_available' => true,
    'configuration_screen_available' => true,
    'owns_sql' => false,
];
