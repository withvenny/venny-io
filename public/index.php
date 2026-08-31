<?php

declare(strict_types=1);

use VennyIO\Kernel\CartridgeLoader;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Support\Response;

require dirname(__DIR__) . '/config/bootstrap.php';

// Browser CORS must run before Request capture and before router dispatch.
// Without this, OPTIONS /sign-in falls through to the router and returns 404.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$defaultAllowedOrigins = [
    'https://www.withvenny.app',
    'https://withvenny.app',
    'https://app-withvenny-www.herokuapp.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
];

$configuredOrigins = getenv('CORS_ALLOWED_ORIGINS') ?: '';
$configuredAllowedOrigins = $configuredOrigins !== ''
    ? array_values(array_filter(array_map('trim', explode(',', $configuredOrigins))))
    : [];

// Keep the WithVenny browser origins available even when CORS_ALLOWED_ORIGINS
// is set in Heroku. This prevents a custom env var from accidentally removing
// the production frontend and causing browser preflight to return 204 without
// Access-Control-Allow-Origin.
$allowedOrigins = array_values(array_unique(array_merge($defaultAllowedOrigins, $configuredAllowedOrigins)));

if (is_string($origin) && $origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: false');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, X-Venny-Session-Id, X-Setup-Token, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
try {
    $request = Request::capture();
    $router = new Router();

    // The filesystem is the cartridge registry. Every valid directory under
    // /cartridges is discovered and loaded in dependency-safe order.
    (new CartridgeLoader(dirname(__DIR__)))->load($router);
    $router->dispatch($request);
} catch (Throwable $throwable) {
    error_log($throwable->getMessage());
    Response::json(500, false, 'internal server error', []);
}
