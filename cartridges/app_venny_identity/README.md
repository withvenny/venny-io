# app_venny_identity

Venny I/O identity cartridge.

## Owns

- `persons`
- `users`
- `profiles`

## Routes

- `/persons`
- `/users`
- `/profiles`

Each route family supports:

```text
GET     /resource
GET     /resource/:id
POST    /resource
PATCH   /resource/:id
DELETE  /resource/:id
```

Deletes are soft archives.
