<?php

declare(strict_types=1);

use VennyIO\Controllers\AppsController;
use VennyIO\Controllers\CartridgesController;
use VennyIO\Controllers\KeysController;
use VennyIO\Controllers\PlatformResourceController;
use VennyIO\Controllers\SessionsController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\AppsRepository;
use VennyIO\Repositories\KeysRepository;
use VennyIO\Repositories\PlatformResourceRepository;
use VennyIO\Repositories\SessionsRepository;
use VennyIO\Support\ApiKeyAuth;
use VennyIO\Support\Database;
use VennyIO\Support\Response;

/** @var Router $router */

$platformBasePath = dirname(__DIR__, 2);

$router->get('#^/health$#', static function (): void {
    Response::json(200, true, 'healthy', [
        'service' => 'venny-io-api',
        'platform_cartridge' => 'app_venny_platform',
    ]);
});

$router->get('#^/db-health$#', static function (Request $request): void {
    $request->requireSetupToken();
    Response::json(200, true, 'database healthy', Database::health());
});

$authorizedDb = static function (): PDO {
    $db = Database::connection();
    ApiKeyAuth::require($db);
    return $db;
};

$makeAppsController = static function () use ($authorizedDb): AppsController {
    return new AppsController(new AppsRepository($authorizedDb()));
};

$router->get('#^/apps$#', static function () use ($makeAppsController): void {
    $makeAppsController()->index();
});

$router->get('#^/apps/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeAppsController): void {
    $makeAppsController()->show($params['id']);
});

$router->post('#^/apps$#', static function (Request $request) use ($makeAppsController): void {
    $makeAppsController()->store($request);
});

$router->patch('#^/apps/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeAppsController): void {
    $makeAppsController()->update($params['id'], $request);
});

$router->delete('#^/apps/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeAppsController): void {
    $makeAppsController()->destroy($params['id']);
});

$makeKeysController = static function () use ($authorizedDb): KeysController {
    return new KeysController(new KeysRepository($authorizedDb()));
};

$router->get('#^/keys$#', static function () use ($makeKeysController): void {
    $makeKeysController()->index();
});

$router->get('#^/keys/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeKeysController): void {
    $makeKeysController()->show($params['id']);
});

$router->post('#^/keys$#', static function (Request $request) use ($makeKeysController): void {
    $makeKeysController()->store($request);
});

$router->patch('#^/keys/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeKeysController): void {
    $makeKeysController()->update($params['id'], $request);
});

$router->delete('#^/keys/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeKeysController): void {
    $makeKeysController()->destroy($params['id']);
});

