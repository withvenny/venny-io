# app_venny_authentication

`app_venny_authentication` owns application-level authentication flows for Venny I/O.

This cartridge sits on top of `app_venny_identity` users and `app_venny_platform` sessions. It does not introduce new tables in v1.

## Endpoints

```text
POST /sign-up
POST /sign-in
POST /sign-out
POST /request-password
POST /reset-password
```

## Boundary

Authentication validates credentials, creates/revokes sessions, and manages password reset tokens. It does not own user profile content, app API keys, posts, communications delivery, or provider-specific email/SMS sending.

## Authentication model

Each endpoint still requires the Venny I/O app Bearer key. The Bearer key scopes the request to the active app/tenant. User sessions created here are application-user sessions, not replacement API keys.

## Password reset delivery

`POST /request-password` stores a hashed reset token in `users.user_attributes`. It intentionally does not send email. A future communication-provider cartridge should deliver the reset link.

For local development only, set:

```text
AUTH_EXPOSE_RESET_TOKEN=true
```

When enabled, the raw reset token is returned once in the response.


## Registration behavior

`POST /sign-up` now creates a `persons` record, a `users` record, a `profiles` record, and an active `sessions` record in one transaction. This keeps personal information, login credentials, and social/profile metadata separated while giving the UI one registration flow.
