# app_venny_crm

`app_venny_crm` is the reusable Venny I/O CRM cartridge. It owns CRM data-model resources and exposes bearer-authenticated resource endpoints.

## Domain

CRM

## Requires

- `app_venny_platform`
- `app_venny_identity`

## Endpoint families

- `/contacts`
- `/companies`
- `/deals`
- `/pipelines`
- `/stages`
- `/activities`
- `/tasks`
- `/notes`
- `/tags`

Each table-backed resource supports:

```text
GET     /resource
GET     /resource/:id
POST    /resource
PATCH   /resource/:id
DELETE  /resource/:id
```

`DELETE` performs a soft archive by setting `status = archived` and `active = 0`.

## Boundary

This cartridge manages CRM records only. Messaging, public posts, storefront products, and CMS assets belong to their own cartridges.


## Updates signup capture

This cartridge also exposes `POST /sign-up-for-updates` for lightweight website update-signup forms. It creates duplicate-allowed `contacts` records with email as the only personal field required and source view metadata stored on `contact_source` and `contact_attributes`.
