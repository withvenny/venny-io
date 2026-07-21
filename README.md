<p align="center">
  <img src="./docs/assets/logo-vennyio.png" alt="Venny I/O" width="420">
</p>

<p align="center">
  <strong>Build more. Build faster. Build consistently.</strong>
</p>

<p align="center">
  <img alt="PHP 8.2+" src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white">
  <img alt="PostgreSQL" src="https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql&logoColor=white">
  <img alt="Heroku" src="https://img.shields.io/badge/Heroku-24-430098?style=flat-square&logo=heroku&logoColor=white">
  <img alt="Architecture" src="https://img.shields.io/badge/Architecture-Cartridge--based-111827?style=flat-square">
</p>

# Venny I/O

Venny I/O is a modular, API-first application platform for building modern web products without rebuilding the same infrastructure for every project.

Instead of treating authentication, accounts, content, customer relationships, messaging, reactions, commerce, and other common capabilities as one tightly coupled application, Venny I/O packages them into self-contained **cartridges**.

A Venny I/O application is assembled from the cartridges it needs. Each cartridge owns its routes, controllers, business logic, SQL, documentation, and developer tooling. The runtime discovers those cartridges automatically, resolves their dependencies, and loads them in the correct order.

The result is a platform that is reusable without becoming rigid and extensible without requiring a central registry to be updated every time a new capability is introduced.

---

## Why Venny I/O Exists

Most web applications repeatedly require the same foundational capabilities:

- application and API-key management
- identity and user accounts
- authentication and sessions
- content and digital assets
- contacts, companies, tasks, and deals
- communication and notifications
- posts, relationships, and reactions
- storefront and commerce services
- installation, diagnostics, and developer tooling

Venny I/O turns those recurring capabilities into reusable platform components.

The guiding idea is simple:

> A developer should spend more time creating the product experience and less time rebuilding the platform beneath it.

---

## Core Principles

### Modular by default

Capabilities are separated into cartridges so each concern can evolve independently.

### Convention over manual registration

Cartridges follow a predictable structure and are discovered automatically from the filesystem.

### API-first

The platform is designed to support web applications, mobile applications, internal tools, and third-party integrations through consistent HTTP endpoints.

### Predictable responses

Endpoints return a common response envelope so frontend applications do not need to interpret a different response format for every feature.

### Secure foundations

Authentication, sessions, API keys, password handling, database access, and setup controls are treated as platform responsibilities rather than optional application details.

### Fail clearly

Invalid manifests, missing dependencies, circular dependencies, and missing required runtime files stop startup with actionable errors instead of silently producing a partially functioning application.

---

## Technology

Venny I/O currently uses:

- PHP 8.2+
- PostgreSQL
- Apache
- Heroku-24
- Composer
- a single public front controller
- cartridge-based domain modules

The standard API response shape is:

```json
{
  "status": 200,
  "success": true,
  "message": "Request completed successfully.",
  "data": {}
}
```

---

## Repository Structure

```text
.
├── README.md
├── Procfile
├── composer.json
├── composer.lock
├── config/
├── public/
│   └── index.php
├── src/
│   └── Kernel/
├── cartridges/
│   ├── app_venny_platform/
│   ├── app_venny_identity/
│   ├── app_venny_authentication/
│   ├── app_venny_account/
│   ├── app_venny_cms/
│   ├── app_venny_crm/
│   ├── app_venny_communications/
│   ├── app_venny_chat/
│   ├── app_venny_posts/
│   ├── app_venny_relationships/
│   ├── app_venny_reactions/
│   ├── app_venny_storefront/
│   ├── app_venny_commerce/
│   └── bm_venny_setupwizard/
├── sql/
├── scripts/
├── storage/
└── docs/
    └── assets/
```

---

# Cartridge Architecture

A cartridge is a self-contained application capability.

A typical cartridge looks like this:

```text
cartridges/app_venny_example/
├── manifest.json
├── cartridge.php
├── routes.php
├── src/
├── sql/
├── postman/
├── assets/
└── docs/
```

Not every cartridge must use every directory, but every cartridge must provide a valid `manifest.json`.

## Cartridge Manifest

The manifest is the contract between the cartridge and the Venny I/O runtime.

```json
{
  "schema_version": "1.0",
  "cartridge": "app_venny_crm",
  "name": "CRM",
  "version": "1.0.0",
  "dependencies": [
    "app_venny_platform",
    "app_venny_identity"
  ],
  "bootstrap": "cartridge.php",
  "routes": "routes.php"
}
```

The manifest describes the cartridge. It does not maintain a separate enabled or disabled runtime state.

If a valid cartridge is present in the application, Venny I/O attempts to resolve and load it.

## Automatic Discovery

Venny I/O does not require cartridges to be manually registered in a central configuration file.

At startup, the runtime:

1. scans the `/cartridges` directory
2. locates every `manifest.json`
3. validates each manifest
4. detects duplicate cartridge names
5. resolves dependencies
6. detects circular dependencies
7. determines the correct load order
8. loads cartridge bootstrap files
9. registers cartridge routes

