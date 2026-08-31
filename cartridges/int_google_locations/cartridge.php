<?php

declare(strict_types=1);

return [
    'manifest_version' => '2.0.0',
    'name' => 'int_google_locations',
    'type' => 'integration',
    'provider' => 'Google Maps Platform',
    'domain' => 'google_locations',
    'version' => '2.0.0',
    'description' => 'Venny I/O Google Maps Platform location cartridge for forward geocoding, reverse geocoding, Place ID resolution, normalized address components, and coordinates.',
    'tool' => 'Geocoding API',
    'tool_url' => 'https://developers.google.com/maps/documentation/geocoding',
    'php' => '>=8.2',
    'requires' => [
        'app_venny_platform',
    ],
    'dependencies' => [
        'php_extensions' => [
            'ext-curl',
            'ext-json',
        ],
        'composer' => [],
        'npm' => [],
    ],
    'configuration' => [
        'v_GOOGLE_MAPS_SERVER_KEY',
        'v_GOOGLE_GEOCODING_LANGUAGE',
        'v_GOOGLE_GEOCODING_REGION',
    ],
    'capabilities' => [
        'geocode_address',
        'reverse_geocode',
        'geocode_place_id',
        'normalize_address',
        'get_coordinates',
        'configuration_validation',
        'health_check',
    ],
    'documentation' => [
        'https://developers.google.com/maps/documentation/geocoding',
        'https://developers.google.com/maps/documentation/geocoding/requests-geocoding',
        'https://developers.google.com/maps/documentation/geocoding/requests-reverse-geocoding',
        'https://developers.google.com/maps/api-security-best-practices',
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
        'Venny\\Cartridges\\GoogleLocations\\' => __DIR__ . '/src',
    ],
];