$platformResourceConfigs = [
    'installations' => [
        'entity' => 'installation',
        'table' => 'installations',
        'primary_key' => 'installation_id',
        'json_fields' => ['installation_attributes', 'installation_modules', 'installation_summary'],
        'columns' => [
            'installation_id', 'installation_attributes', 'installation_experience', 'installation_modules',
            'installation_status', 'installation_started_at', 'installation_finished_at', 'installation_error',
            'installation_summary', 'created_by_user_id', 'event_id', 'process_id', 'access', 'status',
            'active', 'time_started', 'time_updated',
        ],
        'create' => [
            'installation_attributes' => ['type' => 'json_object', 'default' => []],
            'installation_experience' => ['type' => 'text', 'required' => true],
            'installation_modules' => ['type' => 'json_array', 'default' => []],
            'installation_status' => ['type' => 'text_lower', 'default' => 'pending'],
            'installation_started_at' => ['type' => 'timestamp', 'default' => 'now'],
            'installation_finished_at' => ['type' => 'timestamp', 'nullable' => true],
            'installation_error' => ['type' => 'text', 'nullable' => true],
            'installation_summary' => ['type' => 'json_object', 'default' => []],
            'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
            'event_id' => ['type' => 'text', 'default' => 'event_8301'],
            'process_id' => ['type' => 'text', 'default' => 'process_8301'],
            'access' => ['type' => 'text_lower', 'default' => 'private'],
            'status' => ['type' => 'text_lower', 'default' => 'active'],
            'active' => ['type' => 'active', 'default' => 1],
        ],
        'update' => [
            'installation_attributes' => ['type' => 'json_object'],
            'installation_experience' => ['type' => 'text', 'required' => true],
            'installation_modules' => ['type' => 'json_array'],
            'installation_status' => ['type' => 'text_lower', 'required' => true],
            'installation_started_at' => ['type' => 'timestamp'],
            'installation_finished_at' => ['type' => 'timestamp', 'nullable' => true],
            'installation_error' => ['type' => 'text', 'nullable' => true],
            'installation_summary' => ['type' => 'json_object'],
            'created_by_user_id' => ['type' => 'text'],
            'event_id' => ['type' => 'text'],
            'process_id' => ['type' => 'text'],
            'access' => ['type' => 'text_lower'],
            'status' => ['type' => 'text_lower'],
            'active' => ['type' => 'active'],
        ],
    ],
    'steps' => [
        'entity' => 'step',
        'table' => 'steps',
        'primary_key' => 'step_id',
        'json_fields' => ['step_attributes', 'step_summary'],
        'columns' => [
            'step_id', 'step_attributes', 'step_name', 'step_order', 'step_status', 'step_sql_hash',
            'step_started_at', 'step_finished_at', 'step_error', 'step_summary', 'created_by_user_id',
            'event_id', 'process_id', 'access', 'status', 'active', 'time_started', 'time_updated',
        ],
        'create' => [
            'step_attributes' => ['type' => 'json_object', 'default' => []],
            'step_name' => ['type' => 'text', 'required' => true],
            'step_order' => ['type' => 'int', 'default' => 0],
            'step_status' => ['type' => 'text_lower', 'default' => 'pending'],
            'step_sql_hash' => ['type' => 'text', 'nullable' => true],
            'step_started_at' => ['type' => 'timestamp', 'default' => 'now'],
            'step_finished_at' => ['type' => 'timestamp', 'nullable' => true],
            'step_error' => ['type' => 'text', 'nullable' => true],
            'step_summary' => ['type' => 'json_object', 'default' => []],
            'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
            'event_id' => ['type' => 'text', 'default' => 'event_8301'],
            'process_id' => ['type' => 'text', 'default' => 'process_8301'],
            'access' => ['type' => 'text_lower', 'default' => 'private'],
            'status' => ['type' => 'text_lower', 'default' => 'active'],
            'active' => ['type' => 'active', 'default' => 1],
        ],
        'update' => [
            'step_attributes' => ['type' => 'json_object'],
            'step_name' => ['type' => 'text', 'required' => true],
            'step_order' => ['type' => 'int'],
            'step_status' => ['type' => 'text_lower'],
            'step_sql_hash' => ['type' => 'text', 'nullable' => true],
            'step_started_at' => ['type' => 'timestamp'],
            'step_finished_at' => ['type' => 'timestamp', 'nullable' => true],
            'step_error' => ['type' => 'text', 'nullable' => true],
            'step_summary' => ['type' => 'json_object'],
            'created_by_user_id' => ['type' => 'text'],
            'event_id' => ['type' => 'text'],
            'process_id' => ['type' => 'text'],
            'access' => ['type' => 'text_lower'],
            'status' => ['type' => 'text_lower'],
            'active' => ['type' => 'active'],
        ],
    ],
    'windows' => [
        'entity' => 'window',
        'table' => 'windows',
        'primary_key' => 'window_id',
        'json_fields' => ['window_attributes'],
        'columns' => [
            'window_id', 'window_attributes', 'window_key_id', 'window_start', 'window_end', 'window_count',
            'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status',
            'active', 'time_started', 'time_updated',
        ],
        'create' => [
            'window_attributes' => ['type' => 'json_object', 'default' => []],
            'window_key_id' => ['type' => 'text', 'required' => true],
            'window_start' => ['type' => 'timestamp', 'required' => true],
            'window_end' => ['type' => 'timestamp', 'required' => true],
            'window_count' => ['type' => 'nonnegative_int', 'default' => 0],
            'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
            'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
            'event_id' => ['type' => 'text', 'default' => 'event_8301'],
            'process_id' => ['type' => 'text', 'default' => 'process_8301'],
            'access' => ['type' => 'text_lower', 'default' => 'private'],
            'status' => ['type' => 'text_lower', 'default' => 'active'],
            'active' => ['type' => 'active', 'default' => 1],
        ],
        'update' => [
            'window_attributes' => ['type' => 'json_object'],
            'window_key_id' => ['type' => 'text'],
            'window_start' => ['type' => 'timestamp'],
            'window_end' => ['type' => 'timestamp'],
            'window_count' => ['type' => 'nonnegative_int'],
            'created_by_user_id' => ['type' => 'text'],
            'created_for_app_id' => ['type' => 'text'],
            'event_id' => ['type' => 'text'],
            'process_id' => ['type' => 'text'],
            'access' => ['type' => 'text_lower'],
            'status' => ['type' => 'text_lower'],
            'active' => ['type' => 'active'],
        ],
    ],
];