The runtime does not use `VENNY_DISABLED_CARTRIDGES`, an installed-but-disabled state, or a separate application-level activation registry.

A standards-compliant cartridge is recognized because it exists and describes itself correctly.

## Dependency Resolution

Cartridges may depend on other cartridges.

For example:

```json
{
  "cartridge": "app_venny_authentication",
  "dependencies": [
    "app_venny_platform",
    "app_venny_identity"
  ]
}
```

The runtime resolves dependencies before loading routes or bootstrap files. This ensures that foundational capabilities are available before dependent cartridges start.

A typical load order is:

```text
app_venny_platform
app_venny_identity
app_venny_authentication
app_venny_account
app_venny_crm
app_venny_communications
```

If a cartridge cannot be resolved or loaded successfully, startup fails clearly. More advanced recovery and partial-installation patterns can be introduced separately without complicating the current runtime model.

## Runtime Cache

The runtime may cache the resolved cartridge map to reduce repeated filesystem work.

```text
storage/cache/cartridges.php
```

The cache is an optimization only. Cartridge manifests remain the source of truth.

---

# Security

Security is built into the platform architecture rather than added independently by each cartridge.

## API Keys

Applications authenticate to protected API endpoints with a Bearer token:

```http
Authorization: Bearer <api_key>
```

API keys are scoped to application access and validated before protected requests are processed.

## User Sessions

Authenticated users receive session identifiers that are separate from application API keys:

```http
X-Venny-Session-Id: <session_id>
```

This separation allows the platform to distinguish between the application making the request and the signed-in user acting within the application.

## Password Security

Passwords are never stored in plain text. Password creation and verification use PHP's native password-hashing functions.

## Database Access

Database access is handled through PDO and prepared statements. Request data is bound as query parameters rather than interpolated directly into SQL.

## Input Validation

Endpoints validate required values, data types, formats, and business constraints before executing application logic.

Validation failures return consistent, machine-readable responses.

## Setup Security

Business Manager setup is protected by a setup passphrase and setup-state controls. The setup surface is intended to be locked after application provisioning is complete.

## CORS

Allowed origins are explicitly configured. Browser preflight requests are handled separately from authenticated application requests.

## Fail-Fast Runtime Validation

The runtime refuses to continue when it detects:

- an invalid manifest
- a duplicate cartridge name
- a missing dependency
- a circular dependency
- a missing required bootstrap file
- a missing required routes file

This avoids silent, partially loaded applications that are difficult to diagnose and unsafe to operate.

---

# Business Manager

Business Manager is the administrative and provisioning interface for Venny I/O.

It begins as the setup experience used to prepare a new application and later becomes the operational control center for that application.

Business Manager is implemented as its own cartridge:

```text
bm_venny_setupwizard
```

## Initial Setup Responsibilities

Business Manager is designed to guide a developer through:

- verifying system readiness
- authorizing setup with a passphrase
- configuring the application
- preparing required environment values
- building platform tables
- choosing an application experience
- building experience-specific tables
- creating the first application record
- generating an API key
- downloading a Postman collection
- completing and locking setup

## Environment Configuration

Where direct configuration is not appropriate, Business Manager can generate the required Heroku CLI commands.

Examples include:

```bash
heroku config:set APP_ENV=production
heroku config:set CORS_ALLOWED_ORIGINS="https://www.example.com"
```

This keeps Heroku administrative credentials outside the running application.

## Experience Provisioning

Business Manager can present application experience presets such as:

- Content
- Membership
- CRM
- Social
- Commerce
- Custom

Each experience maps to a group of cartridges and required database structures.

## Developer Resources

Business Manager can provide:

- base API URL
- generated API credentials
- Postman collections
- endpoint inventories
- environment values
- readiness results
- installation progress
- diagnostics

## Future Direction

Business Manager is expected to grow into the operational interface for:

- cartridge-aware diagnostics
- schema updates
- release visibility
- developer documentation
- API-key rotation
- application settings
- database maintenance
- generated Postman and OpenAPI artifacts

---

# Available Cartridges

The following cartridges currently make up the Venny I/O platform.

## `app_venny_platform`

The foundational runtime and shared platform services.

Functions include:

- application primitives
- API-key authentication support
- database connections
- request routing
- response formatting
- pagination helpers
- ID generation
- tenant and application scoping
- cartridge discovery
- manifest validation
- dependency resolution
- cartridge loading
- route registration
- runtime diagnostics

All application cartridges ultimately depend on the platform cartridge.

## `app_venny_identity`

Identity and person records.

Functions include:

- persons
- users
- profiles
- user attributes
- identity lifecycle support
- relationships between login identities and personal records

## `app_venny_authentication`

Authentication and credential workflows.

Functions include:

- sign up
- sign in
- sign out
- password reset requests
- password reset completion
- password verification
- session creation
- session termination
- account bootstrap during registration

## `app_venny_account`

Authenticated account management.

Functions include:

- retrieve the current account
- update account information
- update passwords
- sign out the current account session
- combine person, user, profile, and session context for frontend applications

