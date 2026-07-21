# Venny I/O Cartridge Resource Endpoint Pattern

Use this skill when adding a CRUD-style endpoint family to a Venny I/O cartridge, especially `app_venny_[domain]` cartridges.

## Goal

Create endpoint families that follow the established `app_venny_platform` pattern:

```text
GET     /resources
GET     /resources/:id
POST    /resources
PATCH   /resources/:id
DELETE  /resources/:id
```

Use singular controller/repository names based on the resource family:

```text
AppsController        AppsRepository
KeysController        KeysRepository
ContactsController    ContactsRepository
```

## Required files

For a resource inside a cartridge:

```text
cartridges/[cartridge_name]/
  routes.php
  src/
    Controllers/[Resources]Controller.php
    Repositories/[Resources]Repository.php
  docs/[resources]-schema-reference.md
  postman/[resources].postman.json
```

## Routing pattern

Routes belong in the cartridge's `routes.php`, not in `public/index.php`.

```php
$makeResourcesController = static function (): ResourcesController {
    $db = Database::connection();
    ApiKeyAuth::require($db);

    return new ResourcesController(new ResourcesRepository($db));
};

$router->get('#^/resources$#', static function () use ($makeResourcesController): void {
    $makeResourcesController()->index();
});

$router->get('#^/resources/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeResourcesController): void {
    $makeResourcesController()->show($params['id']);
});

$router->post('#^/resources$#', static function (Request $request) use ($makeResourcesController): void {
    $makeResourcesController()->store($request);
});

$router->patch('#^/resources/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeResourcesController): void {
    $makeResourcesController()->update($params['id'], $request);
});

$router->delete('#^/resources/(?P<id>[^/]+)$#', static function (Request $request, array $params) use ($makeResourcesController): void {
    $makeResourcesController()->destroy($params['id']);
});
```

## Controller responsibilities

Controllers handle request behavior only:

- Read `$request->input()`.
- Clean and validate input.
- Generate IDs using `Ids::generate('[entity]')` where appropriate.
- Convert JSON object fields to JSON strings before repository calls.
- Convert active values to `0` or `1`.
- Catch expected database exceptions such as duplicate key or foreign key violations.
- Return standard `Response::json(...)` envelopes.

Controllers should not contain SQL.

## Repository responsibilities

Repositories handle SQL only:

- Define `SELECT_COLUMNS`.
- Exclude sensitive columns from `SELECT_COLUMNS`.
- Implement `all()`, `findBy[Id]()`, `create()`, `update()`, and `softDelete()`.
- Decode PostgreSQL `jsonb` response strings into PHP arrays/objects before returning rows.
- Never return sensitive columns such as password hashes, token hashes, or private secrets.

## Response envelope

Always use the Venny I/O response envelope:

```json
{
  "status": "200",
  "success": true,
  "message": "resource retrieved successfully",
  "data": {}
}
```

## JSONB response rule

PostgreSQL returns `jsonb` columns as JSON strings through PDO pgsql. Repositories must normalize JSONB values before returning API responses.

Example:

```php
if (isset($row['resource_attributes']) && is_string($row['resource_attributes'])) {
    $decoded = json_decode($row['resource_attributes'], true);
    $row['resource_attributes'] = is_array($decoded) && !array_is_list($decoded) ? $decoded : new \stdClass();
}
```

## DELETE behavior

Use soft delete/archive by default:

```text
status = archived
active = 0
time_updated = now()
```

Return messages such as:

```text
resource archived successfully
```

Avoid claiming a hard delete unless the row is actually removed.

## Security rule for token/key resources

For key-like resources:

- Return the raw secret once at creation time only.
- Store only a password hash or secure digest.
- Do not expose hashes in `GET`, `PATCH`, or `DELETE` responses.
- Use a dedicated rotate endpoint later instead of accepting secret/hash changes via `PATCH`.

## Documentation rule

Every endpoint family should include a schema reference doc covering:

- Endpoint family
- Owned table fields
- Required fields
- Defaults
- Sensitive fields
- Example request
- Notes for soft delete/archive behavior

## Postman rule

Every endpoint family should include a Postman snippet with:

- Bearer auth using `{{api_key}}`
- `{{base_url}}`
- A concrete example `:id`
- JSON body for POST/PATCH

## Generic platform resource option

For highly similar platform resources, it is acceptable to use the shared platform resource pattern instead of one controller/repository pair per table.

Use:

```text
PlatformResourceController
PlatformResourceRepository
```

when the resource is a straightforward table-backed CRUD family with:

- one primary ID column
- standard audit fields
- JSONB fields that can be normalized generically
- soft-delete behavior using `status`, `active`, and `time_updated`

Keep bespoke controllers for resources with secret generation, authentication, uploads, external calls, or special workflow behavior. Current examples:

```text
/apps         bespoke
/keys         bespoke because raw key is returned once
/sessions     bespoke because raw refresh token is returned once
/installations generic
/steps         generic
/windows       generic
```

A generic route should define a resource config in the cartridge routes file with:

```text
entity
primary_key
table
columns
json_fields
create rules
update rules
```

This keeps the endpoint family consistent while avoiding a pile of nearly identical controller and repository files.
