<?php

declare(strict_types=1);

use VennyIO\Controllers\AccountController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\AccountRepository;
use VennyIO\Support\ApiKeyAuth;
use VennyIO\Support\Database;
use VennyIO\Support\SessionAuth;

/** @var Router $router */

$makeAccountController = static function (): AccountController {
    $db = Database::connection();
    $appContext = ApiKeyAuth::require($db);
    $sessionContext = SessionAuth::require($db, $appContext);

    return new AccountController(
        new AccountRepository($db),
        $appContext,
        $sessionContext
    );
};

$router->get('#^/account$#', static function (Request $request) use ($makeAccountController): void {
    $makeAccountController()->show($request);
});

$router->patch('#^/account$#', static function (Request $request) use ($makeAccountController): void {
    $makeAccountController()->update($request);
});

$router->patch('#^/account/password$#', static function (Request $request) use ($makeAccountController): void {
    $makeAccountController()->updatePassword($request);
});

$router->post('#^/account/sign-out$#', static function (Request $request) use ($makeAccountController): void {
    $makeAccountController()->signOut($request);
});