## `app_venny_cms`

Content and digital asset management.

Functions include:

- content records
- content lookup by ID
- content lookup by slug
- publishing status
- content metadata
- digital assets
- asset metadata
- application content delivery

## `app_venny_crm`

Customer relationship management.

Functions include:

- contacts
- companies
- tags
- notes
- tasks
- activities
- stages
- deals
- pipelines
- contact capture
- newsletter and update signups
- source attribution

## `app_venny_communications`

Application communication services.

Functions include:

- email delivery support
- SMS delivery support
- notification workflows
- provider abstraction
- communication metadata
- transactional message support

## `app_venny_chat`

Conversation and messaging capabilities.

Functions include:

- conversations
- conversation participants
- messages
- message history
- direct communication between application participants

## `app_venny_posts`

Application publishing and feed content.

Functions include:

- posts
- author relationships
- post retrieval
- post creation
- post updates
- post deletion
- feed-oriented content delivery

## `app_venny_relationships`

Relationships between people, accounts, or platform entities.

Functions include:

- following
- followers
- connections
- relationship creation
- relationship removal
- relationship status

## `app_venny_reactions`

Engagement with content and entities.

Functions include:

- likes
- favorites
- reactions
- reaction removal
- reaction totals
- user-to-content engagement

## `app_venny_storefront`

Storefront presentation and merchandising capabilities.

Functions include:

- storefront configuration
- product presentation
- catalog organization
- merchandising metadata
- storefront-facing product retrieval

## `app_venny_commerce`

Transactional commerce capabilities.

Functions include:

- carts
- cart items
- checkout workflows
- orders
- order items
- payment-related records
- transaction lifecycle support

## `bm_venny_setupwizard`

Business Manager and setup experience.

Functions include:

- setup authorization
- system readiness checks
- platform installation
- application configuration
- experience selection
- table provisioning
- developer-resource generation
- Postman collection generation
- setup completion
- operational diagnostics

---

# Naming Conventions

Venny I/O uses cartridge names that reveal the cartridge's role.

```text
app_<provider>_<domain>
```

Application capabilities:

```text
app_venny_crm
app_venny_cms
app_venny_account
```

Business Manager capabilities:

```text
bm_venny_setupwizard
```

Integration cartridges:

```text
int_<provider>_<system>
```

These conventions make cartridge intent recognizable without opening the source code.

---

# Creating a Cartridge

A new cartridge should:

1. use the appropriate naming convention
2. live directly inside `/cartridges`
3. include a valid `manifest.json`
4. declare all cartridge dependencies
5. keep domain logic inside its own namespace and directory
6. expose routes through its own `routes.php`
7. keep SQL and migrations with the cartridge
8. follow the standard Venny I/O response envelope
9. use platform security and database helpers
10. include documentation for its public endpoints

Minimal example:

```text
cartridges/app_venny_example/
├── manifest.json
├── routes.php
└── src/
    └── Controllers/
        └── ExampleController.php
```

Example `routes.php`:

```php
<?php

declare(strict_types=1);

use Venny\Kernel\Router;
use Venny\Example\Controllers\ExampleController;

return static function (Router $router): void {
    $router->get('/examples', [ExampleController::class, 'index']);
    $router->post('/examples', [ExampleController::class, 'create']);
};
```

Once the cartridge is present and its manifest is valid, the runtime discovers it automatically.

---

# Local Development

Install PHP dependencies:

```bash
composer install
```

Run the application's configured local server or Apache environment, then verify the health endpoint and cartridge discovery logs.

Before committing, remove operating-system artifacts:

```bash
find . -name ".DS_Store" -delete
rm -rf __MACOSX
```

Run PHP syntax checks across project files before deployment.

---

# Heroku Deployment

Initialize and commit the project:

```bash
git init
git add .
git commit -m "Initial Venny I/O installation"
```

Create the Heroku application and remote:

```bash
heroku create app-example-api
heroku git:remote -a app-example-api
```

Provision PostgreSQL:

```bash
heroku addons:create heroku-postgresql:essential-0
```

Deploy:

```bash
git push heroku master
```

Open the application:

```bash
heroku open
```

Use the included setup instructions and Business Manager to complete provisioning.

---

# Contributing

Contributions should preserve the consistency and modularity of the platform.

When contributing:

- keep cartridges self-contained
- avoid central registration lists
- declare dependencies explicitly
- reuse platform helpers before creating duplicate infrastructure
- use prepared statements for database operations
- validate request data
- maintain the standard response envelope
- preserve backward compatibility where practical
- include or update developer documentation
- test cartridge discovery and dependency resolution
- keep deployment artifacts free of local operating-system files

A contribution should make the platform easier to understand, safer to operate, or faster to extend.

---

# Contributors

Venny I/O is created and maintained by **[@sonofadolphus](https://github.com/sonofadolphus)**.

---

# License

Copyright © Venny I/O.

All rights reserved unless otherwise noted.

---

<p align="center">
  <img src="./docs/assets/logo-do-more-with-venny.png" alt="Do More With Venny" width="520">
</p>
