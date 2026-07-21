# /users schema reference

Backed by `users`.

Required on create:

```text
user_email
```

Important response behavior:

```text
user_passwordhash is never returned by API responses.
```

Useful fields:

```text
user_email
user_username
user_displayname
user_bio
user_avatarurl
user_theme
user_biopublished
user_lastlogin
user_addresses
user_phones
user_optins
created_for_app_id
access
status
active
```
