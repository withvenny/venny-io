<?php

declare(strict_types=1);

use VennyIO\Controllers\AuthenticationController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\AuthenticationRepository;
use VennyIO\Support\ApiKeyAuth;
use VennyIO\Support\Database;

/** @var Router $router */

$makeAuthenticationController = static function (): AuthenticationController {
    $db = Database::connection();
    $appContext = ApiKeyAuth::require($db);

    return new AuthenticationController(
        new AuthenticationRepository($db),
        $appContext
    );
};

$router->post('#^/sign-up$#', static function (Request $request) use ($makeAuthenticationController): void {
    $makeAuthenticationController()->signUp($request);
});

$router->post('#^/sign-in$#', static function (Request $request) use ($makeAuthenticationController): void {
    $makeAuthenticationController()->signIn($request);
});

$router->post('#^/sign-out$#', static function (Request $request) use ($makeAuthenticationController): void {
    $makeAuthenticationController()->signOut($request);
});

$router->post('#^/request-password$#', static function (Request $request) use ($makeAuthenticationController): void {
    $makeAuthenticationController()->requestPassword($request);
});

$router->post('#^/reset-password$#', static function (Request $request) use ($makeAuthenticationController): void {
    $makeAuthenticationController()->resetPassword($request);
});
