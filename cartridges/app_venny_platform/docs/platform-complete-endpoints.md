# app_venny_platform Endpoint Baseline

`app_venny_platform` owns the core runtime/admin primitives for Venny I/O. It should remain intentionally boring: auth, app/key management, cartridge discovery, install tracking, rate-limit windows, and system sessions.

## Endpoint families

```text
GET     /health
GET     /db-health

GET     /apps
GET     /apps/:id
POST    /apps
PATCH   /apps/:id
DELETE  /apps/:id

GET     /keys
GET     /keys/:id
POST    /keys
PATCH   /keys/:id
DELETE  /keys/:id

GET     /cartridges
GET     /cartridges/:name

GET     /installations
GET     /installations/:id
POST    /installations
PATCH   /installations/:id
DELETE  /installations/:id

GET     /steps
GET     /steps/:id
POST    /steps
PATCH   /steps/:id
DELETE  /steps/:id

GET     /windows
GET     /windows/:id
POST    /windows
PATCH   /windows/:id
DELETE  /windows/:id

GET     /sessions
GET     /sessions/:id
POST    /sessions
PATCH   /sessions/:id
DELETE  /sessions/:id
```

## Security notes

- All routes except `/health` require Bearer auth, except `/db-health`, which uses the setup token.
- `/keys` never returns `key_hash`.
- `/sessions` never returns `session_refreshtokenhash`.
- `POST /keys` returns the raw API key once.
- `POST /sessions` returns the raw refresh token once.
- DELETE routes archive records by setting `status = archived`, `active = 0`, and updating `time_updated`.

## Pattern notes

`/apps`, `/keys`, and `/sessions` are bespoke because they need special behavior.

`/installations`, `/steps`, and `/windows` use the shared platform resource pattern:

```text
PlatformResourceController
PlatformResourceRepository
```

`/cartridges` is manifest-backed, not table-backed.
