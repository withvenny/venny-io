<?php

declare(strict_types=1);

return [
    'name' => 'app_venny_account',
    'type' => 'app',
    'provider' => 'venny',
    'domain' => 'account',
    'version' => '1.0.0',
    'requires' => [
        'app_venny_platform',
        'app_venny_identity',
        'app_venny_authentication',
    ],
    'routes' => __DIR__ . '/routes.php',
    'sql' => [
        'schema' => __DIR__ . '/sql/schema.sql',
        'constraints' => __DIR__ . '/sql/constraints.sql',
        'indexes' => __DIR__ . '/sql/indexes.sql',
    ],
];
