<?php

declare(strict_types=1);

use VennyIO\Controllers\PlatformResourceController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\PlatformResourceRepository;
use VennyIO\Support\ApiKeyAuth;
use VennyIO\Support\Database;

/** @var Router $router */

$authorizedIdentityDb = static function (): PDO {
    $db = Database::connection();
    ApiKeyAuth::require($db);
    return $db;
};

$identityResourceConfigs = [
    'persons' => [
        'entity' => 'person',
        'table' => 'persons',
        'primary_key' => 'person_id',
        'json_fields' => ['person_attributes', 'person_emails', 'person_phones', 'person_addresses'],
        'columns' => [
            'person_id', 'person_attributes', 'person_firstname', 'person_middlename', 'person_lastname',
            'person_emails', 'person_phones', 'person_addresses', 'person_dateofbirth', 'person_smsoptindate',
            'person_source', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access',
            'status', 'active', 'time_started', 'time_updated',
        ],
        'create' => [
            'person_attributes' => ['type' => 'json_object', 'default' => []],
            'person_firstname' => ['type' => 'text', 'nullable' => true],
            'person_middlename' => ['type' => 'text', 'nullable' => true],
            'person_lastname' => ['type' => 'text', 'nullable' => true],
            'person_emails' => ['type' => 'json_object', 'default' => []],
            'person_phones' => ['type' => 'json_object', 'nullable' => true],
            'person_addresses' => ['type' => 'json_object', 'nullable' => true],
            'person_dateofbirth' => ['type' => 'text', 'nullable' => true],
            'person_smsoptindate' => ['type' => 'timestamp', 'nullable' => true],
            'person_source' => ['type' => 'text_lower', 'default' => 'website'],
            'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
            'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
            'event_id' => ['type' => 'text', 'default' => 'event_8301'],
            'process_id' => ['type' => 'text', 'default' => 'process_8301'],
            'access' => ['type' => 'text_lower', 'default' => 'private'],
            'status' => ['type' => 'text_lower', 'default' => 'active'],
            'active' => ['type' => 'active', 'default' => 1],
        ],
        'update' => [
            'person_attributes' => ['type' => 'json_object'],
            'person_firstname' => ['type' => 'text', 'nullable' => true],
            'person_middlename' => ['type' => 'text', 'nullable' => true],
            'person_lastname' => ['type' => 'text', 'nullable' => true],
            'person_emails' => ['type' => 'json_object'],
            'person_phones' => ['type' => 'json_object', 'nullable' => true],
            'person_addresses' => ['type' => 'json_object', 'nullable' => true],
            'person_dateofbirth' => ['type' => 'text', 'nullable' => true],
            'person_smsoptindate' => ['type' => 'timestamp', 'nullable' => true],
            'person_source' => ['type' => 'text_lower'],
            'created_by_user_id' => ['type' => 'text'],
            'created_for_app_id' => ['type' => 'text'],
            'event_id' => ['type' => 'text'],
            'process_id' => ['type' => 'text'],
            'access' => ['type' => 'text_lower'],
            'status' => ['type' => 'text_lower'],
            'active' => ['type' => 'active'],
        ],
    ],
    'users' => [
        'entity' => 'user',
        'table' => 'users',
        'primary_key' => 'user_id',
        'json_fields' => ['user_attributes', 'user_addresses', 'user_phones', 'user_optins'],
        'hidden_fields' => ['user_passwordhash'],
        'columns' => [
            'user_id', 'user_attributes', 'user_email', 'user_addresses', 'user_phones', 'user_optins',
            'user_passwordhash', 'user_username', 'user_displayname', 'user_bio', 'user_avatarurl', 'user_theme',
            'user_biopublished', 'user_lastlogin', 'created_by_user_id', 'created_for_app_id', 'event_id',
            'process_id', 'access', 'status', 'active', 'time_started', 'time_updated',
        ],
        'create' => [
            'user_attributes' => ['type' => 'json_object', 'default' => []],
            'user_email' => ['type' => 'text_lower', 'required' => true],
            'user_addresses' => ['type' => 'json_object', 'nullable' => true, 'default' => []],
            'user_phones' => ['type' => 'json_object', 'nullable' => true, 'default' => []],
            'user_optins' => ['type' => 'json_object', 'nullable' => true, 'default' => []],
            'user_passwordhash' => ['type' => 'text', 'nullable' => true],
            'user_username' => ['type' => 'text_lower', 'nullable' => true],
            'user_displayname' => ['type' => 'text', 'default' => ''],
            'user_bio' => ['type' => 'text', 'default' => ''],
            'user_avatarurl' => ['type' => 'text', 'default' => ''],
            'user_theme' => ['type' => 'text_lower', 'default' => 'salt'],
            'user_biopublished' => ['type' => 'boolean', 'default' => true],
            'user_lastlogin' => ['type' => 'timestamp', 'nullable' => true],
            'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
            'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
            'event_id' => ['type' => 'text', 'default' => 'event_8301'],
            'process_id' => ['type' => 'text', 'default' => 'process_8301'],
            'access' => ['type' => 'text_lower', 'default' => 'private'],
            'status' => ['type' => 'text_lower', 'default' => 'active'],
            'active' => ['type' => 'active', 'default' => 1],
        ],
        'update' => [
            'user_attributes' => ['type' => 'json_object'],
            'user_email' => ['type' => 'text_lower'],
            'user_addresses' => ['type' => 'json_object', 'nullable' => true],
            'user_phones' => ['type' => 'json_object', 'nullable' => true],
            'user_optins' => ['type' => 'json_object', 'nullable' => true],
            'user_passwordhash' => ['type' => 'text', 'nullable' => true],
            'user_username' => ['type' => 'text_lower', 'nullable' => true],
            'user_displayname' => ['type' => 'text'],
            'user_bio' => ['type' => 'text'],
            'user_avatarurl' => ['type' => 'text'],
            'user_theme' => ['type' => 'text_lower'],
            'user_biopublished' => ['type' => 'boolean'],
            'user_lastlogin' => ['type' => 'timestamp', 'nullable' => true],
            'created_by_user_id' => ['type' => 'text'],
            'created_for_app_id' => ['type' => 'text'],
            'event_id' => ['type' => 'text'],
            'process_id' => ['type' => 'text'],
            'access' => ['type' => 'text_lower'],
            'status' => ['type' => 'text_lower'],
            'active' => ['type' => 'active'],
        ],
    ],
    'profiles' => [
        'entity' => 'profile',
        'table' => 'profiles',
        'primary_key' => 'profile_id',
        'json_fields' => ['profile_attributes'],
        'columns' => [
            'profile_id', 'profile_attributes', 'profile_username', 'profile_displayname', 'profile_bio',
            'profile_avatarurl', 'profile_theme', 'profile_biopublished', 'created_by_user_id',
            'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active', 'time_started',
            'time_updated',
        ],
        'create' => [
            'profile_attributes' => ['type' => 'json_object', 'default' => []],
            'profile_username' => ['type' => 'text_lower', 'nullable' => true],
            'profile_displayname' => ['type' => 'text', 'default' => ''],
            'profile_bio' => ['type' => 'text', 'default' => ''],
            'profile_avatarurl' => ['type' => 'text', 'default' => ''],
            'profile_theme' => ['type' => 'text_lower', 'default' => 'salt'],
            'profile_biopublished' => ['type' => 'boolean', 'default' => true],
            'created_by_user_id' => ['type' => 'text', 'default' => 'user_8301'],
            'created_for_app_id' => ['type' => 'text', 'default' => 'app_8301'],
            'event_id' => ['type' => 'text', 'default' => 'event_8301'],
            'process_id' => ['type' => 'text', 'default' => 'process_8301'],
            'access' => ['type' => 'text_lower', 'default' => 'private'],
            'status' => ['type' => 'text_lower', 'default' => 'active'],
            'active' => ['type' => 'active', 'default' => 1],
        ],
        'update' => [
            'profile_attributes' => ['type' => 'json_object'],
            'profile_username' => ['type' => 'text_lower', 'nullable' => true],
            'profile_displayname' => ['type' => 'text'],
            'profile_bio' => ['type' => 'text'],
            'profile_avatarurl' => ['type' => 'text'],
            'profile_theme' => ['type' => 'text_lower'],
            'profile_biopublished' => ['type' => 'boolean'],
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

$makeIdentityResourceController = static function (string $name) use ($identityResourceConfigs, $authorizedIdentityDb): PlatformResourceController {
    $config = $identityResourceConfigs[$name];
    $repository = new PlatformResourceRepository(
        $authorizedIdentityDb(),
        $config['table'],
        $config['primary_key'],
        $config['columns'],
        $config['json_fields'] ?? [],
        $config['hidden_fields'] ?? []
    );

    return new PlatformResourceController($name, $config['entity'], $config['primary_key'], $repository, $config);
};

foreach (array_keys($identityResourceConfigs) as $resourceName) {
    $router->get('#^/' . $resourceName . '$#', static function (Request $request) use ($makeIdentityResourceController, $resourceName): void {
        $makeIdentityResourceController($resourceName)->index($request);
    });

    $router->get('#^/' . $resourceName . '/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeIdentityResourceController, $resourceName): void {
        $makeIdentityResourceController($resourceName)->show($params['id']);
    });

    $router->post('#^/' . $resourceName . '$#', static function (Request $request) use ($makeIdentityResourceController, $resourceName): void {
        $makeIdentityResourceController($resourceName)->store($request);
    });

    $router->patch('#^/' . $resourceName . '/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeIdentityResourceController, $resourceName): void {
        $makeIdentityResourceController($resourceName)->update($params['id'], $request);
    });

    $router->delete('#^/' . $resourceName . '/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeIdentityResourceController, $resourceName): void {
        $makeIdentityResourceController($resourceName)->destroy($params['id']);
    });
}
