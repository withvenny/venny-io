# Groups schema reference

The `groups` table stores named group relationship containers.

## Primary endpoint

```text
/groups
```

## Fields

| Field | Type | Required | Notes |
|---|---:|---:|---|
| group_id | VARCHAR(64) | yes | Venny I/O generated group identifier. |
| group_attributes | JSONB | yes | Open JSON object for group metadata. |
| group_sender_id | VARCHAR(64) | yes | User creating or inviting. |
| group_recipient_id | VARCHAR(64) | yes | Primary recipient or counterpart. |
| group_title | VARCHAR(100) | yes | Group title. |
| group_headline | VARCHAR(280) | no | Optional short description/headline. |
| group_access | VARCHAR(30) | yes | Group-level visibility, defaults `private`. |
| group_participants | JSONB | yes | Participant map/object. |
| group_images | JSONB | no | Optional image metadata object. |
| created_for_app_id | VARCHAR(64) | yes | Owning app. |
| access | VARCHAR(30) | yes | Record visibility. |
| status | VARCHAR(30) | yes | Record status. |
| active | INT | yes | `1` active, `0` archived. |

## Example create body

```json
{
  "group_sender_id": "user_8301",
  "group_recipient_id": "user_replace_me",
  "group_title": "Founders Circle",
  "group_headline": "Private relationship group.",
  "group_access": "private",
  "group_participants": {
    "users": ["user_8301", "user_replace_me"]
  },
  "group_images": {
    "avatar_asset_id": null,
    "cover_asset_id": null
  },
  "group_attributes": {
    "source": "postman",
    "cartridge": "app_venny_relationships"
  },
  "created_for_app_id": "app_8301",
  "access": "private",
  "status": "active",
  "active": 1
}
```
