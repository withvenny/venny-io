-- app_venny_communications schema

CREATE TABLE IF NOT EXISTS communications (				
id	UUID		DEFAULT gen_random_uuid()	,
communication_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
communication_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
communication_object_id	VARCHAR(64)	NOT NULL		, --object_id ID
communication_parentobject	VARCHAR(64)	NULL		, --parent object 
communication_template	TEXT	NOT NULL		, --template 
communication_initiatedby	VARCHAR(64)	NULL		, --initiated by 
communication_recipients	JSONB	NOT NULL		, --recipients 
communication_processed	TIMESTAMPTZ	NULL		, --processed means all delivery records are created. once processed this will change to the timestamp
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

CREATE TABLE IF NOT EXISTS deliveries (				
id	UUID		DEFAULT gen_random_uuid()	,
delivery_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
delivery_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
delivery_object_id	VARCHAR(64)	NOT NULL		, --object_id ID
delivery_parentobject	VARCHAR(64)	NULL		, --parent object 
delivery_communication	VARCHAR(64)	NOT NULL		, --communication 
delivery_channel	TEXT	NOT NULL		, --channel 
delivery_metadata	JSONB	NOT NULL		, --metadata 
delivery_sentat	TIMESTAMPTZ	NULL		, --sent at means all delivery records are created. once processed this will change to the timestamp
delivery_attempts	INT	NULL	DEFAULT 0	, --
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

CREATE TABLE IF NOT EXISTS threads (				
id	UUID		DEFAULT gen_random_uuid()	,
thread_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
thread_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
thread_subject	VARCHAR(255)	NOT NULL		, --subject ID
thread_participants	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --participants 
thread_lastmessagepreview	TEXT	NULL	DEFAULT ''	, --last message preview is where all recipients, template, subject, etc. will be retrieved from
thread_lastmessageat	TIMESTAMPTZ	NULL		, --last message at 
thread_author_id	VARCHAR(40)	NOT NULL		, --author_id is user who initiated thread
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

CREATE TABLE IF NOT EXISTS messages (				
id	UUID		DEFAULT gen_random_uuid()	,
message_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
message_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
thread_id	VARCHAR(64)	NOT NULL		, --thread id ID
message_sender_id	VARCHAR(64)	NOT NULL		, --sender_id ID
message_body	TEXT	NOT NULL		, --body ID
message_attachments	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --attachments 
message_readby	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --read by is where all recipients, template, subject, etc. will be retrieved from
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
