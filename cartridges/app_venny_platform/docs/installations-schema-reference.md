# /installations Schema Reference

Table: `installations`

Required create fields:

```text
installation_experience
```

Common fields:

```text
installation_id
installation_attributes
installation_experience
installation_modules
installation_status
installation_started_at
installation_finished_at
installation_error
installation_summary
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
  "installation_experience": "CRM App",
  "installation_modules": ["app_venny_platform", "app_venny_identity", "app_venny_crm"],
  "installation_status": "pending",
  "installation_attributes": {
    "source": "business_manager"
  },
  "installation_summary": {}
}
```
