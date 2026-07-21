# orders Schema Reference

Owned by `app_venny_commerce`.

Primary key: `order_id`

## JSONB fields

- `order_attributes`

## Core fields

This endpoint follows the table-backed commerce schema for `orders` and uses the shared platform resource pattern for CRUD, JSONB normalization, response envelopes, and soft archive behavior.

Standard fields include `created_by_user_id`, `created_for_app_id`, `access`, `status`, `active`, `time_started`, and `time_updated`.
