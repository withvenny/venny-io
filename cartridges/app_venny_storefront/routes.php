<?php

declare(strict_types=1);

use VennyIO\Controllers\PlatformResourceController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\PlatformResourceRepository;
use VennyIO\Support\ApiKeyAuth;
use VennyIO\Support\Database;

/** @var Router $router */

$authorizedStorefrontDb = static function (): PDO {
    $db = Database::connection();
    ApiKeyAuth::require($db);
    return $db;
};

$makeStorefrontController = static function (string $resource) use ($authorizedStorefrontDb): PlatformResourceController {
    $resources = json_decode('{"catalogs": {"entity": "catalog", "primaryKey": "catalog_id", "columns": ["catalog_id", "catalog_attributes", "catalog_online", "catalog_public", "catalog_name", "catalog_description", "catalog_slug", "catalog_images", "created_by_user_id", "created_for_app_id", "event_id", "process_id", "access", "status", "active", "time_started", "time_updated"], "jsonFields": ["catalog_attributes", "catalog_images"], "create": {"catalog_attributes": {"type": "json_object", "default": {}}, "catalog_online": {"type": "boolean", "default": true}, "catalog_public": {"type": "boolean", "default": false}, "catalog_name": {"type": "text", "required": true}, "catalog_description": {"type": "text", "required": true}, "catalog_slug": {"type": "text_lower", "required": true}, "catalog_images": {"type": "json_array", "nullable": true, "default": []}, "created_by_user_id": {"type": "text", "default": "user_8301"}, "created_for_app_id": {"type": "text", "default": "app_8301"}, "event_id": {"type": "text", "default": "event_8301"}, "process_id": {"type": "text", "default": "process_8301"}, "access": {"type": "text_lower", "default": "private"}, "status": {"type": "text_lower", "default": "active"}, "active": {"type": "active", "default": 1}}}, "categories": {"entity": "category", "primaryKey": "category_id", "columns": ["category_id", "category_attributes", "category_catalog_id", "category_online", "category_public", "category_name", "category_description", "category_slug", "category_images", "created_by_user_id", "created_for_app_id", "event_id", "process_id", "access", "status", "active", "time_started", "time_updated"], "jsonFields": ["category_attributes", "category_images"], "create": {"category_attributes": {"type": "json_object", "default": {}}, "category_catalog_id": {"type": "text", "required": true}, "category_online": {"type": "boolean", "default": true}, "category_public": {"type": "boolean", "default": false}, "category_name": {"type": "text", "required": true}, "category_description": {"type": "text", "required": true}, "category_slug": {"type": "text_lower", "required": true}, "category_images": {"type": "json_array", "nullable": true, "default": []}, "created_by_user_id": {"type": "text", "default": "user_8301"}, "created_for_app_id": {"type": "text", "default": "app_8301"}, "event_id": {"type": "text", "default": "event_8301"}, "process_id": {"type": "text", "default": "process_8301"}, "access": {"type": "text_lower", "default": "private"}, "status": {"type": "text_lower", "default": "active"}, "active": {"type": "active", "default": 1}}}, "products": {"entity": "product", "primaryKey": "product_id", "columns": ["product_id", "product_attributes", "product_catalog_id", "product_category_id", "product_online", "product_public", "product_name", "product_description", "product_slug", "product_images", "product_sku", "product_base_price", "product_inventory", "product_manufacturer", "product_weight", "created_by_user_id", "created_for_app_id", "event_id", "process_id", "access", "status", "active", "time_started", "time_updated"], "jsonFields": ["product_attributes", "product_images"], "create": {"product_attributes": {"type": "json_object", "default": {}}, "product_catalog_id": {"type": "text", "required": true}, "product_category_id": {"type": "text", "required": true}, "product_online": {"type": "boolean", "default": true}, "product_public": {"type": "boolean", "default": false}, "product_name": {"type": "text", "required": true}, "product_description": {"type": "text", "required": true}, "product_slug": {"type": "text_lower", "required": true}, "product_images": {"type": "json_array", "nullable": true, "default": []}, "product_sku": {"type": "text", "required": true}, "product_base_price": {"type": "text", "required": true}, "product_inventory": {"type": "nonnegative_int", "default": 0}, "product_manufacturer": {"type": "text", "nullable": true}, "product_weight": {"type": "nonnegative_int", "nullable": true}, "created_by_user_id": {"type": "text", "default": "user_8301"}, "created_for_app_id": {"type": "text", "default": "app_8301"}, "event_id": {"type": "text", "default": "event_8301"}, "process_id": {"type": "text", "default": "process_8301"}, "access": {"type": "text_lower", "default": "private"}, "status": {"type": "text_lower", "default": "active"}, "active": {"type": "active", "default": 1}}}, "items": {"entity": "item", "primaryKey": "item_id", "columns": ["item_id", "item_attributes", "item_catalog_id", "item_category_id", "item_product_id", "item_serial_number", "item_quantity", "item_sale_price", "item_size", "item_color", "created_by_user_id", "created_for_app_id", "event_id", "process_id", "access", "status", "active", "time_started", "time_updated"], "jsonFields": ["item_attributes"], "create": {"item_attributes": {"type": "json_object", "default": {}}, "item_catalog_id": {"type": "text", "required": true}, "item_category_id": {"type": "text", "required": true}, "item_product_id": {"type": "text", "required": true}, "item_serial_number": {"type": "text", "nullable": true}, "item_quantity": {"type": "nonnegative_int", "nullable": true}, "item_sale_price": {"type": "text", "nullable": true}, "item_size": {"type": "nonnegative_int", "nullable": true}, "item_color": {"type": "text", "nullable": true}, "created_by_user_id": {"type": "text", "default": "user_8301"}, "created_for_app_id": {"type": "text", "default": "app_8301"}, "event_id": {"type": "text", "default": "event_8301"}, "process_id": {"type": "text", "default": "process_8301"}, "access": {"type": "text_lower", "default": "private"}, "status": {"type": "text_lower", "default": "active"}, "active": {"type": "active", "default": 1}}}}', true, 512, JSON_THROW_ON_ERROR);

    if (!isset($resources[$resource])) {
        throw new InvalidArgumentException('Unknown storefront resource: ' . $resource);
    }

    $definition = $resources[$resource];
    $repository = new PlatformResourceRepository(
        $authorizedStorefrontDb(),
        $resource,
        $definition['primaryKey'],
        $definition['columns'],
        $definition['jsonFields']
    );

    $config = [
        'columns' => $definition['columns'],
        'create' => $definition['create'],
        'update' => $definition['create'],
    ];

    return new PlatformResourceController($resource, $definition['entity'], $definition['primaryKey'], $repository, $config);
};

