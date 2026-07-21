# app_venny_cms endpoints

The `app_venny_cms` cartridge owns content and content-related assets.

## Content

```text
GET     /content
GET     /content/:id
POST    /content
PATCH   /content/:id
DELETE  /content/:id
```

## Assets

```text
GET     /assets
GET     /assets/:id
POST    /assets
PATCH   /assets/:id
DELETE  /assets/:id
```

## Notes

- All CMS endpoints require Bearer auth.
- `content_attributes`, `content_tags`, and `asset_attributes` are returned as JSON, not escaped strings.
- Deletes are soft archives.
- Binary upload/presigned URL handling is intentionally not included yet; this cartridge currently stores asset metadata.
