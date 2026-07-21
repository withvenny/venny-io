<?php

declare(strict_types=1);

use VennyIO\Controllers\PlatformResourceController;
use VennyIO\Controllers\ContactCaptureController;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Repositories\PlatformResourceRepository;
use VennyIO\Repositories\ContactCaptureRepository;
use VennyIO\Support\ApiKeyAuth;
use VennyIO\Support\Database;

/** @var Router $router */

$authorizedCrmDb = static function (): PDO {
    $db = Database::connection();
    ApiKeyAuth::require($db);
    return $db;
};


$makeContactCaptureController = static function (): ContactCaptureController {
    $db = Database::connection();
    $appContext = ApiKeyAuth::require($db);

    return new ContactCaptureController(
        new ContactCaptureRepository($db),
        $appContext
    );
};

$router->post('#^/sign-up-for-updates$#', static function (Request $request) use ($makeContactCaptureController): void {
    $makeContactCaptureController()->signUpForUpdates($request);
});

$makeCrmController = static function (string $resourceName) use ($authorizedCrmDb): PlatformResourceController {
    $resources = [
        'contacts' => [
            'entity' => 'contact',
            'primaryKey' => 'contact_id',
            'columns' => ['contact_id', 'contact_attributes', 'contact_firstname', 'contact_middlename', 'contact_lastname', 'contact_emails', 'contact_phones', 'contact_company_id', 'contact_source', 'contact_title', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active', 'time_started', 'time_updated'],
            'jsonFields' => ['contact_attributes', 'contact_emails', 'contact_phones', 'contact_source'],
            'create' => [
                'contact_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'contact_firstname' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'contact_middlename' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'contact_lastname' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'contact_emails' => [
                    'type' => 'json_object',
                    'required' => true,
                    'default' => []
                ],
                'contact_phones' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => null
                ],
                'contact_company_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'contact_source' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => null
                ],
                'contact_title' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ],
            'update' => [
                'contact_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'contact_firstname' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'contact_middlename' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'contact_lastname' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'contact_emails' => [
                    'type' => 'json_object',
                    'required' => true,
                    'default' => []
                ],
                'contact_phones' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => null
                ],
                'contact_company_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'contact_source' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => null
                ],
                'contact_title' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ]
        ],
        'companies' => [
            'entity' => 'company',
            'primaryKey' => 'company_id',
            'columns' => ['company_id', 'company_attributes', 'company_name', 'company_website', 'company_industry', 'company_phone', 'company_address_1', 'company_address_2', 'company_city', 'company_state', 'company_postalcode', 'company_country', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active', 'time_started', 'time_updated'],
            'jsonFields' => ['company_attributes'],
            'create' => [
                'company_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'company_name' => [
                    'type' => 'text',
                    'required' => true
                ],
                'company_website' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_industry' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_phone' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_address_1' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_address_2' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_city' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_state' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_postalcode' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_country' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ],
            'update' => [
                'company_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'company_name' => [
                    'type' => 'text',
                    'required' => true
                ],
                'company_website' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_industry' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_phone' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_address_1' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_address_2' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_city' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_state' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_postalcode' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'company_country' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ]
        ],
        'deals' => [
            'entity' => 'deal',
            'primaryKey' => 'deal_id',
            'columns' => ['deal_id', 'deal_attributes', 'deal_contact_id', 'deal_pipeline_id', 'deal_company_id', 'deal_stage_id', 'deal_amount', 'deal_expectedclosedate', 'deal_status', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active', 'time_started', 'time_updated'],
            'jsonFields' => ['deal_attributes'],
            'create' => [
                'deal_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'deal_contact_id' => [
                    'type' => 'text',
                    'required' => true
                ],
                'deal_pipeline_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'deal_company_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'deal_stage_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'deal_amount' => [
                    'type' => 'text',
                    'required' => true
                ],
                'deal_expectedclosedate' => [
                    'type' => 'text',
                    'required' => true
                ],
                'deal_status' => [
                    'type' => 'text',
                    'nullable' => true,
                    'default' => 'open'
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ],
            'update' => [
                'deal_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'deal_contact_id' => [
                    'type' => 'text',
                    'required' => true
                ],
                'deal_pipeline_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'deal_company_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'deal_stage_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'deal_amount' => [
                    'type' => 'text',
                    'required' => true
                ],
                'deal_expectedclosedate' => [
                    'type' => 'text',
                    'required' => true
                ],
                'deal_status' => [
                    'type' => 'text',
                    'nullable' => true,
                    'default' => 'open'
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ]
        ],
        'pipelines' => [
            'entity' => 'pipeline',
            'primaryKey' => 'pipeline_id',
            'columns' => ['pipeline_id', 'pipeline_attributes', 'pipeline_name', 'pipeline_description', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active', 'time_started', 'time_updated'],
            'jsonFields' => ['pipeline_attributes'],
            'create' => [
                'pipeline_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'pipeline_name' => [
                    'type' => 'text',
                    'required' => true
                ],
                'pipeline_description' => [
                    'type' => 'text',
                    'required' => true
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ],
            'update' => [
                'pipeline_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'pipeline_name' => [
                    'type' => 'text',
                    'required' => true
                ],
                'pipeline_description' => [
                    'type' => 'text',
                    'required' => true
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ]
        ],
        'stages' => [
            'entity' => 'stage',
            'primaryKey' => 'stage_id',
            'columns' => ['stage_id', 'stage_attributes', 'stage_pipeline_id', 'stage_name', 'stage_position', 'stage_probability', 'stage_is_closed', 'stage_is_won', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active', 'time_started', 'time_updated'],
            'jsonFields' => ['stage_attributes'],
            'create' => [
                'stage_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'stage_pipeline_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'stage_name' => [
                    'type' => 'text',
                    'required' => true
                ],
                'stage_position' => [
                    'type' => 'nonnegative_int',
                    'nullable' => true,
                    'default' => 0
                ],
                'stage_probability' => [
                    'type' => 'nonnegative_int',
                    'nullable' => true,
                    'default' => 0
                ],
                'stage_is_closed' => [
                    'type' => 'boolean',
                    'nullable' => true,
                    'default' => false
                ],
                'stage_is_won' => [
                    'type' => 'boolean',
                    'nullable' => true,
                    'default' => false
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ],
            'update' => [
                'stage_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'stage_pipeline_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'stage_name' => [
                    'type' => 'text',
                    'required' => true
                ],
                'stage_position' => [
                    'type' => 'nonnegative_int',
                    'nullable' => true,
                    'default' => 0
                ],
                'stage_probability' => [
                    'type' => 'nonnegative_int',
                    'nullable' => true,
                    'default' => 0
                ],
                'stage_is_closed' => [
                    'type' => 'boolean',
                    'nullable' => true,
                    'default' => false
                ],
                'stage_is_won' => [
                    'type' => 'boolean',
                    'nullable' => true,
                    'default' => false
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ]
        ],
        'activities' => [
            'entity' => 'activity',
            'primaryKey' => 'activity_id',
            'columns' => ['activity_id', 'activity_attributes', 'activity_related_id', 'activity_contact_id', 'activity_company_id', 'activity_deal_id', 'activity_related_type', 'activity_activity_type', 'activity_subject', 'activity_body', 'activity_occurred_at', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active', 'time_started', 'time_updated'],
            'jsonFields' => ['activity_attributes'],
            'create' => [
                'activity_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'activity_related_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'activity_contact_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'activity_company_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'activity_deal_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'activity_related_type' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'activity_activity_type' => [
                    'type' => 'text',
                    'nullable' => true,
                    'default' => 'note'
                ],
                'activity_subject' => [
                    'type' => 'text',
                    'required' => true
                ],
                'activity_body' => [
                    'type' => 'text',
                    'required' => true
                ],
                'activity_occurred_at' => [
                    'type' => 'timestamp',
                    'nullable' => true
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ],
            'update' => [
                'activity_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'activity_related_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'activity_contact_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'activity_company_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'activity_deal_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'activity_related_type' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'activity_activity_type' => [
                    'type' => 'text',
                    'nullable' => true,
                    'default' => 'note'
                ],
                'activity_subject' => [
                    'type' => 'text',
                    'required' => true
                ],
                'activity_body' => [
                    'type' => 'text',
                    'required' => true
                ],
                'activity_occurred_at' => [
                    'type' => 'timestamp',
                    'nullable' => true
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ]
        ],
        'tasks' => [
            'entity' => 'task',
            'primaryKey' => 'task_id',
            'columns' => ['task_id', 'task_attributes', 'task_assigned_to_user_id', 'task_contact_id', 'task_deal_id', 'task_company_id', 'task_title', 'task_description', 'task_due_at', 'task_completed_at', 'task_priority', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active', 'time_started', 'time_updated'],
            'jsonFields' => ['task_attributes'],
            'create' => [
                'task_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'task_assigned_to_user_id' => [
                    'type' => 'text',
                    'required' => true
                ],
                'task_contact_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'task_deal_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'task_company_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'task_title' => [
                    'type' => 'text',
                    'required' => true
                ],
                'task_description' => [
                    'type' => 'text',
                    'required' => true
                ],
                'task_due_at' => [
                    'type' => 'timestamp',
                    'nullable' => true
                ],
                'task_completed_at' => [
                    'type' => 'timestamp',
                    'nullable' => true
                ],
                'task_priority' => [
                    'type' => 'text',
                    'nullable' => true,
                    'default' => 'normal'
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ],
            'update' => [
                'task_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'task_assigned_to_user_id' => [
                    'type' => 'text',
                    'required' => true
                ],
                'task_contact_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'task_deal_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'task_company_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'task_title' => [
                    'type' => 'text',
                    'required' => true
                ],
                'task_description' => [
                    'type' => 'text',
                    'required' => true
                ],
                'task_due_at' => [
                    'type' => 'timestamp',
                    'nullable' => true
                ],
                'task_completed_at' => [
                    'type' => 'timestamp',
                    'nullable' => true
                ],
                'task_priority' => [
                    'type' => 'text',
                    'nullable' => true,
                    'default' => 'normal'
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ]
        ],
        'notes' => [
            'entity' => 'note',
            'primaryKey' => 'note_id',
            'columns' => ['note_id', 'note_attributes', 'note_company_id', 'note_contact_id', 'note_deal_id', 'note_body', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active', 'time_started', 'time_updated'],
            'jsonFields' => ['note_attributes'],
            'create' => [
                'note_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'note_company_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'note_contact_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'note_deal_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'note_body' => [
                    'type' => 'text',
                    'required' => true
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ],
            'update' => [
                'note_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'note_company_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'note_contact_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'note_deal_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'note_body' => [
                    'type' => 'text',
                    'required' => true
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ]
        ],
        'tags' => [
            'entity' => 'tag',
            'primaryKey' => 'tag_id',
            'columns' => ['tag_id', 'tag_attributes', 'tag_related_id', 'tag_contact_id', 'tag_company_id', 'tag_deal_id', 'tag_name', 'tag_type', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active', 'time_started', 'time_updated'],
            'jsonFields' => ['tag_attributes'],
            'create' => [
                'tag_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'tag_related_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'tag_contact_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'tag_company_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'tag_deal_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'tag_name' => [
                    'type' => 'text',
                    'required' => true
                ],
                'tag_type' => [
                    'type' => 'text',
                    'nullable' => true,
                    'default' => 'label'
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ],
            'update' => [
                'tag_attributes' => [
                    'type' => 'json_object',
                    'nullable' => true,
                    'default' => []
                ],
                'tag_related_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'tag_contact_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'tag_company_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'tag_deal_id' => [
                    'type' => 'text',
                    'nullable' => true
                ],
                'tag_name' => [
                    'type' => 'text',
                    'required' => true
                ],
                'tag_type' => [
                    'type' => 'text',
                    'nullable' => true,
                    'default' => 'label'
                ],
                'created_by_user_id' => [
                    'type' => 'text',
                    'default' => 'user_8301'
                ],
                'created_for_app_id' => [
                    'type' => 'text',
                    'default' => 'app_8301'
                ],
                'event_id' => [
                    'type' => 'text',
                    'default' => 'event_8301'
                ],
                'process_id' => [
                    'type' => 'text',
                    'default' => 'process_8301'
                ],
                'access' => [
                    'type' => 'text',
                    'default' => 'private'
                ],
                'status' => [
                    'type' => 'text',
                    'default' => 'active'
                ],
                'active' => [
                    'type' => 'active',
                    'default' => 1
                ]
            ]
        ]
    ];

    if (!isset($resources[$resourceName])) {
        throw new RuntimeException('Unknown CRM resource: ' . $resourceName);
    }

    $resource = $resources[$resourceName];
    $repository = new PlatformResourceRepository(
        $authorizedCrmDb(),
        $resourceName,
        $resource['primaryKey'],
        $resource['columns'],
        $resource['jsonFields']
    );

    return new PlatformResourceController(
        $resourceName,
        $resource['entity'],
        $resource['primaryKey'],
        $repository,
        [
            'columns' => $resource['columns'],
            'create' => $resource['create'],
            'update' => $resource['update'],
        ]
    );
};


$router->get('/contacts', function () use ($makeCrmController): void {
    $makeCrmController('contacts')->index();
});
$router->post('/contacts', function (Request $request) use ($makeCrmController): void {
    $makeCrmController('contacts')->store($request);
});
$router->get('/contacts/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('contacts')->show($id);
});
$router->patch('/contacts/{id}', function (string $id, Request $request) use ($makeCrmController): void {
    $makeCrmController('contacts')->update($id, $request);
});
$router->delete('/contacts/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('contacts')->destroy($id);
});

$router->get('/companies', function () use ($makeCrmController): void {
    $makeCrmController('companies')->index();
});
$router->post('/companies', function (Request $request) use ($makeCrmController): void {
    $makeCrmController('companies')->store($request);
});
$router->get('/companies/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('companies')->show($id);
});
$router->patch('/companies/{id}', function (string $id, Request $request) use ($makeCrmController): void {
    $makeCrmController('companies')->update($id, $request);
});
$router->delete('/companies/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('companies')->destroy($id);
});

$router->get('/deals', function () use ($makeCrmController): void {
    $makeCrmController('deals')->index();
});
$router->post('/deals', function (Request $request) use ($makeCrmController): void {
    $makeCrmController('deals')->store($request);
});
$router->get('/deals/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('deals')->show($id);
});
$router->patch('/deals/{id}', function (string $id, Request $request) use ($makeCrmController): void {
    $makeCrmController('deals')->update($id, $request);
});
$router->delete('/deals/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('deals')->destroy($id);
});

