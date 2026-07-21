# app_venny_commerce

`app_venny_commerce` owns the transactional commerce primitives for Venny I/O.

This cartridge is intentionally scoped to the schema-backed commerce tables only.

## Endpoints

```text
GET     /transactions
GET     /transactions/:id
POST    /transactions
PATCH   /transactions/:id
DELETE  /transactions/:id

GET     /orders
GET     /orders/:id
POST    /orders
PATCH   /orders/:id
DELETE  /orders/:id

GET     /coupons
GET     /coupons/:id
POST    /coupons
PATCH   /coupons/:id
DELETE  /coupons/:id

GET     /customers
GET     /customers/:id
POST    /customers
PATCH   /customers/:id
DELETE  /customers/:id
```

## Boundary

This cartridge owns transactions, orders, coupons, and commerce customers.
It does not own storefront catalog structure, product inventory, content, assets, or provider-specific payment integrations.

Provider-specific payment behavior should move through integration cartridges later, for example `int_stripe`.

`DELETE /:resource/:id` soft-archives records using the shared platform resource pattern.