$router->get('#^/catalogs$#', static function () use ($makeStorefrontController): void {
    $makeStorefrontController('catalogs')->index();
});

$router->get('#^/catalogs/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeStorefrontController): void {
    $makeStorefrontController('catalogs')->show($params['id']);
});

$router->post('#^/catalogs$#', static function (Request $request) use ($makeStorefrontController): void {
    $makeStorefrontController('catalogs')->store($request);
});

$router->patch('#^/catalogs/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeStorefrontController): void {
    $makeStorefrontController('catalogs')->update($params['id'], $request);
});

$router->delete('#^/catalogs/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeStorefrontController): void {
    $makeStorefrontController('catalogs')->destroy($params['id']);
});

$router->get('#^/categories$#', static function () use ($makeStorefrontController): void {
    $makeStorefrontController('categories')->index();
});

$router->get('#^/categories/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeStorefrontController): void {
    $makeStorefrontController('categories')->show($params['id']);
});

$router->post('#^/categories$#', static function (Request $request) use ($makeStorefrontController): void {
    $makeStorefrontController('categories')->store($request);
});

$router->patch('#^/categories/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeStorefrontController): void {
    $makeStorefrontController('categories')->update($params['id'], $request);
});

$router->delete('#^/categories/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeStorefrontController): void {
    $makeStorefrontController('categories')->destroy($params['id']);
});

$router->get('#^/products$#', static function () use ($makeStorefrontController): void {
    $makeStorefrontController('products')->index();
});

$router->get('#^/products/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeStorefrontController): void {
    $makeStorefrontController('products')->show($params['id']);
});

$router->post('#^/products$#', static function (Request $request) use ($makeStorefrontController): void {
    $makeStorefrontController('products')->store($request);
});

$router->patch('#^/products/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeStorefrontController): void {
    $makeStorefrontController('products')->update($params['id'], $request);
});

$router->delete('#^/products/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeStorefrontController): void {
    $makeStorefrontController('products')->destroy($params['id']);
});

$router->get('#^/items$#', static function () use ($makeStorefrontController): void {
    $makeStorefrontController('items')->index();
});

$router->get('#^/items/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeStorefrontController): void {
    $makeStorefrontController('items')->show($params['id']);
});

$router->post('#^/items$#', static function (Request $request) use ($makeStorefrontController): void {
    $makeStorefrontController('items')->store($request);
});

$router->patch('#^/items/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeStorefrontController): void {
    $makeStorefrontController('items')->update($params['id'], $request);
});

$router->delete('#^/items/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeStorefrontController): void {
    $makeStorefrontController('items')->destroy($params['id']);
});
