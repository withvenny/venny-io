# app_venny_identity Endpoints

`app_venny_identity` owns Venny I/O identity primitives that other domain cartridges can depend on.

## Endpoint families

```text
GET     /persons
GET     /persons/:id
POST    /persons
PATCH   /persons/:id
DELETE  /persons/:id

GET     /users
GET     /users/:id
POST    /users
PATCH   /users/:id
DELETE  /users/:id

GET     /profiles
GET     /profiles/:id
POST    /profiles
PATCH   /profiles/:id
DELETE  /profiles/:id
```

## Notes

- All endpoints require `Authorization: Bearer {{api_key}}`.
- Deletes are soft deletes: `status = archived`, `active = 0`.
- JSONB fields are returned as JSON objects, not escaped JSON strings.
- `users.user_passwordhash` is hidden from API responses.
- User login/auth endpoints are intentionally not included yet. This cartridge currently manages identity records only.
