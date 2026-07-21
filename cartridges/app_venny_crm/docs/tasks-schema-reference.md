# tasks schema reference

Primary key: `task_id`

## Columns exposed by the API

- `task_id`
- `task_attributes`
- `task_assigned_to_user_id`
- `task_contact_id`
- `task_deal_id`
- `task_company_id`
- `task_title`
- `task_description`
- `task_due_at`
- `task_completed_at`
- `task_priority`
- `created_by_user_id`
- `created_for_app_id`
- `event_id`
- `process_id`
- `access`
- `status`
- `active`
- `time_started`
- `time_updated`

## JSON fields

- `task_attributes`

## Required on create

- `task_assigned_to_user_id`
- `task_title`
- `task_description`
