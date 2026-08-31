<?php

declare(strict_types=1);

return [
    'manifest_version' => '2.0.0',
    'name' => 'int_google_places',
    'type' => 'integration',
    'provider' => 'Google Maps Platform',
    'domain' => 'google_places',
    'version' => '2.0.0',
    'description' => 'Venny I/O Google Places integration cartridge for Autocomplete, Place Details, Text Search, Nearby Search, Place Photos, session-token handling, and provider diagnostics.',
    'tool' => 'Places API (New)',
    'tool_url' => 'https://developers.google.com/maps/documentation/places/web-service/op-overview',
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
        'v_GOOGLE_PLACES_LANGUAGE',
        'v_GOOGLE_PLACES_REGION_CODE',
    ],
    'capabilities' => [
        'autocomplete',
        'place_details',
        'text_search',
        'nearby_search',
        'place_photo_uri',
        'session_token_generation',
        'configuration_validation',
        'health_check',
    ],
    'documentation' => [
        'https://developers.google.com/maps/documentation/places/web-service/op-overview',
        'https://developers.google.com/maps/documentation/places/web-service/place-details',
        'https://developers.google.com/maps/documentation/places/web-service/text-search',
        'https://developers.google.com/maps/documentation/places/web-service/nearby-search',
        'https://developers.google.com/maps/documentation/places/web-service/place-photos',
        'https://developers.google.com/maps/documentation/places/web-service/place-autocomplete',
        'https://developers.google.com/maps/documentation/places/web-service/choose-fields',
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
        'Venny\\Cartridges\\GooglePlaces\\' => __DIR__ . '/src',
    ],
];
