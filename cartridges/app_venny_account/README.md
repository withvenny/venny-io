# app_venny_account

Authenticated account settings facade for Venny I/O.

## Endpoints

```text
GET   /account
PATCH /account
PATCH /account/password
POST  /account/sign-out
```

All endpoints require both:

```http
Authorization: Bearer {{api_key}}
X-Venny-Session-Id: {{session_id}}
```

The UI should call these account endpoints rather than directly updating `/persons`, `/users`, or `/profiles`.
