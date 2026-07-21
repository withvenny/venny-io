<?php

declare(strict_types=1);

return [
    'name' => 'app_venny_platform',
    'type' => 'app',
    'provider' => 'venny',
    'domain' => 'platform',
    'version' => '1.0.0',
    'requires' => [],
    'routes' => __DIR__ . '/routes.php',
    'sql' => [
        'schema' => __DIR__ . '/sql/schema.sql',
        'constraints' => __DIR__ . '/sql/constraints.sql',
        'indexes' => __DIR__ . '/sql/indexes.sql',
    ],
];
