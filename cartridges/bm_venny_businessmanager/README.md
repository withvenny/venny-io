# Venny I/O Business Manager

`bm_venny_businessmanager` is the administrative control surface for a Venny I/O installation.

## Manifest

Business Manager v2.0.3 follows the Venny I/O Cartridge Runtime 2.0 `cartridge.php` contract. The filesystem is the cartridge registry; `config/cartridges.php` is not used.

## Access

The entire Business Manager browser experience is passphrase protected.

Configure the environment key:

`v_BUSINESS_MANAGER_PASSPHRASE`

The value must contain exactly five whitespace-separated words. Each word may contain no more than five characters.

The passphrase is never rendered in the Business Manager UI. Successful entry creates an HTTP-only, SameSite=Strict browser-session access cookie scoped to `/business-manager`. The cookie is signed from the configured passphrase, so changing the passphrase invalidates existing Business Manager access grants.

Use the **Lock** control to clear the current browser's access grant.

## Routes

- `/business-manager/login.html`
- `/business-manager/welcome.html`
- `/business-manager/environment.html`
- `/business-manager/environment/infrastructure.html`
- `/business-manager/environment/variables.html`
- `/business-manager/application.html`
- `/business-manager/application/data.html`
- `/business-manager/application/data/install.html` (POST)
- `/business-manager/application/cartridges.html`

Business Manager pages intentionally use `.html` browser routes even though the server implementation is PHP.

## Database setup

`Application > Data` can initialize the PostgreSQL database directly through the configured `DATABASE_URL`.

The install plan is generated from the existing `sql` block in installed application cartridge manifests. Standard SQL roles run in this order:

1. every `schema` file
2. every `constraints` file
3. every `indexes` file

Within each phase, cartridges are ordered from the least dependent to the most dependent using the manifest `requires[]` graph. Dependency cycles block installation.

Database setup is idempotent. Existing tables, indexes, and constraints are skipped and installation continues. Business Manager normalizes ordinary `CREATE TABLE` and `CREATE INDEX` statements to use `IF NOT EXISTS`; constraints use Venny's guarded constraint helper/current constraint guards. PostgreSQL duplicate-relation and duplicate-object outcomes are recorded as `skipped` rather than failed. Non-duplicate SQL failures still stop installation.

Once the platform `installations` and `steps` tables exist, Business Manager records the installation and each SQL step there, including the SHA-256 hash of the source SQL file. An advisory PostgreSQL lock prevents two Business Manager database installs from running concurrently.

The installer contains compatibility handling for the current constraint packages, which use the shared `venny_add_constraint` helper and contain legacy terminal `COMMIT` / helper-drop statements in aggregate constraint files.

Business Manager does not special-case or rewrite `app_venny_platform` ownership. The platform cartridge should ultimately contain only platform-owned SQL; reducing the current aggregate platform SQL is a separate cartridge cleanup.

`Reset` remains disabled until backup and reset semantics are implemented.

## Cartridge view

`Application > Cartridges` renders installed cartridge manifests as a collapsed accordion. Each cartridge summary shows only its identity, version, and status. Expanding a cartridge reveals provider, routes, manifest path, dependencies, and SQL declarations.

## Variables

`Environment > Variables` always renders these critical project keys first:

- `DATABASE_URL`
- `v_SITE_NAME`
- `v_SITE_DESCRIPTION`
- `v_SITE_LANGUAGE`
- `v_SITE_LOCALE`
- `v_SITE_CURRENCY`
- `v_SITE_SAFE_URLS`

Each row shows whether the key is **Set** or **Not set** without rendering its current value. Additional Venny-created environment keys using the `v_` prefix are listed afterward. PHP, Heroku runtime, Composer, and buildpack variables are omitted from this management screen.


## Database installation diagnostics

Business Manager records database install steps in the Venny installation ledger. Comment-only SQL files are treated as no-op steps and recorded as skipped. Failed executable SQL steps persist SQLSTATE, driver details, checksums, dependency depth, timing, rollback state, and a SQL excerpt when PostgreSQL reports a line number so the Data screen can support root-cause analysis.


## v2.0.2 passphrase UI

The access gate presents the five-word passphrase as five separate password fields. Each field accepts up to five characters. The POST handler joins the five values with spaces and passes the resulting string through the existing BusinessManagerAccess verification path. The legacy single `passphrase` POST field remains accepted for backward compatibility.

## v2.0.3 passphrase field refinement

Removed visible placeholder text from the five passphrase fields. Accessibility labels remain in place, so the fields stay distinguishable to assistive technology without adding visual text inside the inputs.
