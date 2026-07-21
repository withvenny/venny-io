-- app_venny_crm indexes

CREATE INDEX IF NOT EXISTS idx_contacts_app_name
ON contacts (created_for_app_id, contact_lastname, contact_firstname, active, status);

CREATE INDEX IF NOT EXISTS idx_contacts_company_app
ON contacts (contact_company_id, created_for_app_id, active, status)
WHERE contact_company_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_contacts_emails_gin
ON contacts USING GIN (contact_emails);

CREATE INDEX IF NOT EXISTS idx_contacts_phones_gin
ON contacts USING GIN (contact_phones)
WHERE contact_phones IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_companies_app_name
ON companies (created_for_app_id, company_name, active, status);

CREATE INDEX IF NOT EXISTS idx_companies_app_industry_state
ON companies (created_for_app_id, company_industry, company_state, active, status);

CREATE INDEX IF NOT EXISTS idx_deals_app_pipeline_stage_close
ON deals (created_for_app_id, deal_pipeline_id, deal_stage_id, deal_expectedclosedate, active, status);

CREATE INDEX IF NOT EXISTS idx_deals_contact_app
ON deals (deal_contact_id, created_for_app_id, active, status)
WHERE deal_contact_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_deals_company_app
ON deals (deal_company_id, created_for_app_id, active, status)
WHERE deal_company_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_deals_app_status_amount
ON deals (created_for_app_id, deal_status, deal_amount DESC, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_pipelines_app_name
ON pipelines (created_for_app_id, pipeline_name, active, status);

CREATE INDEX IF NOT EXISTS idx_stages_pipeline_position
ON stages (stage_pipeline_id, stage_position, active, status);

CREATE INDEX IF NOT EXISTS idx_stages_app_closed_won
ON stages (created_for_app_id, stage_is_closed, stage_is_won, active, status);

CREATE INDEX IF NOT EXISTS idx_activities_related_timeline
ON activities (activity_related_type, activity_related_id, activity_occurred_at DESC, time_started DESC)
WHERE activity_related_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_activities_contact_time
ON activities (activity_contact_id, activity_occurred_at DESC)
WHERE activity_contact_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_activities_company_time
ON activities (activity_company_id, activity_occurred_at DESC)
WHERE activity_company_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_activities_deal_time
ON activities (activity_deal_id, activity_occurred_at DESC)
WHERE activity_deal_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_tasks_assignee_due_open
ON tasks (task_assigned_to_user_id, task_due_at, task_priority, created_for_app_id)
WHERE task_completed_at IS NULL AND active = 1;

CREATE INDEX IF NOT EXISTS idx_tasks_contact_due
ON tasks (task_contact_id, task_due_at)
WHERE task_contact_id IS NOT NULL AND task_completed_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_tasks_company_due
ON tasks (task_company_id, task_due_at)
WHERE task_company_id IS NOT NULL AND task_completed_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_tasks_deal_due
ON tasks (task_deal_id, task_due_at)
WHERE task_deal_id IS NOT NULL AND task_completed_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_notes_contact_time
ON notes (note_contact_id, time_started DESC)
WHERE note_contact_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_notes_company_time
ON notes (note_company_id, time_started DESC)
WHERE note_company_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_notes_deal_time
ON notes (note_deal_id, time_started DESC)
WHERE note_deal_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_tags_app_name_type
ON tags (created_for_app_id, tag_name, tag_type, active, status);

CREATE INDEX IF NOT EXISTS idx_tags_related_lookup
ON tags (tag_related_id, tag_type, created_for_app_id)
WHERE tag_related_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_tags_contact_lookup
ON tags (tag_contact_id, tag_name, created_for_app_id)
WHERE tag_contact_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_tags_company_lookup
ON tags (tag_company_id, tag_name, created_for_app_id)
WHERE tag_company_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_tags_deal_lookup
ON tags (tag_deal_id, tag_name, created_for_app_id)
WHERE tag_deal_id IS NOT NULL;
