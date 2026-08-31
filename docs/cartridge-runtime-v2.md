# Venny I/O Cartridge Runtime 2.0

## Source of truth

The `/cartridges` filesystem is the only cartridge registry.

A directory is treated as an installed and enabled Venny I/O cartridge when it exists under `/cartridges` and contains a valid `cartridge.php` manifest. There is no `config/cartridges.php`, application activation list, or second cartridge registry.

## Startup contract

At application bootstrap Venny I/O reads every `/cartridges/*/cartridge.php` manifest to register cartridge-owned autoload roots. At HTTP runtime `CartridgeRegistry` discovers the same manifests, validates the contract, validates cartridge dependencies, resolves dependency order, and `CartridgeLoader` registers declared routes.

Cartridge dependency failures are fatal to the application runtime. A missing required cartridge or dependency cycle is not silently ignored.

## Canonical manifest keys

Every cartridge uses the same top-level keys:

- `manifest_version`
- `name`
- `type`
- `provider`
- `domain`
- `version`
- `description`
- `tool`
- `tool_url`
- `php`
- `requires`
- `dependencies`
- `configuration`
- `capabilities`
- `documentation`
- `routes`
- `sql`
- `business_manager`
- `autoload`

Values may be null or empty where a capability does not apply. Structural deviations are not required for application, integration, or Business Manager cartridges.

## Dependency model

`requires` contains dependencies on other Venny I/O cartridges. Venny resolves those dependencies before route loading.

`dependencies` declares runtime dependencies that are not Venny cartridges. The standard buckets are:

- `php_extensions`
- `composer`
- `npm`

Provider configuration keys belong in `configuration`; they are not cartridge dependencies.

## SQL ownership

SQL is declared in each cartridge manifest but is not executed automatically during HTTP startup. Business Manager remains the installation surface.

Business Manager installs application-cartridge SQL in three standard phases:

1. schema
2. constraints
3. indexes

Within each phase, dependency order is preserved. Installation remains idempotent and retains existing Business Manager diagnostic behavior.

## Cartridge states

For Runtime 2.0 there is no installed-but-disabled state.

- directory + valid manifest: installed and enabled
- directory absent: unavailable
- missing required cartridge: dependency failure
- missing provider configuration: installed, but provider capability may be unconfigured
- missing external runtime package: installed, but the affected provider capability is unavailable until its declared runtime dependency is present
