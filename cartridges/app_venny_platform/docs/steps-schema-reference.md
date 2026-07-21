# /steps Schema Reference

Table: `steps`

Required create fields:

```text
step_name
```

Common fields:

```text
step_id
step_attributes
step_name
step_order
step_status
step_sql_hash
step_started_at
step_finished_at
step_error
step_summary
created_by_user_id
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
  "step_name": "Create platform tables",
  "step_order": 10,
  "step_status": "pending",
  "step_attributes": {
    "cartridge": "app_venny_platform"
  },
  "step_summary": {}
}
```
