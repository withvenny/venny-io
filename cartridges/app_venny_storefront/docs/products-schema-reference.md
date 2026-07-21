# products Schema Reference

Owned by `app_venny_storefront`.

Primary key: `product_id`

## JSONB fields

- `product_attributes`
- `product_images`

## Core fields

This endpoint follows the table-backed storefront schema for `products` and uses the shared platform resource pattern for CRUD, JSONB normalization, response envelopes, and soft archive behavior.

Standard fields include `created_by_user_id`, `created_for_app_id`, `access`, `status`, `active`, `time_started`, and `time_updated`.
