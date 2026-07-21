# Reactions Endpoints

```text
GET     /acknowledgements
GET     /acknowledgements/:id
POST    /acknowledgements
PATCH   /acknowledgements/:id
DELETE  /acknowledgements/:id

GET     /comments
GET     /comments/:id
POST    /comments
PATCH   /comments/:id
DELETE  /comments/:id
```

## Domain ownership

`app_venny_reactions` owns acknowledgement and comment records only.

Use acknowledgements for lightweight reactions such as read, like, agree, approve, confirm, or dismiss.

Use comments for text responses attached to another object.
