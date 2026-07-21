# Venny I/O Pagination Pattern

Pagination is owned by `app_venny_platform` because it is a cross-cutting API behavior shared by every cartridge.

## Location

```text
cartridges/app_venny_platform/src/Support/Pagination.php
cartridges/app_venny_platform/src/Controllers/PlatformResourceController.php
cartridges/app_venny_platform/src/Repositories/PlatformResourceRepository.php
```

Bespoke platform repositories also use the same helper:

```text
AppsRepository
KeysRepository
SessionsRepository
```

## Collection query parameters

Every table-backed collection endpoint should accept:

```text
page        default 1
per_page    default 25, max 100
sort        default time_started
direction   default desc; allowed asc or desc
```

Example:

```http
GET /contacts?page=1&per_page=25&sort=time_started&direction=desc
```

## Response shape

Collection endpoints return `items` plus `pagination` metadata inside the existing Venny I/O response envelope:

```json
{
  "status": "200",
  "success": true,
  "message": "contacts retrieved successfully",
  "data": {
    "items": [],
    "pagination": {
      "page": 1,
      "per_page": 25,
      "total": 0,
      "total_pages": 0,
      "has_next": false,
      "has_previous": false,
      "sort": "time_started",
      "direction": "desc"
    }
  }
}
```

## Cartridge rule

Cartridges should not implement their own pagination math. A cartridge may define resource-level options, but the platform must handle:

```text
page/per_page normalization
max per-page enforcement
limit/offset calculation
sort field allow-listing
direction normalization
pagination metadata
```

## Repository pattern

Repositories should execute one count query and one page query:

```sql
SELECT COUNT(*)::int AS total FROM contacts;

SELECT ...
FROM contacts
ORDER BY time_started DESC
LIMIT :limit OFFSET :offset;
```

`sort` and `direction` must come from the platform helper after allow-list validation. Do not concatenate raw user input into SQL.

## Cursor pagination later

Offset pagination is the default for now. Cursor pagination can be added later for high-volume endpoints such as:

```text
/messages
/posts
/transactions
```
