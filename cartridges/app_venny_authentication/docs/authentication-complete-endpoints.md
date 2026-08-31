# Authentication Endpoints

Base URL: `https://api.withvenny.app`

All authentication endpoints require the application Bearer key:

```http
Authorization: Bearer {{api_key}}
Content-Type: application/json
```

The Bearer key scopes each request to the authenticated Venny I/O app/tenant.

## POST /sign-up

Creates a user in `users`, stores `user_passwordhash`, creates a session, and returns the raw refresh token once.

### Request

```json
{
  "user_email": "ada@example.com",
  "password": "{{user_password}}",
  "password_confirmation": "{{user_password}}",
  "user_username": "ada",
  "user_displayname": "Ada Lovelace",
  "user_attributes": {
    "source": "notio-reference-app"
  }
}
```

### Response

```json
{
  "status": "201",
  "success": true,
  "message": "signed up successfully",
  "data": {
    "user": {},
    "session": {},
    "raw_refresh_token": "venny_refresh_...",
    "warning": "Store this raw refresh token now. It is returned only once and cannot be retrieved later."
  }
}
```

## POST /sign-in

Validates email/password, updates `user_lastlogin`, creates a session, and returns the raw refresh token once.

### Request

```json
{
  "user_email": "ada@example.com",
  "password": "{{user_password}}"
}
```

## POST /sign-out

Revokes a session by setting `status = archived`, `active = 0`, and `session_revokedat = now()`.

### Request

```json
{
  "session_id": "session_REPLACE_ME",
  "user_id": "user_REPLACE_ME"
}
```

`user_id` is optional, but recommended.

## POST /request-password

Generates a password reset token, stores only the hashed secret in `users.user_attributes`, and returns a generic response.

This endpoint does not send email in v1. A future communications/integration cartridge should deliver the reset link.

To expose the reset token during local development only, set:

```text
AUTH_EXPOSE_RESET_TOKEN=true
```

### Request

```json
{
  "user_email": "ada@example.com"
}
```

## POST /reset-password

Validates the reset token, updates `user_passwordhash`, clears the reset token metadata, creates a new session, and returns the raw refresh token once.

### Request

```json
{
  "reset_token": "{{reset_token}}",
  "password": "{{new_password}}",
  "password_confirmation": "{{new_password}}"
}
```
