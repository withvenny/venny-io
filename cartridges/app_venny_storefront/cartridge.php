<?php

declare(strict_types=1);

return [
    'name' => 'app_venny_storefront',
    'type' => 'app',
    'provider' => 'venny',
    'domain' => 'storefront',
    'version' => '1.0.0',
    'requires' => [
        'app_venny_platform',
        'app_venny_cms',
    ],
    'routes' => __DIR__ . '/routes.php',
    'sql' => [
        'schema' => __DIR__ . '/sql/schema.sql',
        'constraints' => __DIR__ . '/sql/constraints.sql',
        'indexes' => __DIR__ . '/sql/indexes.sql',
    ],
];
