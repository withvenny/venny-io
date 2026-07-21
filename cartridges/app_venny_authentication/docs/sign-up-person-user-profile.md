# Sign-up identity split

`POST /sign-up` creates an account in one transaction across three Identity tables:

- `persons` stores personal information: name, email, phone, and address.
- `users` stores login information: email, username, password hash, display name, opt-ins, and auth metadata.
- `profiles` stores social/public metadata: screen name, display name, bio, avatar URL, theme, and handles inside `profile_attributes.social`.

The response returns the created `person`, `user`, `profile`, and `session` records. Password hashes are never returned.

The UI should use `/account` endpoints after login so users are not exposed to table boundaries.
