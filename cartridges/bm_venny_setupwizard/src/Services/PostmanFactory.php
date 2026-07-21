<?php

declare(strict_types=1);

final class PostmanFactory
{
    private static array $endpointsByModule = [
        'Platform Primitives' => ['/health', '/apps', '/keys', '/sessions', '/windows'],
        'Personal Information' => ['/persons', '/users', '/profiles', '/assets', '/content', '/communications', '/deliveries'],
        'CRM' => ['/contacts', '/companies', '/deals', '/pipelines', '/stages', '/activities', '/tasks', '/notes', '/tags'],
        'Engagement' => ['/threads', '/messages', '/posts', '/followships', '/groups', '/acknowledgements', '/comments'],
        'E-Commerce' => ['/catalogs', '/categories', '/products', '/items', '/transactions', '/orders', '/coupons', '/customers'],
    ];

    public static function collection(string $appName, array $modules): array
    {
        $items = [];
        foreach ($modules as $module) {
            foreach (self::$endpointsByModule[$module] ?? [] as $endpoint) {
                $name = trim($endpoint, '/') ?: 'health';
                $items[] = [
                    'name' => strtoupper($name) . ' - list',
                    'request' => [
                        'method' => 'GET',
                        'header' => [['key' => 'X-API-Key', 'value' => '{{api_key}}']],
                        'url' => ['raw' => '{{base_url}}' . $endpoint, 'host' => ['{{base_url}}'], 'path' => array_values(array_filter(explode('/', $endpoint)))],
                    ],
                ];
                if ($endpoint !== '/health') {
                    $items[] = [
                        'name' => strtoupper($name) . ' - create',
                        'request' => [
                            'method' => 'POST',
                            'header' => [
                                ['key' => 'X-API-Key', 'value' => '{{api_key}}'],
                                ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],
                            ],
                            'body' => ['mode' => 'urlencoded', 'urlencoded' => [['key' => $name . '_name', 'value' => 'Example ' . ucfirst($name), 'type' => 'text']]],
                            'url' => ['raw' => '{{base_url}}' . $endpoint, 'host' => ['{{base_url}}'], 'path' => array_values(array_filter(explode('/', $endpoint)))],
                        ],
                    ];
                }
            }
        }
        return [
            'info' => [
                'name' => 'Venny I/O API - ' . $appName,
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'variable' => [
                ['key' => 'base_url', 'value' => 'https://api.example.com'],
                ['key' => 'api_key', 'value' => 'paste_generated_key_here'],
            ],
            'item' => $items,
        ];
    }
}
