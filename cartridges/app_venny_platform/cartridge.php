<?php

declare(strict_types=1);

return [
    'manifest_version' => '2.0.0',
    'name' => 'app_venny_platform',
    'type' => 'app',
    'provider' => 'venny',
    'domain' => 'platform',
    'version' => '2.0.0',
    'description' => 'Venny I/O core platform cartridge providing shared runtime primitives, database access, response handling, and platform APIs.',
    'tool' => null,
    'tool_url' => null,
    'php' => '>=8.2',
    'requires' => [],
    'dependencies' => [
        'php_extensions' => [
            'ext-pdo',
            'ext-pdo_pgsql',
        ],
        'composer' => [],
        'npm' => [],
    ],
    'configuration' => [],
    'capabilities' => [],
    'documentation' => [],
    'routes' => __DIR__ . '/routes.php',
    'sql' => [
        'schema' => __DIR__ . '/sql/schema.sql',
        'constraints' => __DIR__ . '/sql/constraints.sql',
        'indexes' => __DIR__ . '/sql/indexes.sql',
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
