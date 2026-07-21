-- app_venny_identity schema

-- ---------------------------------------------------------
				
CREATE TABLE IF NOT EXISTS persons (				
id	UUID		DEFAULT gen_random_uuid()	,
person_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
person_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
person_firstname	TEXT	NULL		, --First Name
person_middlename	TEXT	NULL		, --Middle Name
person_lastname	TEXT	NULL		, --Last Name
person_emails	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --Emails
person_phones	JSONB	NULL		, --Phones
person_addresses	JSONB	NULL		, --Addresses
person_dateofbirth	DATE	NULL		, --Date of Birth
person_smsoptindate	TIMESTAMPTZ	NULL		, --SMS Optin Date
person_source	TEXT	NOT NULL	DEFAULT 'website'	, --Source
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

-- ---------------------------------------------------------			

CREATE TABLE IF NOT EXISTS users (				
id	UUID		DEFAULT gen_random_uuid()	,
user_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
user_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
user_email	CITEXT	NOT NULL	UNIQUE	, --
user_addresses	JSONB	NULL	DEFAULT '{}'	, --
user_phones	JSONB	NULL	DEFAULT '{}'	, --
user_optins	JSONB	NULL	DEFAULT '{}'	, --
user_passwordhash	TEXT	NULL		, --
user_username	CITEXT	NULL	UNIQUE	, --
user_displayname	TEXT	NULL	DEFAULT ''	, --display name 
user_bio	TEXT	NULL	DEFAULT ''	, --bio are optional
user_avatarurl	TEXT	NULL	DEFAULT ''	, --avatar url 
user_theme	TEXT	NULL	DEFAULT 'salt'	, --
user_biopublished	BOOLEAN	NULL	DEFAULT TRUE	, --bio published 
user_lastlogin	TIMESTAMPTZ	NULL		, --last login 
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

-- ---------------------------------------------------------				
		
CREATE TABLE IF NOT EXISTS profiles (				
id	UUID		DEFAULT gen_random_uuid()	,
profile_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
profile_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
profile_username	CITEXT	NULL	UNIQUE	, --
profile_displayname	TEXT	NULL	DEFAULT ''	, --display name 
profile_bio	TEXT	NULL	DEFAULT ''	, --bio are optional
profile_avatarurl	TEXT	NULL	DEFAULT ''	, --avatar url 
profile_theme	TEXT	NULL	DEFAULT 'salt'	, --
profile_biopublished	BOOLEAN	NULL	DEFAULT TRUE	, --bio published 
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
