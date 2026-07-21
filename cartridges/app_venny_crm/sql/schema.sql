CREATE TABLE IF NOT EXISTS contacts (				
id	UUID		DEFAULT gen_random_uuid()	,
contact_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
contact_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
contact_firstname	TEXT	NULL		, --captures first name
contact_middlename	TEXT	NULL		, --captures middle name
contact_lastname	TEXT	NULL		, --captures last name
contact_emails	JSONB	NOT NULL		, --captures emails
contact_phones	JSONB	NULL		, --captures phones
contact_company_id	VARCHAR(64)	NULL		, --captures company_id
contact_source	JSONB	NULL		, --captures source
contact_title	TEXT	NULL		, --captures title
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
created_for_app_id	VARCHAR(64)	NOT NULL	DEFAULT 'app_8301'	, --which app is making the call to this record
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	 --time recard of updated if allowed to be updated
);

CREATE TABLE IF NOT EXISTS companies (				
id	UUID		DEFAULT gen_random_uuid()	,
company_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
company_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
company_name	TEXT	NOT NULL		, --captures name
company_website	TEXT	NULL		, --captures website
company_industry	TEXT	NULL		, --captures industry
company_phone	TEXT	NULL		, --captures phone
company_address_1	TEXT	NULL		, --captures address_1
company_address_2	TEXT	NULL		, --captures address_2
company_city	TEXT	NULL		, --captures city
company_state	TEXT	NULL		, --captures state
company_postalcode	TEXT	NULL		, --captures postal code
company_country	TEXT	NULL		, --captures country
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
created_for_app_id	VARCHAR(64)	NOT NULL	DEFAULT 'app_8301'	, --which app is making the call to this record
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	 --time recard of updated if allowed to be updated
);

CREATE TABLE IF NOT EXISTS deals (				
id	UUID		DEFAULT gen_random_uuid()	,
deal_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
deal_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
deal_contact_id	VARCHAR(64)	NOT NULL		, --contact_id
deal_pipeline_id	VARCHAR(64)	NULL		, --pipeline_id
deal_company_id	VARCHAR(64)	NULL		, --company_id
deal_stage_id	VARCHAR(64)	NULL		, --stage_id
deal_amount	NUMERIC(12,2)	NOT NULL		, --amount
deal_expectedclosedate	DATE	NOT NULL		, --captures expected close date
deal_status	TEXT	NULL		, --captures status
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
created_for_app_id	VARCHAR(64)	NOT NULL	DEFAULT 'app_8301'	, --which app is making the call to this record
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	 --time recard of updated if allowed to be updated		
);

CREATE TABLE IF NOT EXISTS pipelines (				
id	UUID		DEFAULT gen_random_uuid()	,
pipeline_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --id
pipeline_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --attributes
pipeline_name	VARCHAR(64)	NOT NULL		, --name
pipeline_description	VARCHAR(280)	NOT NULL		, --description
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
created_for_app_id	VARCHAR(64)	NOT NULL	DEFAULT 'app_8301'	, --which app is making the call to this record
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	 --time recard of updated if allowed to be updated
);

CREATE TABLE IF NOT EXISTS stages (				
id	UUID		DEFAULT gen_random_uuid()	,
stage_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
stage_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
stage_pipeline_id	VARCHAR(64)	NULL		, --used to capture future triggers and/or filters
stage_name	TEXT	NULL		, --used to capture future triggers and/or filters
stage_position	INT	NULL		, --captures position
stage_probability	INT	NULL		, --captures probability
stage_is_closed	BOOLEAN	NULL		, --captures is_closed
stage_is_won	BOOLEAN	NULL		, --captures is_won
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
created_for_app_id	VARCHAR(64)	NOT NULL	DEFAULT 'app_8301'	, --which app is making the call to this record
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	 --time recard of updated if allowed to be updated
);

CREATE TABLE IF NOT EXISTS activities (				
id	UUID		DEFAULT gen_random_uuid()	,
activity_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
activity_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
activity_related_id	VARCHAR(64)	NULL		, --related_id
activity_contact_id	VARCHAR(64)	NULL		, --contact_id
activity_company_id	VARCHAR(64)	NULL		, --company_id
activity_deal_id	VARCHAR(64)	NULL		, --deal_id
activity_related_type	TEXT	NULL		, --related_type
activity_activity_type	TEXT	NULL		, --captures activity_type
activity_subject	TEXT	NOT NULL		, --captures subject
activity_body	TEXT	NOT NULL		, --captures body
activity_occurred_at	TIMESTAMPTZ	NULL		, --captures occurred_at
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
created_for_app_id	VARCHAR(64)	NOT NULL	DEFAULT 'app_8301'	, --which app is making the call to this record
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	 --time recard of updated if allowed to be updated
);

CREATE TABLE IF NOT EXISTS tasks (				
id	UUID		DEFAULT gen_random_uuid()	,
task_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
task_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
task_assigned_to_user_id	VARCHAR(64)	NOT NULL		, --used to capture future triggers and/or filters
task_contact_id	VARCHAR(64)	NULL		, --used to capture future triggers and/or filters
task_deal_id	VARCHAR(64)	NULL		, --captures deal_id
task_company_id	VARCHAR(64)	NULL		, --captures company_id
task_title	TEXT	NOT NULL		, --used to capture future triggers and/or filters
task_description	TEXT	NOT NULL		, --captures description
task_due_at	TIMESTAMPTZ	NULL		, --captures due_at
task_completed_at	TIMESTAMPTZ	NULL		, --captures completed_at
task_priority	TEXT	NULL		, --captures priority
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
created_for_app_id	VARCHAR(64)	NOT NULL	DEFAULT 'app_8301'	, --which app is making the call to this record
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	 --time recard of updated if allowed to be updated
);

CREATE TABLE IF NOT EXISTS notes (				
id	UUID		DEFAULT gen_random_uuid()	,
note_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
note_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
note_company_id	VARCHAR(64)	NULL		, --company_id
note_contact_id	VARCHAR(64)	NULL		, --contact_id
note_deal_id	VARCHAR(64)	NULL		, --captures deal_id
note_body	TEXT	NOT NULL		, --body
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
created_for_app_id	VARCHAR(64)	NOT NULL	DEFAULT 'app_8301'	, --which app is making the call to this record
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW() --time recard of updated if allowed to be updated
);

CREATE TABLE IF NOT EXISTS tags (				
id	UUID		DEFAULT gen_random_uuid()	,
tag_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
tag_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
tag_related_id	VARCHAR(64)	NULL		, --related_id
tag_contact_id	VARCHAR(64)	NULL		, --contact_id
tag_company_id	VARCHAR(64)	NULL		, --company_id
tag_deal_id	VARCHAR(64)	NULL		, --deal_id
tag_name	VARCHAR(100)	NOT NULL		, --used to capture future triggers and/or filters
tag_type	VARCHAR(280)	NULL		,
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
created_for_app_id	VARCHAR(64)	NOT NULL	DEFAULT 'app_8301'	, --which app is making the call to this record
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	 --time recard of updated if allowed to be updated
);
