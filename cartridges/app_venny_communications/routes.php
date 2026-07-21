<?php

declare(strict_types=1);

use VennyIO\Controllers\PlatformResourceController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\PlatformResourceRepository;
use VennyIO\Support\ApiKeyAuth;
use VennyIO\Support\Database;

/** @var Router $router */

$authorizedCommunicationsDb = static function (): PDO {
    $db = Database::connection();
    ApiKeyAuth::require($db);
    return $db;
};

$communicationsResourceConfigs = [
    'communications' => [
        'table' => 'communications',
        'entity' => 'communication',
        'primary_key' => 'communication_id',
        'columns' => [
            'communication_id', 'communication_attributes', 'communication_object_id', 'communication_parentobject',
            'communication_template', 'communication_initiatedby', 'communication_recipients', 'communication_processed',
            'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active',
            'time_started', 'time_updated',
        ],
        'json_fields' => ['communication_attributes', 'communication_recipients'],
        'create' => [
            'communication_attributes' => ['type' => 'json_object', 'default' => []],
            'communication_object_id' => ['type' => 'text', 'required' => true],
            'communication_parentobject' => ['type' => 'text_lower', 'nullable' => true],
            'communication_template' => ['type' => 'text', 'required' => true],
            'communication_initiatedby' => ['type' => 'text', 'nullable' => true],
            'communication_recipients' => ['type' => 'json_object', 'required' => true],
            'communication_processed' => ['type' => 'timestamp', 'nullable' => true],
            'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
            'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
            'event_id' => ['type' => 'text', 'default' => 'event_8301'],
            'process_id' => ['type' => 'text', 'default' => 'process_8301'],
            'access' => ['type' => 'text_lower', 'default' => 'private'],
            'status' => ['type' => 'text_lower', 'default' => 'active'],
            'active' => ['type' => 'active', 'default' => 1],
        ],
        'update' => [
            'communication_attributes' => ['type' => 'json_object'],
            'communication_object_id' => ['type' => 'text'],
            'communication_parentobject' => ['type' => 'text_lower', 'nullable' => true],
            'communication_template' => ['type' => 'text'],
            'communication_initiatedby' => ['type' => 'text', 'nullable' => true],
            'communication_recipients' => ['type' => 'json_object'],
            'communication_processed' => ['type' => 'timestamp', 'nullable' => true],
            'created_by_user_id' => ['type' => 'text'],
            'created_for_app_id' => ['type' => 'text'],
            'event_id' => ['type' => 'text'],
            'process_id' => ['type' => 'text'],
            'access' => ['type' => 'text_lower'],
            'status' => ['type' => 'text_lower'],
            'active' => ['type' => 'active'],
        ],
    ],
    'deliveries' => [
        'table' => 'deliveries',
        'entity' => 'delivery',
        'primary_key' => 'delivery_id',
        'columns' => [
            'delivery_id', 'delivery_attributes', 'delivery_object_id', 'delivery_parentobject', 'delivery_communication',
            'delivery_channel', 'delivery_metadata', 'delivery_sentat', 'delivery_attempts',
            'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active',
            'time_started', 'time_updated',
        ],
        'json_fields' => ['delivery_attributes', 'delivery_metadata'],
        'create' => [
            'delivery_attributes' => ['type' => 'json_object', 'default' => []],
            'delivery_object_id' => ['type' => 'text', 'required' => true],
            'delivery_parentobject' => ['type' => 'text_lower', 'nullable' => true],
            'delivery_communication' => ['type' => 'text', 'required' => true],
            'delivery_channel' => ['type' => 'text_lower', 'required' => true],
            'delivery_metadata' => ['type' => 'json_object', 'required' => true],
            'delivery_sentat' => ['type' => 'timestamp', 'nullable' => true],
            'delivery_attempts' => ['type' => 'nonnegative_int', 'default' => 0],
            'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
            'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
            'event_id' => ['type' => 'text', 'default' => 'event_8301'],
            'process_id' => ['type' => 'text', 'default' => 'process_8301'],
            'access' => ['type' => 'text_lower', 'default' => 'private'],
            'status' => ['type' => 'text_lower', 'default' => 'active'],
            'active' => ['type' => 'active', 'default' => 1],
        ],
        'update' => [
            'delivery_attributes' => ['type' => 'json_object'],
            'delivery_object_id' => ['type' => 'text'],
            'delivery_parentobject' => ['type' => 'text_lower', 'nullable' => true],
            'delivery_communication' => ['type' => 'text'],
            'delivery_channel' => ['type' => 'text_lower'],
            'delivery_metadata' => ['type' => 'json_object'],
            'delivery_sentat' => ['type' => 'timestamp', 'nullable' => true],
            'delivery_attempts' => ['type' => 'nonnegative_int'],
            'created_by_user_id' => ['type' => 'text'],
            'created_for_app_id' => ['type' => 'text'],
            'event_id' => ['type' => 'text'],
            'process_id' => ['type' => 'text'],
            'access' => ['type' => 'text_lower'],
            'status' => ['type' => 'text_lower'],
            'active' => ['type' => 'active'],
        ],
    ],
    'threads' => [
        'table' => 'threads',
        'entity' => 'thread',
        'primary_key' => 'thread_id',
        'columns' => [
            'thread_id', 'thread_attributes', 'thread_subject', 'thread_participants', 'thread_lastmessagepreview',
            'thread_lastmessageat', 'thread_author_id',
            'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active',
            'time_started', 'time_updated',
        ],
        'json_fields' => ['thread_attributes', 'thread_participants'],
        'create' => [
            'thread_attributes' => ['type' => 'json_object', 'default' => []],
            'thread_subject' => ['type' => 'text', 'required' => true],
            'thread_participants' => ['type' => 'json_object', 'default' => []],
            'thread_lastmessagepreview' => ['type' => 'text', 'nullable' => true, 'default' => ''],
            'thread_lastmessageat' => ['type' => 'timestamp', 'nullable' => true],
            'thread_author_id' => ['type' => 'text', 'required' => true],
            'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
            'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
            'event_id' => ['type' => 'text', 'default' => 'event_8301'],
            'process_id' => ['type' => 'text', 'default' => 'process_8301'],
            'access' => ['type' => 'text_lower', 'default' => 'private'],
            'status' => ['type' => 'text_lower', 'default' => 'active'],
            'active' => ['type' => 'active', 'default' => 1],
        ],
        'update' => [
            'thread_attributes' => ['type' => 'json_object'],
            'thread_subject' => ['type' => 'text'],
            'thread_participants' => ['type' => 'json_object'],
            'thread_lastmessagepreview' => ['type' => 'text', 'nullable' => true],
            'thread_lastmessageat' => ['type' => 'timestamp', 'nullable' => true],
            'thread_author_id' => ['type' => 'text'],
            'created_by_user_id' => ['type' => 'text'],
            'created_for_app_id' => ['type' => 'text'],
            'event_id' => ['type' => 'text'],
            'process_id' => ['type' => 'text'],
            'access' => ['type' => 'text_lower'],
            'status' => ['type' => 'text_lower'],
            'active' => ['type' => 'active'],
        ],
    ],
    'messages' => [
        'table' => 'messages',
        'entity' => 'message',
        'primary_key' => 'message_id',
        'columns' => [
            'message_id', 'message_attributes', 'thread_id', 'message_sender_id', 'message_body',
            'message_attachments', 'message_readby',
            'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active',
            'time_started', 'time_updated',
        ],
        'json_fields' => ['message_attributes', 'message_attachments', 'message_readby'],
        'create' => [
            'message_attributes' => ['type' => 'json_object', 'default' => []],
            'thread_id' => ['type' => 'text', 'required' => true],
            'message_sender_id' => ['type' => 'text', 'required' => true],
            'message_body' => ['type' => 'text', 'required' => true],
            'message_attachments' => ['type' => 'json_object', 'default' => []],
            'message_readby' => ['type' => 'json_object', 'default' => []],
            'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
            'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
            'event_id' => ['type' => 'text', 'default' => 'event_8301'],
            'process_id' => ['type' => 'text', 'default' => 'process_8301'],
            'access' => ['type' => 'text_lower', 'default' => 'private'],
            'status' => ['type' => 'text_lower', 'default' => 'active'],
            'active' => ['type' => 'active', 'default' => 1],
        ],
        'update' => [
            'message_attributes' => ['type' => 'json_object'],
            'thread_id' => ['type' => 'text'],
            'message_sender_id' => ['type' => 'text'],
            'message_body' => ['type' => 'text'],
            'message_attachments' => ['type' => 'json_object'],
            'message_readby' => ['type' => 'json_object'],
            'created_by_user_id' => ['type' => 'text'],
            'created_for_app_id' => ['type' => 'text'],
            'event_id' => ['type' => 'text'],
            'process_id' => ['type' => 'text'],
            'access' => ['type' => 'text_lower'],
            'status' => ['type' => 'text_lower'],
            'active' => ['type' => 'active'],
        ],
    ],
];

