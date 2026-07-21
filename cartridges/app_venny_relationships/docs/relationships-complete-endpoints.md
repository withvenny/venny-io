# app_venny_relationships endpoints

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

`app_venny_relationships` owns `followships` and `groups` only. Generic object-to-object relationships should not be exposed from this cartridge unless a real schema table exists for that purpose.
