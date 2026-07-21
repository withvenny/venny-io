# app_venny_cms

Reusable Venny I/O CMS cartridge.

## Owns

```text
content
assets
```

## Endpoints

```text
GET     /content
GET     /content/:id
POST    /content
PATCH   /content/:id
DELETE  /content/:id

GET     /assets
GET     /assets/:id
POST    /assets
PATCH   /assets/:id
DELETE  /assets/:id
```

## Depends on

```text
app_venny_platform
```

`assets` lives here because content management needs media/document metadata as a first-class CMS concern. Actual file upload/presigned URL behavior can later be delegated to an integration cartridge such as `int_aws_s3`.
