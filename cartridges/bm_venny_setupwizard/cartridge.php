<?php

declare(strict_types=1);

return [
    'name' => 'bm_venny_setupwizard',
    'type' => 'bm',
    'provider' => 'venny',
    'domain' => 'setupwizard',
    'version' => '1.0.0',
    'requires' => [
        'app_venny_platform',
    ],
    'routes' => __DIR__ . '/routes.php',
    'sql' => [
        'experience_matrix' => __DIR__ . '/sql/experience-matrix.csv',
    ],
];
