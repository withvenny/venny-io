<?php

declare(strict_types=1);

use VennyIO\Controllers\PlatformResourceController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\PlatformResourceRepository;
use VennyIO\Support\ApiKeyAuth;
use VennyIO\Support\Database;

/** @var Router $router */

$authorizedPostsDb = static function (): PDO {
    $db = Database::connection();
    ApiKeyAuth::require($db);
    return $db;
};

$makePostsController = static function () use ($authorizedPostsDb): PlatformResourceController {
    $columns = [
        'post_id', 'post_attributes', 'post_object_id', 'post_parent_object_id', 'post_body',
        'post_images', 'post_closed', 'post_deleted', 'created_by_user_id', 'created_for_app_id',
        'event_id', 'process_id', 'access', 'status', 'active', 'time_started', 'time_updated',
    ];

    $create = [
        'post_attributes' => ['type' => 'json_object', 'default' => []],
        'post_object_id' => ['type' => 'text', 'required' => true],
        'post_parent_object_id' => ['type' => 'text', 'nullable' => true],
        'post_body' => ['type' => 'text', 'required' => true],
        'post_images' => ['type' => 'json_array', 'nullable' => true, 'default' => []],
        'post_closed' => ['type' => 'boolean', 'default' => false],
        'post_deleted' => ['type' => 'boolean', 'default' => false],
        'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
        'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
        'event_id' => ['type' => 'text', 'default' => 'event_8301'],
        'process_id' => ['type' => 'text', 'default' => 'process_8301'],
        'access' => ['type' => 'text_lower', 'default' => 'private'],
        'status' => ['type' => 'text_lower', 'default' => 'active'],
        'active' => ['type' => 'active', 'default' => 1],
    ];

    $config = [
        'columns' => $columns,
        'create' => $create,
        'update' => $create,
    ];

    $repository = new PlatformResourceRepository(
        $authorizedPostsDb(),
        'posts',
        'post_id',
        $columns,
        ['post_attributes', 'post_images']
    );

    return new PlatformResourceController('posts', 'post', 'post_id', $repository, $config);
};

$router->get('#^/posts$#', static function () use ($makePostsController): void {
    $makePostsController()->index();
});

$router->get('#^/posts/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makePostsController): void {
    $makePostsController()->show($params['id']);
});

$router->post('#^/posts$#', static function (Request $request) use ($makePostsController): void {
    $makePostsController()->store($request);
});

$router->patch('#^/posts/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makePostsController): void {
    $makePostsController()->update($params['id'], $request);
});

$router->delete('#^/posts/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makePostsController): void {
    $makePostsController()->destroy($params['id']);
});
