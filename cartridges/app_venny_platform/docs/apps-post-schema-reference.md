# POST /apps Schema Reference

Source schema: `io-venny-data.schema.v4.sql`, table `apps`.

## Required request fields

| Field | Type | Notes |
| --- | --- | --- |
| `app_name` | string | Required, nonblank. |
| `app_slug` | string | Required, normalized to lowercase slug. Unique in `apps.app_slug`. |
| `app_description` | string | Required, nonblank. |

## Optional request fields

| Field | DB Type | API Behavior |
| --- | --- | --- |
| `app_id` | `VARCHAR(64)` | Optional. Generated when omitted. Must match `app_[0-9A-Za-z]{4,64}` when provided. |
| `app_attributes` | `JSONB NOT NULL DEFAULT '{}'::jsonb` | Optional. Must be a JSON object. Defaults to `{}`. |
| `app_domain` | `TEXT NULL` | Optional. Empty string becomes `null`. |
| `app_website` | `TEXT NULL` | Optional. Empty string becomes `null`. |
| `app_contactname` | `TEXT NULL` | Optional. Empty string becomes `null`. |
| `app_contactemail` | `TEXT NULL` | Optional. Validated when provided. Empty string becomes `null`. |
| `app_contactphone` | `TEXT NULL` | Optional. Empty string becomes `null`. |
| `app_environment` | `VARCHAR(30) NOT NULL DEFAULT 'production'` | Optional. Defaults to `production`. |
| `app_type` | `VARCHAR(30) NOT NULL DEFAULT 'internal'` | Optional. Defaults to `internal`. |
| `created_by_user_id` | `VARCHAR(64) NOT NULL DEFAULT 'user_8301'` | Optional. Defaults to `user_8301`. |
| `event_id` | `VARCHAR(64) NOT NULL DEFAULT 'event_8301'` | Optional. Defaults to `event_8301`. |
| `process_id` | `VARCHAR(64) NOT NULL DEFAULT 'process_8301'` | Optional. Defaults to `process_8301`. |
| `access` | `VARCHAR(30) NOT NULL DEFAULT 'private'` | Optional. Defaults to `private`; must be nonblank. |
| `status` | `VARCHAR(30) NOT NULL DEFAULT 'active'` | Optional. Defaults to `active`; must be nonblank. |
| `active` | `INT NOT NULL DEFAULT 1` | Optional. Must resolve to `1` or `0`. |

## JSON example

```json
{
  "app_name": "Example App",
  "app_slug": "example-app",
  "app_description": "Example Venny I/O app created through POST /apps.",
  "app_attributes": {
    "source": "postman",
    "cartridge": "app_venny_platform"
  },
  "app_environment": "production",
  "app_type": "installed",
  "access": "private",
  "status": "active",
  "active": 1
}
```

## Curl example

```bash
curl -i "https://YOUR_HOST/apps" \
  -H "Authorization: Bearer YOUR_RAW_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "app_name": "Example App",
    "app_slug": "example-app",
    "app_description": "Example Venny I/O app created through POST /apps.",
    "app_attributes": {"source":"curl","cartridge":"app_venny_platform"},
    "app_environment": "production",
    "app_type": "installed"
  }'
```
