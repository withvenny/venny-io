<?php

declare(strict_types=1);

use VennyIO\Controllers\ChatController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\ChatRepository;
use VennyIO\Support\ChatAuth;
use VennyIO\Support\Database;

/** @var Router $router */

$makeChatController = static function (Request $request): ChatController {
    $db = Database::connection();
    $context = ChatAuth::require($db, $request);
    return new ChatController(new ChatRepository($db), $context);
};

// Nice-to-have facade endpoints for Chatio.
$router->get('#^/chat/threads$#', static function (Request $request) use ($makeChatController): void {
    $makeChatController($request)->listThreads($request);
});

$router->get('#^/chat/threads/(?P<id>[^/]+)/messages$#', static function (Request $request, array $params) use ($makeChatController): void {
    $makeChatController($request)->listMessagesForThread($params['id'], $request);
});

$router->post('#^/chat/threads/(?P<id>[^/]+)/messages$#', static function (Request $request, array $params) use ($makeChatController): void {
    $makeChatController($request)->createMessageInThread($params['id'], $request);
});

$router->post('#^/threads/find-or-create-direct$#', static function (Request $request) use ($makeChatController): void {
    $makeChatController($request)->findOrCreateDirectThread($request);
});

$router->patch('#^/messages/(?P<id>[^/]+)/read$#', static function (Request $request, array $params) use ($makeChatController): void {
    $makeChatController($request)->markMessageRead($params['id']);
});

// Direct resource endpoints, secured by app key + active user session.
$router->get('#^/threads$#', static function (Request $request) use ($makeChatController): void {
    $makeChatController($request)->listThreads($request);
});

$router->get('#^/threads/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeChatController): void {
    $makeChatController($request)->showThread($params['id']);
});

$router->post('#^/threads$#', static function (Request $request) use ($makeChatController): void {
    $makeChatController($request)->createThread($request);
});

$router->patch('#^/threads/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeChatController): void {
    $makeChatController($request)->updateThread($params['id'], $request);
});

$router->delete('#^/threads/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeChatController): void {
    $makeChatController($request)->destroyThread($params['id']);
});

$router->get('#^/messages$#', static function (Request $request) use ($makeChatController): void {
    $makeChatController($request)->listMessages($request);
});

$router->get('#^/messages/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeChatController): void {
    $makeChatController($request)->showMessage($params['id']);
});

$router->post('#^/messages$#', static function (Request $request) use ($makeChatController): void {
    $makeChatController($request)->createMessage($request);
});

$router->patch('#^/messages/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeChatController): void {
    $makeChatController($request)->updateMessage($params['id'], $request);
});

$router->delete('#^/messages/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeChatController): void {
    $makeChatController($request)->destroyMessage($params['id']);
});
