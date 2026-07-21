# acknowledgements Schema Reference

## Required create fields

```text
acknowledgement_object_id
acknowledgement_type
```

## Common fields

```text
acknowledgement_id
acknowledgement_attributes
acknowledgement_object_id
acknowledgement_parent_object_id
acknowledgement_type
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
  "acknowledgement_object_id": "post_replace_me",
  "acknowledgement_parent_object_id": null,
  "acknowledgement_type": "like",
  "acknowledgement_attributes": {
    "source": "postman",
    "cartridge": "app_venny_reactions"
  },
  "created_for_app_id": "app_8301",
  "access": "private",
  "status": "active",
  "active": 1
}
```
