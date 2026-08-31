<?php

declare(strict_types=1);

return [
    'manifest_version' => '2.0.0',
    'name' => 'bm_venny_businessmanager',
    'type' => 'bm',
    'provider' => 'venny',
    'domain' => 'businessmanager',
    'version' => '2.0.3',
    'description' => 'Venny I/O Business Manager administrative control surface for cartridge visibility, environment configuration, database installation, and runtime diagnostics.',
    'tool' => null,
    'tool_url' => null,
    'php' => '>=8.2',
    'requires' => [
        'app_venny_platform',
    ],
    'dependencies' => [
        'php_extensions' => [],
        'composer' => [],
        'npm' => [],
    ],
    'configuration' => [
        'v_BUSINESS_MANAGER_PASSPHRASE',
        'DATABASE_URL',
    ],
    'capabilities' => [],
    'documentation' => [],
    'routes' => __DIR__ . '/routes.php',
    'sql' => [
        'schema' => null,
        'constraints' => null,
        'indexes' => null,
    ],
    'business_manager' => [
        'metadata' => null,
        'configuration' => null,
        'health' => null,
    ],
    'autoload' => [
        'VennyIO\\' => __DIR__ . '/src',
    ],
];
