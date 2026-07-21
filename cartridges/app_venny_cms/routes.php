<?php

declare(strict_types=1);

use VennyIO\Controllers\ContentController;
use VennyIO\Controllers\PlatformResourceController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\PlatformResourceRepository;
use VennyIO\Support\ApiKeyAuth;
use VennyIO\Support\Database;

/** @var Router $router */

$authorizedCmsDb = static function (): PDO {
    $db = Database::connection();
    ApiKeyAuth::require($db);
    return $db;
};

$makeContentController = static function () use ($authorizedCmsDb): ContentController {
    $columns = [
        'content_id', 'content_attributes', 'content_startdate', 'content_enddate', 'content_slug',
        'content_title', 'content_description', 'content_body', 'content_tags', 'content_template',
        'content_visible', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id',
        'access', 'status', 'active', 'time_started', 'time_updated',
    ];

    $repository = new PlatformResourceRepository(
        $authorizedCmsDb(),
        'content',
        'content_id',
        $columns,
        ['content_attributes', 'content_tags']
    );

    return new ContentController($repository);
};

$makeAssetsController = static function () use ($authorizedCmsDb): PlatformResourceController {
    $columns = [
        'asset_id', 'asset_attributes', 'asset_object_id', 'asset_object_type', 'asset_originalfilename',
        'asset_displayname', 'asset_storageprovider', 'asset_bucket', 'asset_region', 'asset_appslug',
        'asset_key', 'asset_etag', 'asset_checksum_sha265', 'asset_mimetype', 'asset_extension',
        'asset_size_bytes', 'asset_category', 'asset_purpose', 'asset_visibility', 'asset_uploadstatus',
        'asset_processingstatus', 'asset_processingattempts', 'asset_processingstartedat', 'asset_processedat',
        'asset_processingerror', 'asset_uploadedat', 'asset_archivedat', 'asset_deletedat',
        'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active',
        'time_started', 'time_updated',
    ];

    $create = [
        'asset_attributes' => ['type' => 'json_object', 'default' => []],
        'asset_object_id' => ['type' => 'text', 'nullable' => true],
        'asset_object_type' => ['type' => 'text_lower', 'required' => true],
        'asset_originalfilename' => ['type' => 'text', 'required' => true],
        'asset_displayname' => ['type' => 'text', 'nullable' => true],
        'asset_storageprovider' => ['type' => 'text_lower', 'default' => 's3'],
        'asset_bucket' => ['type' => 'text', 'default' => 'io-venny-assets'],
        'asset_region' => ['type' => 'text_lower', 'default' => 'us-east-2'],
        'asset_appslug' => ['type' => 'text_lower', 'required' => true],
        'asset_key' => ['type' => 'text', 'required' => true],
        'asset_etag' => ['type' => 'text', 'required' => true],
        'asset_checksum_sha265' => ['type' => 'text', 'nullable' => true],
        'asset_mimetype' => ['type' => 'text_lower', 'default' => 'text/html'],
        'asset_extension' => ['type' => 'text_lower', 'nullable' => true],
        'asset_size_bytes' => ['type' => 'nonnegative_int', 'nullable' => true],
        'asset_category' => ['type' => 'text_lower', 'default' => 'other'],
        'asset_purpose' => ['type' => 'text_lower', 'nullable' => true],
        'asset_visibility' => ['type' => 'text_lower', 'default' => 'private'],
        'asset_uploadstatus' => ['type' => 'text_lower', 'default' => 'uploaded'],
        'asset_processingstatus' => ['type' => 'text_lower', 'nullable' => true],
        'asset_processingattempts' => ['type' => 'nonnegative_int', 'default' => 0],
        'asset_processingstartedat' => ['type' => 'timestamp', 'nullable' => true],
        'asset_processedat' => ['type' => 'timestamp', 'nullable' => true],
        'asset_processingerror' => ['type' => 'text', 'nullable' => true],
        'asset_uploadedat' => ['type' => 'timestamp', 'nullable' => true],
        'asset_archivedat' => ['type' => 'timestamp', 'nullable' => true],
        'asset_deletedat' => ['type' => 'timestamp', 'nullable' => true],
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
        $authorizedCmsDb(),
        'assets',
        'asset_id',
        $columns,
        ['asset_attributes']
    );

    return new PlatformResourceController('assets', 'asset', 'asset_id', $repository, $config);
};

$router->get('#^/content$#', static function () use ($makeContentController): void {
    $makeContentController()->index();
});

$router->get('#^/content/slug/(?P<slug>[^/]+)$#', static function (Request $request, array $params) use ($makeContentController): void {
    $makeContentController()->showBySlug($params['slug']);
});

$router->get('#^/content/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeContentController): void {
    $makeContentController()->show($params['id']);
});

$router->post('#^/content$#', static function (Request $request) use ($makeContentController): void {
    $makeContentController()->store($request);
});

$router->patch('#^/content/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeContentController): void {
    $makeContentController()->update($params['id'], $request);
});

$router->delete('#^/content/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeContentController): void {
    $makeContentController()->destroy($params['id']);
});

$router->get('#^/assets$#', static function () use ($makeAssetsController): void {
    $makeAssetsController()->index();
});

$router->get('#^/assets/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeAssetsController): void {
    $makeAssetsController()->show($params['id']);
});

$router->post('#^/assets$#', static function (Request $request) use ($makeAssetsController): void {
    $makeAssetsController()->store($request);
});

$router->patch('#^/assets/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeAssetsController): void {
    $makeAssetsController()->update($params['id'], $request);
});

$router->delete('#^/assets/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeAssetsController): void {
    $makeAssetsController()->destroy($params['id']);
});
