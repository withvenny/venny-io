# app_venny_posts

`app_venny_posts` owns the basic post/feed primitive for Venny I/O.

This cartridge is intentionally narrow. It exposes only the `/posts` endpoint family.

## Endpoints

```text
GET     /posts
GET     /posts/:id
POST    /posts
PATCH   /posts/:id
DELETE  /posts/:id
```

## Boundary

This cartridge does not own comments, reactions, followships, groups, acknowledgements, or chat. Those should remain separate cartridges or future expansions only when the product requires them.

`DELETE /posts/:id` soft-archives a post using the shared platform resource pattern.
