# `assets` schema reference

`app_venny_cms` owns the `assets` table because CMS content normally needs images, documents, media files, and other addressable storage objects.

## Required fields on `POST /assets`

```json
{
  "asset_object_type": "content",
  "asset_originalfilename": "hero-image.png",
  "asset_appslug": "withvenny",
  "asset_key": "apps/withvenny/content/hero-image.png",
  "asset_etag": "example-etag"
}
```

## Common optional fields

```json
{
  "asset_attributes": {
    "alt": "Hero image",
    "caption": "Homepage hero"
  },
  "asset_object_id": "content_...",
  "asset_displayname": "Hero Image",
  "asset_storageprovider": "s3",
  "asset_bucket": "io-venny-assets",
  "asset_region": "us-east-2",
  "asset_mimetype": "image/png",
  "asset_extension": "png",
  "asset_size_bytes": 248100,
  "asset_category": "image",
  "asset_purpose": "hero",
  "asset_visibility": "public"
}
```

## Notes

- `asset_attributes` is returned as a JSON object, not escaped JSON text.
- `DELETE /assets/:id` soft-archives the asset by setting `status = archived` and `active = 0`.
- `asset_key` should be the storage path/key, not a public URL.
- Actual binary upload/presigned URL behavior should be added later as a dedicated storage/integration flow.
