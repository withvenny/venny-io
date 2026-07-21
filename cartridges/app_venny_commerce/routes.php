<?php

declare(strict_types=1);

use VennyIO\Controllers\PlatformResourceController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\PlatformResourceRepository;
use VennyIO\Support\ApiKeyAuth;
use VennyIO\Support\Database;

/** @var Router $router */

$authorizedCommerceDb = static function (): PDO {
    $db = Database::connection();
    ApiKeyAuth::require($db);
    return $db;
};

$makeCommerceController = static function (string $resource) use ($authorizedCommerceDb): PlatformResourceController {
    $resources = json_decode('{"transactions": {"entity": "transaction", "pk": "transaction_id", "json": ["transaction_attributes"], "cols": ["transaction_id", "transaction_attributes", "transaction_email", "transaction_firstname", "transaction_middlename", "transaction_lastname", "transaction_phone", "transaction_address1", "transaction_address2", "transaction_city", "transaction_state", "transaction_zip", "transaction_country", "transaction_currency", "transaction_subtotal", "transaction_discount", "transaction_tax", "transaction_total", "transaction_attemptcount", "transaction_stripecustomerid", "transaction_stripecheckoutsessionid", "transaction_stripepaymentintentid", "transaction_stripechargeid", "transaction_cardbrand", "transaction_cardfunding", "transaction_cardlast4", "transaction_cardcountry", "transaction_paidat", "transaction_failedat", "transaction_cancelledat", "transaction_refundedat", "created_by_user_id", "created_for_app_id", "event_id", "process_id", "access", "status", "active", "time_started", "time_updated"], "create": {"transaction_attributes": {"type": "json_object", "default": {}}, "transaction_email": {"type": "text", "nullable": true}, "transaction_firstname": {"type": "text", "nullable": true}, "transaction_middlename": {"type": "text", "nullable": true}, "transaction_lastname": {"type": "text", "nullable": true}, "transaction_phone": {"type": "text", "nullable": true}, "transaction_address1": {"type": "text", "nullable": true}, "transaction_address2": {"type": "text", "nullable": true}, "transaction_city": {"type": "text", "nullable": true}, "transaction_state": {"type": "text", "nullable": true}, "transaction_zip": {"type": "text", "nullable": true}, "transaction_country": {"type": "text", "default": "United States of America"}, "transaction_currency": {"type": "text_lower", "default": "usd"}, "transaction_subtotal": {"type": "text", "default": "0"}, "transaction_discount": {"type": "text", "default": "0"}, "transaction_tax": {"type": "text", "default": "0"}, "transaction_total": {"type": "text", "default": "0"}, "transaction_attemptcount": {"type": "nonnegative_int", "default": 0}, "transaction_stripecustomerid": {"type": "text", "nullable": true}, "transaction_stripecheckoutsessionid": {"type": "text", "nullable": true}, "transaction_stripepaymentintentid": {"type": "text", "nullable": true}, "transaction_stripechargeid": {"type": "text", "nullable": true}, "transaction_cardbrand": {"type": "text", "nullable": true}, "transaction_cardfunding": {"type": "text", "nullable": true}, "transaction_cardlast4": {"type": "text", "nullable": true}, "transaction_cardcountry": {"type": "text", "nullable": true}, "transaction_paidat": {"type": "timestamp", "nullable": true}, "transaction_failedat": {"type": "timestamp", "nullable": true}, "transaction_cancelledat": {"type": "timestamp", "nullable": true}, "transaction_refundedat": {"type": "timestamp", "nullable": true}, "created_by_user_id": {"type": "text", "default": "user_8301"}, "created_for_app_id": {"type": "text", "default": "app_8301"}, "event_id": {"type": "text", "default": "event_8301"}, "process_id": {"type": "text", "default": "process_8301"}, "access": {"type": "text_lower", "default": "private"}, "status": {"type": "text_lower", "default": "active"}, "active": {"type": "active", "default": 1}}}, "orders": {"entity": "order", "pk": "order_id", "json": ["order_attributes"], "cols": ["order_id", "order_attributes", "order_customer_id", "order_totalproduct", "order_totaltax", "order_totalshipping", "order_totaltaxshipping", "order_totaladjustment", "order_description", "order_currency", "order_locked", "order_address", "created_by_user_id", "created_for_app_id", "event_id", "process_id", "access", "status", "active", "time_started", "time_updated"], "create": {"order_attributes": {"type": "json_object", "default": {}}, "order_customer_id": {"type": "text", "nullable": true}, "order_totalproduct": {"type": "text", "required": true}, "order_totaltax": {"type": "text", "required": true}, "order_totalshipping": {"type": "text", "required": true}, "order_totaltaxshipping": {"type": "text", "required": true}, "order_totaladjustment": {"type": "text", "nullable": true}, "order_description": {"type": "text", "nullable": true}, "order_currency": {"type": "text_lower", "default": "usd"}, "order_locked": {"type": "boolean", "default": false}, "order_address": {"type": "text", "nullable": true}, "created_by_user_id": {"type": "text", "default": "user_8301"}, "created_for_app_id": {"type": "text", "default": "app_8301"}, "event_id": {"type": "text", "default": "event_8301"}, "process_id": {"type": "text", "default": "process_8301"}, "access": {"type": "text_lower", "default": "private"}, "status": {"type": "text_lower", "default": "active"}, "active": {"type": "active", "default": 1}}}, "coupons": {"entity": "coupon", "pk": "coupon_id", "json": ["coupon_attributes"], "cols": ["coupon_id", "coupon_attributes", "coupon_code", "coupon_description", "coupon_discounttype", "coupon_percent", "coupon_amount", "coupon_currency", "coupon_minimumamount", "coupon_maximumamount", "coupon_startsat", "coupon_expiresat", "coupon_maximumredemptions", "coupon_redemptions", "coupon_subtotal", "coupon_discount", "coupon_tax", "coupon_total", "coupon_attemptcount", "coupon_stripecustomerid", "coupon_stripecheckoutsessionid", "coupon_stripepaymentintentid", "coupon_stripechargeid", "coupon_cardbrand", "coupon_cardfunding", "coupon_cardlast4", "coupon_cardcountry", "coupon_paidat", "coupon_failedat", "coupon_cancelledat", "coupon_refundedat", "created_by_user_id", "created_for_app_id", "event_id", "process_id", "access", "status", "active", "time_started", "time_updated"], "create": {"coupon_attributes": {"type": "json_object", "default": {}}, "coupon_code": {"type": "text", "required": true}, "coupon_description": {"type": "text", "default": ""}, "coupon_discounttype": {"type": "text_lower", "required": true}, "coupon_percent": {"type": "text", "nullable": true}, "coupon_amount": {"type": "text", "nullable": true}, "coupon_currency": {"type": "text_lower", "default": "usd"}, "coupon_minimumamount": {"type": "text", "nullable": true}, "coupon_maximumamount": {"type": "text", "nullable": true}, "coupon_startsat": {"type": "timestamp", "nullable": true}, "coupon_expiresat": {"type": "timestamp", "nullable": true}, "coupon_maximumredemptions": {"type": "nonnegative_int", "nullable": true}, "coupon_redemptions": {"type": "nonnegative_int", "default": 0}, "coupon_subtotal": {"type": "text", "default": "0"}, "coupon_discount": {"type": "text", "default": "0"}, "coupon_tax": {"type": "text", "default": "0"}, "coupon_total": {"type": "text", "default": "0"}, "coupon_attemptcount": {"type": "nonnegative_int", "default": 0}, "coupon_stripecustomerid": {"type": "text", "nullable": true}, "coupon_stripecheckoutsessionid": {"type": "text", "nullable": true}, "coupon_stripepaymentintentid": {"type": "text", "nullable": true}, "coupon_stripechargeid": {"type": "text", "nullable": true}, "coupon_cardbrand": {"type": "text", "nullable": true}, "coupon_cardfunding": {"type": "text", "nullable": true}, "coupon_cardlast4": {"type": "text", "nullable": true}, "coupon_cardcountry": {"type": "text", "nullable": true}, "coupon_paidat": {"type": "timestamp", "nullable": true}, "coupon_failedat": {"type": "timestamp", "nullable": true}, "coupon_cancelledat": {"type": "timestamp", "nullable": true}, "coupon_refundedat": {"type": "timestamp", "nullable": true}, "created_by_user_id": {"type": "text", "default": "user_8301"}, "created_for_app_id": {"type": "text", "default": "app_8301"}, "event_id": {"type": "text", "default": "event_8301"}, "process_id": {"type": "text", "default": "process_8301"}, "access": {"type": "text_lower", "default": "private"}, "status": {"type": "text_lower", "default": "active"}, "active": {"type": "active", "default": 1}}}, "customers": {"entity": "customer", "pk": "customer_id", "json": ["customer_attributes"], "cols": ["customer_id", "customer_attributes", "customer_firstname", "customer_middlename", "customer_lastname", "customer_telephone", "customer_initialcontact", "customer_email", "created_by_user_id", "created_for_app_id", "event_id", "process_id", "access", "status", "active", "time_started", "time_updated"], "create": {"customer_attributes": {"type": "json_object", "default": {}}, "customer_firstname": {"type": "text", "required": true}, "customer_middlename": {"type": "text", "nullable": true}, "customer_lastname": {"type": "text", "required": true}, "customer_telephone": {"type": "text", "nullable": true}, "customer_initialcontact": {"type": "timestamp", "nullable": true}, "customer_email": {"type": "text", "nullable": true}, "created_by_user_id": {"type": "text", "default": "user_8301"}, "created_for_app_id": {"type": "text", "default": "app_8301"}, "event_id": {"type": "text", "default": "event_8301"}, "process_id": {"type": "text", "default": "process_8301"}, "access": {"type": "text_lower", "default": "private"}, "status": {"type": "text_lower", "default": "active"}, "active": {"type": "active", "default": 1}}}}', true, 512, JSON_THROW_ON_ERROR);

    if (!isset($resources[$resource])) {
        throw new InvalidArgumentException('Unknown commerce resource: ' . $resource);
    }

    $definition = $resources[$resource];
    $repository = new PlatformResourceRepository(
        $authorizedCommerceDb(),
        $resource,
        $definition['pk'],
        $definition['cols'],
        $definition['json']
    );

    $config = [
        'columns' => $definition['cols'],
        'create' => $definition['create'],
        'update' => $definition['create'],
    ];

    return new PlatformResourceController($resource, $definition['entity'], $definition['pk'], $repository, $config);
};

