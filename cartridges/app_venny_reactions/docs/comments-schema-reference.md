# comments Schema Reference

## Required create fields

```text
comment_object_id
comment_body
```

## Common fields

```text
comment_id
comment_attributes
comment_object_id
comment_parent_object_id
comment_body
created_by_user_id
created_for_app_id
event_id
process_id
access
status
active
time_started
time_updated
```

## Example body

```json
{
  "comment_object_id": "post_replace_me",
  "comment_parent_object_id": null,
  "comment_body": "This is an example comment.",
  "comment_attributes": {
    "source": "postman",
    "cartridge": "app_venny_reactions"
  },
  "created_for_app_id": "app_8301",
  "access": "private",
  "status": "active",
  "active": 1
}
```
