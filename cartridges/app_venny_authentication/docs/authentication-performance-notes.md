# Authentication Performance Notes

The first successful `/sign-up` proved the cartridge works, but a 17-second Heroku service time is too slow for a browser-facing reference app.

## Changes in this patch

- Keeps `password_hash()` / `password_verify()` for user-created passwords.
- Moves high-entropy API keys, refresh tokens, and password reset secrets to fast SHA-256 token hashes through `VennyIO\Support\TokenHash`.
- Keeps backward compatibility with existing `password_hash()` token rows.
- Rehashes existing bcrypt API-key rows to SHA-256 after a successful authentication when `API_KEY_REHASH_FAST` is enabled.
- Throttles `keys.key_lastused` writes to once per key per 60 seconds by default.
- Adds `DB_CONNECT_TIMEOUT`, defaulting to `5`, to the Postgres DSN.

## Recommended Heroku config

```bash
heroku config:set DB_PERSISTENT=true -a app-withvenny-api
heroku config:set DB_CONNECT_TIMEOUT=5 -a app-withvenny-api
heroku config:set API_KEY_REHASH_FAST=true -a app-withvenny-api
heroku config:set API_KEY_LASTUSED_MIN_SECONDS=60 -a app-withvenny-api
```

## Test sequence

1. Deploy this patch.
2. Call `POST /sign-in` once with the existing API key. If that key was bcrypt-hashed, this first request may still be slower because it performs one last `password_verify()` before rehashing the key.
3. Call `POST /sign-in` again with the same API key. The second request should avoid bcrypt API-key verification.
4. Check database connection timing:

```bash
curl -s https://api.withvenny.app/db-health \
  -H "X-Setup-Token: $APP_SETUP_TOKEN"
```

If `timing_ms.connect` is still high, the remaining bottleneck is database connection latency, Heroku dyno/database wake-up behavior, or network path. If `connect` is low but auth still takes seconds, the remaining bottleneck is inside the auth workflow.
