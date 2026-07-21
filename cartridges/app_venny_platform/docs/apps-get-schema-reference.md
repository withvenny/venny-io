# app_venny_platform — Apps GET endpoints

## GET /apps

Returns all apps ordered by `time_started DESC`.

## GET /apps/:id

Returns one app by concrete `apps.app_id`. Example:

```http
GET /apps/app_8301
Authorization: Bearer {{api_key}}
```

The `:id` notation is for Postman/API documentation. The actual request path must contain the app ID, such as `/apps/app_8301`.

## JSONB response handling

`apps.app_attributes` is stored as Postgres `JSONB`. PDO returns JSONB as a JSON string, so the repository decodes it before sending the API response. Clients should now receive:

```json
"app_attributes": {
  "source": "postman",
  "cartridge": "app_venny_platform"
}
```

not:

```json
"app_attributes": "{\"source\": \"postman\"}"
```
