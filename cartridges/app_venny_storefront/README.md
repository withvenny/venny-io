# app_venny_storefront

`app_venny_storefront` owns the storefront catalog primitives for Venny I/O.

This cartridge is intentionally scoped to the schema-backed storefront tables only.

## Endpoints

```text
GET     /catalogs
GET     /catalogs/:id
POST    /catalogs
PATCH   /catalogs/:id
DELETE  /catalogs/:id

GET     /categories
GET     /categories/:id
POST    /categories
PATCH   /categories/:id
DELETE  /categories/:id

GET     /products
GET     /products/:id
POST    /products
PATCH   /products/:id
DELETE  /products/:id

GET     /items
GET     /items/:id
POST    /items
PATCH   /items/:id
DELETE  /items/:id
```

## Boundary

This cartridge owns catalogs, categories, products, and inventory/item records.
It does not own orders, transactions, coupons, customers, carts, payments, shipping, or fulfillment.
Those should remain separate commerce/order cartridges if and when needed.

`DELETE /:resource/:id` soft-archives records using the shared platform resource pattern.
