# /sessions Schema Reference

Table: `sessions`

`/sessions` is a platform/system session endpoint. User login/session behavior can move into `app_venny_identity` later.

Required create behavior:

```text
session_useragent defaults to request User-Agent or "unknown"
session_expiresat defaults to +30 days
session_refreshtokenhash is generated from a raw refresh token
```

Sensitive field:

```text
session_refreshtokenhash is never returned
```

Example POST:

```json
{
  "session_attributes": {
    "source": "postman"
  },
  "session_user_id": null,
  "session_expiresat": "+30 days",
  "access": "private",
  "status": "active",
  "active": 1
}
```

Response includes `raw_refresh_token` once. Store it immediately.
