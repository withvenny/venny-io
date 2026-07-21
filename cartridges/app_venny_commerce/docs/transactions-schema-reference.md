# transactions Schema Reference

Owned by `app_venny_commerce`.

Primary key: `transaction_id`

## JSONB fields

- `transaction_attributes`

## Core fields

This endpoint follows the table-backed commerce schema for `transactions` and uses the shared platform resource pattern for CRUD, JSONB normalization, response envelopes, and soft archive behavior.

Standard fields include `created_by_user_id`, `created_for_app_id`, `access`, `status`, `active`, `time_started`, and `time_updated`.
