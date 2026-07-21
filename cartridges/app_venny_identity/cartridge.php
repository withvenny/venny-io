<?php

declare(strict_types=1);

return [
    'name' => 'app_venny_identity',
    'type' => 'app',
    'provider' => 'venny',
    'domain' => 'identity',
    'version' => '1.0.0',
    'requires' => [
        'app_venny_platform',
    ],
    'routes' => __DIR__ . '/routes.php',
    'sql' => [
        'schema' => __DIR__ . '/sql/schema.sql',
        'constraints' => __DIR__ . '/sql/constraints.sql',
        'indexes' => __DIR__ . '/sql/indexes.sql',
    ],
];
