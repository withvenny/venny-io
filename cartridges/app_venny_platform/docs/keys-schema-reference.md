# `POST /keys` schema reference

Cartridge: `app_venny_platform`

Endpoint family:

```text
GET     /keys
GET     /keys/:id
POST    /keys
PATCH   /keys/:id
DELETE  /keys/:id
```

## Security behavior

`POST /keys` generates a raw Bearer token and stores only the password hash in `keys.key_hash`.

The raw key is returned **one time only** in the `POST /keys` response:

```json
{
  "raw_key": "venny_live_...",
  "warning": "Store this raw key now. It is returned only once and cannot be retrieved later.",
  "key": {}
}
```

`GET /keys`, `GET /keys/:id`, `PATCH /keys/:id`, and `DELETE /keys/:id` must never expose `key_hash` or the full raw key.

## `keys` table fields used

| Column | API behavior |
|---|---|
| `key_id` | Optional on create. Generated when omitted. |
| `key_attributes` | Optional JSON object. Defaults to `{}`. |
| `key_name` | Required on create. |
| `key_prefix` | Generated from the first 20 characters of the raw key. Not client-settable. |
| `key_hash` | Generated using `password_hash`. Not returned by API. |
| `key_ratelimit` | Optional positive integer. Defaults to `1000`. |
| `key_windowsize` | Optional positive integer. Defaults to `60`. |
| `key_lastused` | Read-only for this endpoint family. Updated by auth later. |
| `key_expires` | Optional date/time string or `null`. |
| `created_by_user_id` | Optional. Defaults to `user_8301`. |
| `created_for_app_id` | Optional. Defaults to `app_8301`. Must reference an existing app. |
| `event_id` | Optional. Defaults to `event_8301`. |
| `process_id` | Optional. Defaults to `process_8301`. |
| `access` | Optional nonblank string. Defaults to `private`. |
| `status` | Optional nonblank string. Defaults to `active`. |
| `active` | Optional `true`, `false`, `1`, or `0`. Defaults to `1`. |

## Example request

```json
{
  "key_name": "Postman Dev Key",
  "key_attributes": {
    "source": "postman",
    "cartridge": "app_venny_platform"
  },
  "key_ratelimit": 1000,
  "key_windowsize": 60,
  "key_expires": null,
  "created_for_app_id": "app_8301",
  "access": "private",
  "status": "active",
  "active": 1
}
```

## Notes

- `DELETE /keys/:id` archives the key by setting `status = archived` and `active = 0`.
- Key rotation should be introduced later as a dedicated action, for example `POST /keys/:id/rotate`, instead of allowing `PATCH /keys/:id` to overwrite `key_hash`.
