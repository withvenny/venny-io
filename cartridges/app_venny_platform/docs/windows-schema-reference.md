# /windows Schema Reference

Table: `windows`

Required create fields:

```text
window_key_id
window_start
window_end
```

Common fields:

```text
window_id
window_attributes
window_key_id
window_start
window_end
window_count
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

Example POST:

```json
{
  "window_key_id": "key_...",
  "window_start": "2026-07-02T00:00:00Z",
  "window_end": "2026-07-02T00:01:00Z",
  "window_count": 0,
  "window_attributes": {
    "source": "postman"
  }
}
```
