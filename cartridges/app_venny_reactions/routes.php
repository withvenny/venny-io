<?php

declare(strict_types=1);

use VennyIO\Controllers\PlatformResourceController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\PlatformResourceRepository;
use VennyIO\Support\ApiKeyAuth;
use VennyIO\Support\Database;

/** @var Router $router */

$authorizedReactionsDb = static function (): PDO {
    $db = Database::connection();
    ApiKeyAuth::require($db);
    return $db;
};

$makeAcknowledgementsController = static function () use ($authorizedReactionsDb): PlatformResourceController {
    $columns = [
        'acknowledgement_id', 'acknowledgement_attributes', 'acknowledgement_object_id',
        'acknowledgement_parent_object_id', 'acknowledgement_type', 'created_by_user_id',
        'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active',
        'time_started', 'time_updated',
    ];

    $create = [
        'acknowledgement_attributes' => ['type' => 'json_object', 'default' => []],
        'acknowledgement_object_id' => ['type' => 'text', 'required' => true],
        'acknowledgement_parent_object_id' => ['type' => 'text', 'nullable' => true],
        'acknowledgement_type' => ['type' => 'text_lower', 'required' => true],
        'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
        'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
        'event_id' => ['type' => 'text', 'default' => 'event_8301'],
        'process_id' => ['type' => 'text', 'default' => 'process_8301'],
        'access' => ['type' => 'text_lower', 'default' => 'private'],
        'status' => ['type' => 'text_lower', 'default' => 'active'],
        'active' => ['type' => 'active', 'default' => 1],
    ];

    $repository = new PlatformResourceRepository(
        $authorizedReactionsDb(),
        'acknowledgements',
        'acknowledgement_id',
        $columns,
        ['acknowledgement_attributes']
    );

    return new PlatformResourceController('acknowledgements', 'acknowledgement', 'acknowledgement_id', $repository, [
        'columns' => $columns,
        'create' => $create,
        'update' => $create,
    ]);
};

$makeCommentsController = static function () use ($authorizedReactionsDb): PlatformResourceController {
    $columns = [
        'comment_id', 'comment_attributes', 'comment_object_id', 'comment_parent_object_id',
        'comment_body', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id',
        'access', 'status', 'active', 'time_started', 'time_updated',
    ];

    $create = [
        'comment_attributes' => ['type' => 'json_object', 'default' => []],
        'comment_object_id' => ['type' => 'text', 'required' => true],
        'comment_parent_object_id' => ['type' => 'text', 'nullable' => true],
        'comment_body' => ['type' => 'text', 'required' => true],
        'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
        'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
        'event_id' => ['type' => 'text', 'default' => 'event_8301'],
        'process_id' => ['type' => 'text', 'default' => 'process_8301'],
        'access' => ['type' => 'text_lower', 'default' => 'private'],
        'status' => ['type' => 'text_lower', 'default' => 'active'],
        'active' => ['type' => 'active', 'default' => 1],
    ];

    $repository = new PlatformResourceRepository(
        $authorizedReactionsDb(),
        'comments',
        'comment_id',
        $columns,
        ['comment_attributes']
    );

    return new PlatformResourceController('comments', 'comment', 'comment_id', $repository, [
        'columns' => $columns,
        'create' => $create,
        'update' => $create,
    ]);
};

$router->get('#^/acknowledgements$#', static function () use ($makeAcknowledgementsController): void {
    $makeAcknowledgementsController()->index();
});

$router->get('#^/acknowledgements/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeAcknowledgementsController): void {
    $makeAcknowledgementsController()->show($params['id']);
});

$router->post('#^/acknowledgements$#', static function (Request $request) use ($makeAcknowledgementsController): void {
    $makeAcknowledgementsController()->store($request);
});

$router->patch('#^/acknowledgements/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeAcknowledgementsController): void {
    $makeAcknowledgementsController()->update($params['id'], $request);
});

$router->delete('#^/acknowledgements/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeAcknowledgementsController): void {
    $makeAcknowledgementsController()->destroy($params['id']);
});

$router->get('#^/comments$#', static function () use ($makeCommentsController): void {
    $makeCommentsController()->index();
});

$router->get('#^/comments/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommentsController): void {
    $makeCommentsController()->show($params['id']);
});

$router->post('#^/comments$#', static function (Request $request) use ($makeCommentsController): void {
    $makeCommentsController()->store($request);
});

$router->patch('#^/comments/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommentsController): void {
    $makeCommentsController()->update($params['id'], $request);
});

$router->delete('#^/comments/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommentsController): void {
    $makeCommentsController()->destroy($params['id']);
});
