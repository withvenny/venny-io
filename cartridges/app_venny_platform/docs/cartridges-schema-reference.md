# /cartridges Reference

`/cartridges` is manifest-backed, not table-backed.

It reads each cartridge's `cartridge.php` manifest and returns:

```text
name
type
provider
domain
version
requires
enabled
routes
sql
```

Supported routes:

```text
GET /cartridges
GET /cartridges/:name
```

Example:

```text
GET /cartridges/app_venny_platform
```
