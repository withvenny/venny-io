# app_venny_relationships

`app_venny_relationships` provides relationship primitives already present in the Venny I/O schema.

This cartridge intentionally exposes only two endpoint families:

```text
GET     /followships
GET     /followships/:id
POST    /followships
PATCH   /followships/:id
DELETE  /followships/:id

GET     /groups
GET     /groups/:id
POST    /groups
PATCH   /groups/:id
DELETE  /groups/:id
```

## Boundary

This cartridge owns user-to-user relationship and grouping records:

```text
followships
groups
```

It does not expose a generic `/relationships` endpoint.
