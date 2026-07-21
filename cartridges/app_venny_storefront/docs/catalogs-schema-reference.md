# catalogs Schema Reference

Owned by `app_venny_storefront`.

Primary key: `catalog_id`

## JSONB fields

- `catalog_attributes`
- `catalog_images`

## Core fields

This endpoint follows the table-backed storefront schema for `catalogs` and uses the shared platform resource pattern for CRUD, JSONB normalization, response envelopes, and soft archive behavior.

Standard fields include `created_by_user_id`, `created_for_app_id`, `access`, `status`, `active`, `time_started`, and `time_updated`.