$router->get('/pipelines', function () use ($makeCrmController): void {
    $makeCrmController('pipelines')->index();
});
$router->post('/pipelines', function (Request $request) use ($makeCrmController): void {
    $makeCrmController('pipelines')->store($request);
});
$router->get('/pipelines/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('pipelines')->show($id);
});
$router->patch('/pipelines/{id}', function (string $id, Request $request) use ($makeCrmController): void {
    $makeCrmController('pipelines')->update($id, $request);
});
$router->delete('/pipelines/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('pipelines')->destroy($id);
});

$router->get('/stages', function () use ($makeCrmController): void {
    $makeCrmController('stages')->index();
});
$router->post('/stages', function (Request $request) use ($makeCrmController): void {
    $makeCrmController('stages')->store($request);
});
$router->get('/stages/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('stages')->show($id);
});
$router->patch('/stages/{id}', function (string $id, Request $request) use ($makeCrmController): void {
    $makeCrmController('stages')->update($id, $request);
});
$router->delete('/stages/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('stages')->destroy($id);
});

$router->get('/activities', function () use ($makeCrmController): void {
    $makeCrmController('activities')->index();
});
$router->post('/activities', function (Request $request) use ($makeCrmController): void {
    $makeCrmController('activities')->store($request);
});
$router->get('/activities/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('activities')->show($id);
});
$router->patch('/activities/{id}', function (string $id, Request $request) use ($makeCrmController): void {
    $makeCrmController('activities')->update($id, $request);
});
$router->delete('/activities/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('activities')->destroy($id);
});