$makePlatformResourceController = static function (string $name) use ($platformResourceConfigs, $authorizedDb): PlatformResourceController {
    $config = $platformResourceConfigs[$name];
    $repository = new PlatformResourceRepository(
        $authorizedDb(),
        $config['table'],
        $config['primary_key'],
        $config['columns'],
        $config['json_fields'] ?? []
    );

    return new PlatformResourceController($name, $config['entity'], $config['primary_key'], $repository, $config);
};

foreach (array_keys($platformResourceConfigs) as $resourceName) {
    $router->get('#^/' . $resourceName . '$#', static function () use ($makePlatformResourceController, $resourceName): void {
        $makePlatformResourceController($resourceName)->index();
    });

    $router->get('#^/' . $resourceName . '/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makePlatformResourceController, $resourceName): void {
        $makePlatformResourceController($resourceName)->show($params['id']);
    });

    $router->post('#^/' . $resourceName . '$#', static function (Request $request) use ($makePlatformResourceController, $resourceName): void {
        $makePlatformResourceController($resourceName)->store($request);
    });

    $router->patch('#^/' . $resourceName . '/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makePlatformResourceController, $resourceName): void {
        $makePlatformResourceController($resourceName)->update($params['id'], $request);
    });

    $router->delete('#^/' . $resourceName . '/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makePlatformResourceController, $resourceName): void {
        $makePlatformResourceController($resourceName)->destroy($params['id']);
    });
}

$makeSessionsController = static function () use ($authorizedDb): SessionsController {
    return new SessionsController(new SessionsRepository($authorizedDb()));
};

$router->get('#^/sessions$#', static function () use ($makeSessionsController): void {
    $makeSessionsController()->index();
});

$router->get('#^/sessions/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeSessionsController): void {
    $makeSessionsController()->show($params['id']);
});

$router->post('#^/sessions$#', static function (Request $request) use ($makeSessionsController): void {
    $makeSessionsController()->store($request);
});

$router->patch('#^/sessions/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeSessionsController): void {
    $makeSessionsController()->update($params['id'], $request);
});

$router->delete('#^/sessions/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeSessionsController): void {
    $makeSessionsController()->destroy($params['id']);
});

$makeCartridgesController = static function () use ($platformBasePath, $authorizedDb): CartridgesController {
    $authorizedDb();
    return new CartridgesController($platformBasePath);
};

$router->get('#^/cartridges$#', static function () use ($makeCartridgesController): void {
    $makeCartridgesController()->index();
});

$router->get('#^/cartridges/(?P<name>[^/]+)$#', static function (Request $request, array $params) use ($makeCartridgesController): void {
    $makeCartridgesController()->show($params['name']);
});