$router->get('#^/transactions$#', static function () use ($makeCommerceController): void {
    $makeCommerceController('transactions')->index();
});

$router->get('#^/transactions/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommerceController): void {
    $makeCommerceController('transactions')->show($params['id']);
});

$router->post('#^/transactions$#', static function (Request $request) use ($makeCommerceController): void {
    $makeCommerceController('transactions')->store($request);
});

$router->patch('#^/transactions/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommerceController): void {
    $makeCommerceController('transactions')->update($params['id'], $request);
});

$router->delete('#^/transactions/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommerceController): void {
    $makeCommerceController('transactions')->destroy($params['id']);
});

$router->get('#^/orders$#', static function () use ($makeCommerceController): void {
    $makeCommerceController('orders')->index();
});

$router->get('#^/orders/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommerceController): void {
    $makeCommerceController('orders')->show($params['id']);
});

$router->post('#^/orders$#', static function (Request $request) use ($makeCommerceController): void {
    $makeCommerceController('orders')->store($request);
});

$router->patch('#^/orders/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommerceController): void {
    $makeCommerceController('orders')->update($params['id'], $request);
});

$router->delete('#^/orders/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommerceController): void {
    $makeCommerceController('orders')->destroy($params['id']);
});

$router->get('#^/coupons$#', static function () use ($makeCommerceController): void {
    $makeCommerceController('coupons')->index();
});

$router->get('#^/coupons/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommerceController): void {
    $makeCommerceController('coupons')->show($params['id']);
});

$router->post('#^/coupons$#', static function (Request $request) use ($makeCommerceController): void {
    $makeCommerceController('coupons')->store($request);
});

$router->patch('#^/coupons/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommerceController): void {
    $makeCommerceController('coupons')->update($params['id'], $request);
});

$router->delete('#^/coupons/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommerceController): void {
    $makeCommerceController('coupons')->destroy($params['id']);
});

$router->get('#^/customers$#', static function () use ($makeCommerceController): void {
    $makeCommerceController('customers')->index();
});

$router->get('#^/customers/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommerceController): void {
    $makeCommerceController('customers')->show($params['id']);
});

$router->post('#^/customers$#', static function (Request $request) use ($makeCommerceController): void {
    $makeCommerceController('customers')->store($request);
});

$router->patch('#^/customers/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommerceController): void {
    $makeCommerceController('customers')->update($params['id'], $request);
});

$router->delete('#^/customers/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommerceController): void {
    $makeCommerceController('customers')->destroy($params['id']);
});

