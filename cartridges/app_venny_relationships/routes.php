<?php

declare(strict_types=1);

use VennyIO\Controllers\PlatformResourceController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\PlatformResourceRepository;
use VennyIO\Support\ApiKeyAuth;
use VennyIO\Support\Database;

/** @var Router $router */

$authorizedRelationshipsDb = static function (): PDO {
    $db = Database::connection();
    ApiKeyAuth::require($db);
    return $db;
};

$makeFollowshipsController = static function () use ($authorizedRelationshipsDb): PlatformResourceController {
    $columns = [
        'followship_id', 'followship_attributes', 'followship_sender_id', 'followship_recipient_id',
        'followship_status', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id',
        'access', 'status', 'active', 'time_started', 'time_updated',
    ];

    $create = [
        'followship_attributes' => ['type' => 'json_object', 'default' => []],
        'followship_sender_id' => ['type' => 'text', 'required' => true],
        'followship_recipient_id' => ['type' => 'text', 'required' => true],
        'followship_status' => ['type' => 'text_lower', 'default' => 'requested'],
        'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
        'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
        'event_id' => ['type' => 'text', 'default' => 'event_8301'],
        'process_id' => ['type' => 'text', 'default' => 'process_8301'],
        'access' => ['type' => 'text_lower', 'default' => 'private'],
        'status' => ['type' => 'text_lower', 'default' => 'active'],
        'active' => ['type' => 'active', 'default' => 1],
    ];

    $repository = new PlatformResourceRepository(
        $authorizedRelationshipsDb(),
        'followships',
        'followship_id',
        $columns,
        ['followship_attributes']
    );

    return new PlatformResourceController('followships', 'followship', 'followship_id', $repository, [
        'columns' => $columns,
        'create' => $create,
        'update' => $create,
    ]);
};

$makeGroupsController = static function () use ($authorizedRelationshipsDb): PlatformResourceController {
    $columns = [
        'group_id', 'group_attributes', 'group_sender_id', 'group_recipient_id', 'group_title',
        'group_headline', 'group_access', 'group_participants', 'group_images', 'created_by_user_id',
        'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active',
        'time_started', 'time_updated',
    ];

    $create = [
        'group_attributes' => ['type' => 'json_object', 'default' => []],
        'group_sender_id' => ['type' => 'text', 'required' => true],
        'group_recipient_id' => ['type' => 'text', 'required' => true],
        'group_title' => ['type' => 'text', 'required' => true],
        'group_headline' => ['type' => 'text', 'nullable' => true],
        'group_access' => ['type' => 'text_lower', 'default' => 'private'],
        'group_participants' => ['type' => 'json_object', 'default' => []],
        'group_images' => ['type' => 'json_object', 'nullable' => true],
        'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
        'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
        'event_id' => ['type' => 'text', 'default' => 'event_8301'],
        'process_id' => ['type' => 'text', 'default' => 'process_8301'],
        'access' => ['type' => 'text_lower', 'default' => 'private'],
        'status' => ['type' => 'text_lower', 'default' => 'active'],
        'active' => ['type' => 'active', 'default' => 1],
    ];

    $repository = new PlatformResourceRepository(
        $authorizedRelationshipsDb(),
        'groups',
        'group_id',
        $columns,
        ['group_attributes', 'group_participants', 'group_images']
    );

    return new PlatformResourceController('groups', 'group', 'group_id', $repository, [
        'columns' => $columns,
        'create' => $create,
        'update' => $create,
    ]);
};

$router->get('#^/followships$#', static function (Request $request) use ($makeFollowshipsController): void {
    $makeFollowshipsController()->index($request);
});

$router->get('#^/followships/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeFollowshipsController): void {
    $makeFollowshipsController()->show($params['id']);
});

$router->post('#^/followships$#', static function (Request $request) use ($makeFollowshipsController): void {
    $makeFollowshipsController()->store($request);
});

$router->patch('#^/followships/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeFollowshipsController): void {
    $makeFollowshipsController()->update($params['id'], $request);
});

$router->delete('#^/followships/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeFollowshipsController): void {
    $makeFollowshipsController()->destroy($params['id']);
});

$router->get('#^/groups$#', static function (Request $request) use ($makeGroupsController): void {
    $makeGroupsController()->index($request);
});

$router->get('#^/groups/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeGroupsController): void {
    $makeGroupsController()->show($params['id']);
});

$router->post('#^/groups$#', static function (Request $request) use ($makeGroupsController): void {
    $makeGroupsController()->store($request);
});

$router->patch('#^/groups/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeGroupsController): void {
    $makeGroupsController()->update($params['id'], $request);
});

$router->delete('#^/groups/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeGroupsController): void {
    $makeGroupsController()->destroy($params['id']);
});
