# Venny I/O Cartridge Runtime

Venny I/O discovers cartridges automatically from the `/cartridges` directory.

## Runtime behavior

1. Scan each direct child directory in `/cartridges`.
2. Read `manifest.json` when present.
3. Use `cartridge.php` only as a temporary backward-compatible manifest source.
4. Validate the cartridge manifest.
5. Resolve declared dependencies.
6. Load optional bootstrap files.
7. Register cartridge routes.
8. Write a best-effort discovery cache.

There is no cartridge disable state and no environment-variable exclusion list.
A standards-compliant cartridge present in `/cartridges` is part of the runtime.

If discovery, dependency resolution, bootstrap loading, or route registration fails,
the request fails and the error is logged. Installation recovery and partial-install
handling are intentionally outside this runtime version.

## Minimum manifest

```json
{
  "schema_version": "1.0",
  "name": "app_venny_example",
  "type": "app",
  "provider": "venny",
  "domain": "example",
  "version": "1.0.0",
  "priority": 500,
  "requires": [
    "app_venny_platform"
  ],
  "routes": "routes.php"
}
```
