# app_venny_reactions

`app_venny_reactions` provides the lightweight reactions domain for Venny I/O.

## Boundary

This cartridge owns only the existing schema-backed reaction tables:

- `acknowledgements`
- `comments`

It does **not** create or expose a generic `/reactions` endpoint.

## Endpoints

```text
GET     /acknowledgements
GET     /acknowledgements/:id
POST    /acknowledgements
PATCH   /acknowledgements/:id
DELETE  /acknowledgements/:id

GET     /comments
GET     /comments/:id
POST    /comments
PATCH   /comments/:id
DELETE  /comments/:id
```

All endpoints require Bearer auth.

## Delete behavior

Deletes are soft archives: `status = archived`, `active = 0`.
