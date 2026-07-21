# Followships schema reference

The `followships` table stores user-to-user follow/request relationship records.

## Primary endpoint

```text
/followships
```

## Fields

| Field | Type | Required | Notes |
|---|---:|---:|---|
| followship_id | VARCHAR(64) | yes | Venny I/O generated followship identifier. |
| followship_attributes | JSONB | yes | Open JSON object for followship metadata. |
| followship_sender_id | VARCHAR(64) | yes | User initiating the relationship. |
| followship_recipient_id | VARCHAR(64) | yes | User receiving the relationship. |
| followship_status | VARCHAR(30) | yes | Relationship status, for example `requested`, `accepted`, `blocked`, `muted`. |
| created_for_app_id | VARCHAR(64) | yes | Owning app. |
| access | VARCHAR(30) | yes | Visibility. |
| status | VARCHAR(30) | yes | Record status. |
| active | INT | yes | `1` active, `0` archived. |

## Example create body

```json
{
  "followship_sender_id": "user_8301",
  "followship_recipient_id": "user_replace_me",
  "followship_status": "requested",
  "followship_attributes": {
    "source": "postman",
    "cartridge": "app_venny_relationships"
  },
  "created_for_app_id": "app_8301",
  "access": "private",
  "status": "active",
  "active": 1
}
```