$router->get('/tasks', function () use ($makeCrmController): void {
    $makeCrmController('tasks')->index();
});
$router->post('/tasks', function (Request $request) use ($makeCrmController): void {
    $makeCrmController('tasks')->store($request);
});
$router->get('/tasks/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('tasks')->show($id);
});
$router->patch('/tasks/{id}', function (string $id, Request $request) use ($makeCrmController): void {
    $makeCrmController('tasks')->update($id, $request);
});
$router->delete('/tasks/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('tasks')->destroy($id);
});

$router->get('/notes', function () use ($makeCrmController): void {
    $makeCrmController('notes')->index();
});
$router->post('/notes', function (Request $request) use ($makeCrmController): void {
    $makeCrmController('notes')->store($request);
});
$router->get('/notes/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('notes')->show($id);
});
$router->patch('/notes/{id}', function (string $id, Request $request) use ($makeCrmController): void {
    $makeCrmController('notes')->update($id, $request);
});
$router->delete('/notes/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('notes')->destroy($id);
});

$router->get('/tags', function () use ($makeCrmController): void {
    $makeCrmController('tags')->index();
});
$router->post('/tags', function (Request $request) use ($makeCrmController): void {
    $makeCrmController('tags')->store($request);
});
$router->get('/tags/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('tags')->show($id);
});
$router->patch('/tags/{id}', function (string $id, Request $request) use ($makeCrmController): void {
    $makeCrmController('tags')->update($id, $request);
});
$router->delete('/tags/{id}', function (string $id) use ($makeCrmController): void {
    $makeCrmController('tags')->destroy($id);
});