$makeCommunicationsResourceController = static function (string $name) use ($communicationsResourceConfigs, $authorizedCommunicationsDb): PlatformResourceController {
    $config = $communicationsResourceConfigs[$name];
    $repository = new PlatformResourceRepository(
        $authorizedCommunicationsDb(),
        $config['table'],
        $config['primary_key'],
        $config['columns'],
        $config['json_fields'] ?? [],
        $config['hidden_fields'] ?? []
    );

    return new PlatformResourceController($name, $config['entity'], $config['primary_key'], $repository, $config);
};

foreach (array_keys($communicationsResourceConfigs) as $resourceName) {
    $router->get('#^/' . $resourceName . '$#', static function () use ($makeCommunicationsResourceController, $resourceName): void {
        $makeCommunicationsResourceController($resourceName)->index();
    });

    $router->get('#^/' . $resourceName . '/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommunicationsResourceController, $resourceName): void {
        $makeCommunicationsResourceController($resourceName)->show($params['id']);
    });

    $router->post('#^/' . $resourceName . '$#', static function (Request $request) use ($makeCommunicationsResourceController, $resourceName): void {
        $makeCommunicationsResourceController($resourceName)->store($request);
    });

    $router->patch('#^/' . $resourceName . '/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommunicationsResourceController, $resourceName): void {
        $makeCommunicationsResourceController($resourceName)->update($params['id'], $request);
    });

    $router->delete('#^/' . $resourceName . '/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeCommunicationsResourceController, $resourceName): void {
        $makeCommunicationsResourceController($resourceName)->destroy($params['id']);
    });
}
