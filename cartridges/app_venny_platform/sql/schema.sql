CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- ---------------------------------------------------------
-- Venny I/O table: Installations
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS installations (				
id	UUID		DEFAULT gen_random_uuid()	,
installation_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
installation_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
installation_experience	VARCHAR(80)	NOT NULL		, --experience
installation_modules	JSONB	NOT NULL	DEFAULT '[]'::jsonb	, --modules
installation_status	VARCHAR(30)	NOT NULL	DEFAULT 'pending'	, --status
installation_started_at	TIMESTAMPTZ	NOT NULL	DEFAULT now()	, --started_at
installation_finished_at	TIMESTAMPTZ	NULL		, --finished_at
installation_error	TEXT	NULL		, --error
installation_summary	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --summary
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	 --time recard of updated if allowed to be updated
);
				
--SELECT * FROM	installations;		
--drop table 	installations;





































-- ---------------------------------------------------------
-- Venny I/O table: Steps
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS steps (				
id	UUID		DEFAULT gen_random_uuid()	,
step_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
step_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
step_name	VARCHAR(120)	NOT NULL		, --name
step_order	INT	NOT NULL	DEFAULT 0	, --order
step_status	VARCHAR(30)	NOT NULL	DEFAULT 'pending'	, --status
step_sql_hash	TEXT	NULL		, --sql_hash
step_started_at	TIMESTAMPTZ	NOT NULL	DEFAULT now()	, --started_at
step_finished_at	TIMESTAMPTZ	NULL		, --finished_at
step_error	TEXT	NULL		, --error
step_summary	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --summary
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	 --time recard of updated if allowed to be updated
);				
				
--SELECT * FROM	steps;			
--drop TABLE	steps;		






























































-- ---------------------------------------------------------
-- Venny I/O table: Apps
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS apps (				
id	UUID		DEFAULT gen_random_uuid()	,
app_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
app_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
app_name	TEXT	NOT NULL		, --name
app_slug	TEXT	NOT NULL	UNIQUE	, --slug
app_description	TEXT	NOT NULL		, --description
app_domain	TEXT	NULL		, --domain
app_website	TEXT	NULL		, --website
app_contactname	TEXT	NULL		, --contact name
app_contactemail	TEXT	NULL		, --contact email
app_contactphone	TEXT	NULL		, --contact phone
app_environment	VARCHAR(30)	NOT NULL	DEFAULT 'production'	, --
app_type	VARCHAR(30)	NOT NULL	DEFAULT 'internal'	, --
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	 --time recard of updated if allowed to be updated
);
				
--SELECT * FROM	apps;
--DROP table apps;


























-- ---------------------------------------------------------
-- Venny I/O table: Keys
-- ---------------------------------------------------------		
				
CREATE TABLE IF NOT EXISTS keys (				
id	UUID		DEFAULT gen_random_uuid()	,
key_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
key_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
key_name	TEXT	NOT NULL		, --name
key_prefix	VARCHAR(20)	NOT NULL	DEFAULT 'key_'	, --prefix
key_hash	TEXT	NOT NULL	UNIQUE	, --hash
key_ratelimit	INT	NOT NULL	DEFAULT 60	, --rate limit
key_windowsize	INT	NOT NULL	DEFAULT 60	, --window size
key_lastused	TIMESTAMPTZ	NULL		, --last used
key_expires	TIMESTAMPTZ	NULL		, --expires
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
				
--SELECT * FROM	keys;
--DROP table keys;
























-- ---------------------------------------------------------
-- Venny I/O table: Sessions
-- ---------------------------------------------------------			
				
CREATE TABLE IF NOT EXISTS sessions (				
id	UUID		DEFAULT gen_random_uuid()	,
session_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
session_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
session_refreshtokenhash	TEXT	NOT NULL	UNIQUE	, --refresh token hash
session_ipaddresshash	TEXT	NULL		, --ip address hash Raw IP is personal data. Treat it like it can show up in discovery, because someday it might.
session_ipcountryhash	TEXT	NULL		, --ip country hash Raw IP is personal data. Treat it like it can show up in discovery, because someday it might.
session_user_id	VARCHAR(64)	NULL		, --user_id
session_useragent	TEXT	NOT NULL		, --user agent
session_expiresat	TIMESTAMPTZ	NOT NULL		, --expires at
session_revokedat	TIMESTAMPTZ	NULL		, --revoked at
session_createdat	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --created at
session_lastseenat	TIMESTAMPTZ	NULL		, --last seen at
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
				
--SELECT * FROM	sessions;
--DROP table sessions;























-- ---------------------------------------------------------
-- Venny I/O table: Windows
-- ---------------------------------------------------------			
				
CREATE TABLE IF NOT EXISTS windows (				
id	UUID		DEFAULT gen_random_uuid()	,
window_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
window_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
window_key_id	VARCHAR(64)	NOT NULL	, --key_id
window_start	TIMESTAMPTZ	NOT NULL		, --start
window_end	TIMESTAMPTZ	NOT NULL		, --end
window_count	INT	NOT NULL	DEFAULT 0	, --count
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
				
--SELECT * FROM	windows;			
--DROP TABLE	windows;






















-- ---------------------------------------------------------
-- Venny I/O table: Persons
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
				
--SELECT * FROM	persons;			
--DROP TABLE	persons;























-- ---------------------------------------------------------
-- Venny I/O table: Users
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

--SELECT * from users;	
--DROP table users;






















-- ---------------------------------------------------------
-- Venny I/O table: Profiles
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
				
--SELECT * FROM	profiles;
--DROP TABLE	profiles;			






















-- ---------------------------------------------------------
-- Venny I/O table: Assets
-- ---------------------------------------------------------			
				
CREATE TABLE IF NOT EXISTS assets (				
id	UUID		DEFAULT gen_random_uuid()	,
asset_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
asset_attributes	JSONB	not NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
asset_object_id	VARCHAR(64)	NULL		, --object_id
asset_object_type	TEXT	NOT NULL		, --object_type
asset_originalfilename	TEXT	NOT NULL		, --original filename
asset_displayname	TEXT	NULL		, --display name
asset_storageprovider	VARCHAR(30)	NOT NULL	DEFAULT 's3'	, --s3, r2, local, etc.
asset_bucket	TEXT	NOT NULL	DEFAULT 'io-venny-assets'	, --bucket
asset_region	VARCHAR(50)	NOT NULL	DEFAULT 'us-east-2'	, --AWS region
asset_appslug	TEXT	NOT NULL		, --example: wealthonce; used to enforce apps/{asset_app_slug}/ path
asset_key	TEXT	NOT NULL		, --key
asset_etag	TEXT	NOT NULL		, --etag
asset_checksum_sha265	TEXT	NULL		, --checksum_sha265
asset_mimetype	TEXT	NOT NULL	DEFAULT 'text/html'	, --mime type
asset_extension	VARCHAR(20)	NULL		, --extension
asset_size_bytes	BIGINT	NULL		, --size_bytes
asset_category	VARCHAR(50)	NOT NULL	DEFAULT 'other'	, --category
asset_purpose	VARCHAR(100)	NULL		,
asset_visibility	VARCHAR(30)	NOT NULL	DEFAULT 'private',
asset_uploadstatus	TEXT	NOT NULL	DEFAULT 'uploaded'	, --upload status
asset_processingstatus	VARCHAR(30)	NULL		, --processing status
asset_processingattempts	INT	NOT NULL	DEFAULT 0	, --processing attempts
asset_processingstartedat	TIMESTAMPTZ	NULL		, --processing started at
asset_processedat	TIMESTAMPTZ	NULL		, --processed at
asset_processingerror	TEXT	NULL		, --processing error
asset_uploadedat	TIMESTAMPTZ	NULL		, --uploaded at
asset_archivedat	TIMESTAMPTZ	NULL		, --archived at
asset_deletedat	TIMESTAMPTZ	NULL		, --deleted at
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

--SELECT * FROM	assets;			
--DROP table  assets;























-- ---------------------------------------------------------
-- Venny I/O table: Content
-- ---------------------------------------------------------			
				
CREATE TABLE IF NOT EXISTS content (				
id	UUID		DEFAULT gen_random_uuid()	,
content_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
content_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
content_startdate	TIMESTAMPTZ	NULL		, --Start Date
content_enddate	TIMESTAMPTZ	NULL		, --End Date
content_slug	TEXT	NOT NULL		, --Slug must url friendly just in case we want to feature the content asset as a page
content_title	TEXT	NOT NULL		, --Title
content_description	VARCHAR(280)	NULL		, --Description also used for SEO
content_body	TEXT	NOT NULL		, --Body
content_tags	JSONB	NULL		, --Tags also used for SEO
content_template	TEXT	NULL		, --Template
content_visible	BOOLEAN	NOT NULL	DEFAULT true	, --Visible
created_by_user_id	VARCHAR(64)	NOT NULL	DEFAULT 'user_8301'	, --what user is this record associated with?
created_for_app_id	VARCHAR(64)	NOT NULL	DEFAULT 'app_8301'	, --which app is making the call to this record
event_id	VARCHAR(64)	NOT NULL	DEFAULT 'event_8301'	, --identifier or the event occurring to produce this record... must always be accompanied with a process ID
process_id	VARCHAR(64)	NOT NULL	DEFAULT 'process_8301'	, --identifier of the process occurring to produce this record... should include an individual event as well.
access	VARCHAR(30)	NOT NULL	DEFAULT 'public'	, --to whom is this record available to?
status	VARCHAR(30)	NOT NULL	DEFAULT 'active'	, --is this record active?
active	INT	NOT NULL	DEFAULT 1	, --identifier of the process occurring to produce this record... should include an individual event as well.
time_started	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	, --time record was started on the path to being written
time_updated	TIMESTAMPTZ	NOT NULL	DEFAULT NOW()	 --time recard of updated if allowed to be updated		
);

--SELECT * FROM	content;			
--DROP TABLE	t_content;























-- ---------------------------------------------------------
-- Venny I/O table: Contacts
-- ---------------------------------------------------------			
				
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
				
--SELECT * FROM	contacts;			
--DROP TABLE	contacts;






















-- ---------------------------------------------------------
-- Venny I/O table: Companies
-- ---------------------------------------------------------			
				
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

--SELECT * from  	companies;			
--DROP TABLE	companies;
























-- ---------------------------------------------------------
-- Venny I/O table: Deals
-- ---------------------------------------------------------			
				
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

--SELECT * from deals;			
--DROP TABLE	deals;






















-- ---------------------------------------------------------
-- Venny I/O table: Pipelines
-- ---------------------------------------------------------		

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
		
--SELECT * FROM	pipelines;			
--DROP TABLE	pipelines;























-- ---------------------------------------------------------
-- Venny I/O table: Stages
-- ---------------------------------------------------------				
				
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

--SELECT * FROM	stages;
--DROP TABLE	stages;























-- ---------------------------------------------------------
-- Venny I/O table: Activities
-- ---------------------------------------------------------				
				
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

--SELECT * FROM	activities;			
--DROP TABLE	activities;






















-- ---------------------------------------------------------
-- Venny I/O table: Tasks
-- ---------------------------------------------------------
				
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

--SELECT * FROM	tasks;			
--DROP TABLE	tasks;























-- ---------------------------------------------------------
-- Venny I/O table: Notes
-- ---------------------------------------------------------			
				
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
	
--SELECT * FROM	notes;			
--DROP TABLE	notes;























-- ---------------------------------------------------------
-- Venny I/O table: Tags
-- ---------------------------------------------------------				
				
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

--SELECT * FROM	tags;			
--DROP TABLE	tags;























-- ---------------------------------------------------------
-- Venny I/O table: Communications
-- ---------------------------------------------------------	
				
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
				
--SELECT * FROM	communications;			
--DROP TABLE	communications;






















-- ---------------------------------------------------------
-- Venny I/O table: Deliveries
-- ---------------------------------------------------------				
				
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

--SELECT * FROM	deliveries;			
--DROP TABLE	deliveries;






















-- ---------------------------------------------------------
-- Venny I/O table: Threads
-- ---------------------------------------------------------				
				
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
			
--SELECT * FROM	threads;			
--DROP TABLE	threads;























"-- ---------------------------------------------------------
-- Venny I/O table: Messages
-- ---------------------------------------------------------"				
				
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

--SELECT * FROM	messages;			
--DROP TABLE	messages;






















-- ---------------------------------------------------------
-- Venny I/O table: Posts
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS posts (				
id	UUID		DEFAULT gen_random_uuid()	,
post_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
post_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
post_object_id	VARCHAR(64)	NOT NULL		, --object_id ID
post_parent_object_id	VARCHAR(64)	NULL		, --parent_object_id ID
post_body	TEXT	NOT NULL		, --body ID
post_images	JSONB			, --images ID
post_closed	BOOLEAN	NOT NULL	DEFAULT false	, --closed ID
post_deleted	BOOLEAN	NOT NULL	DEFAULT false	, --deleted ID
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

--SELECT * FROM	posts;	
--DROP TABLE	posts;
























-- ---------------------------------------------------------
-- Venny I/O table: Followships
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS followships (				
id	UUID		DEFAULT gen_random_uuid()	,
followship_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
followship_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
followship_sender_id	VARCHAR(64)	NOT NULL		, --sender_id ID
followship_recipient_id	VARCHAR(64)	NOT NULL		, --recipient_id ID
followship_status	VARCHAR(30)	NOT NULL		, --status ID
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

--SELECT * FROM	followships;			
--DROP TABLE	followships;























-- ---------------------------------------------------------
-- Venny I/O table: Groups
-- ---------------------------------------------------------
				
CREATE TABLE IF NOT EXISTS groups (				
id	UUID		DEFAULT gen_random_uuid()	,
group_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
group_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
group_sender_id	VARCHAR(64)	NOT NULL		, --sender_id ID
group_recipient_id	VARCHAR(64)	NOT NULL		, --recipient_id ID
group_title	VARCHAR(100)	NOT NULL		, --title ID
group_headline	VARCHAR(280)	NULL		, --headline ID
group_access	VARCHAR(30)	NOT NULL	DEFAULT 'private'	, --access ID
group_participants	JSONB	NOT NULL		, --participants ID
group_images	JSONB	NULL		, --images ID
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

--SELECT * FROM	groups;		
--DROP TABLE	groups;























-- ---------------------------------------------------------
-- Venny I/O table: Acknowledgements
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS acknowledgements (				
id	UUID		DEFAULT gen_random_uuid()	,
acknowledgement_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
acknowledgement_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
acknowledgement_object_id	VARCHAR(64)	NOT NULL		, --object_id ID
acknowledgement_parent_object_id	VARCHAR(64)	NULL		, --parent_object_id ID
acknowledgement_type	VARCHAR(30)	NOT NULL		, --type ID
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

--SELECT * FROM	acknowledgements;
--DROP table acknowledgements;






















-- ---------------------------------------------------------
-- Venny I/O table: Comments
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS comments (				
id	UUID		DEFAULT gen_random_uuid()	,
comment_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
comment_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
comment_object_id	VARCHAR(64)	NOT NULL		, --object_id ID
comment_parent_object_id	VARCHAR(64)	NULL		, --parent_object_id ID
comment_body	TEXT	NOT NULL		, --body ID
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
				
--SELECT * FROM	comments;			
--DROP TABLE	comments;























-- ---------------------------------------------------------
-- Venny I/O table: Catalogs
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS catalogs (				
id	UUID		DEFAULT gen_random_uuid()	,
catalog_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
catalog_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
catalog_online	BOOLEAN	NOT NULL		, --online ID
catalog_public	BOOLEAN	NOT NULL		, --public ID
catalog_name	VARCHAR(100)	NOT NULL		, --name ID
catalog_description	VARCHAR(280)	NOT NULL		, --description ID
catalog_slug	VARCHAR(100)	NOT NULL		, --slug ID
catalog_images	JSONB	NULL		, --images ID
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

--SELECT * FROM	catalogs;
--DROP TABLE	catalogs;






















-- ---------------------------------------------------------
-- Venny I/O table: Categories
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS categories (				
id	UUID		DEFAULT gen_random_uuid()	,
category_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
category_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
category_catalog_id	VARCHAR(64)	NOT NULL		, --catalog_id ID
category_online	BOOLEAN	NOT NULL		, --online ID
category_public	BOOLEAN	NOT NULL		, --public ID
category_name	VARCHAR(100)	NOT NULL		, --name ID
category_description	VARCHAR(280)	NOT NULL		, --description ID
category_slug	VARCHAR(100)	NOT NULL		, --slug ID
category_images	JSONB	NULL		, --images ID
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

--SELECT * FROM	categories;			
--DROP TABLE	categories;























-- ---------------------------------------------------------
-- Venny I/O table: Products
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS products (				
id	UUID		DEFAULT gen_random_uuid()	,
product_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
product_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
product_catalog_id	VARCHAR(64)	NOT NULL		, --catalog_id ID
product_category_id	VARCHAR(64)	NOT NULL		, --category_id ID
product_online	BOOLEAN	NOT NULL		, --online
product_public	BOOLEAN	NOT NULL		, --public
product_name	VARCHAR(100)	NOT NULL		, --name
product_description	VARCHAR(280)	NOT NULL		, --description
product_slug	VARCHAR(100)	NOT NULL		, --slug
product_images	JSONB	NULL		, --images
product_sku	VARCHAR(100)	NOT NULL		, --sku
product_base_price	NUMERIC(12,2)	NOT NULL		, --price
product_inventory	INT	NOT NULL		, --inventory
product_manufacturer	VARCHAR(280)	NULL		, --manufacturer
product_weight	INT	NULL		, --weight
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
		
--SELECT * FROM	products;			
--DROP TABLE	products;






















-- ---------------------------------------------------------
-- Venny I/O table: Items
-- ---------------------------------------------------------			
				
CREATE TABLE IF NOT EXISTS items (				
id	UUID		DEFAULT gen_random_uuid()	,
item_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
item_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
item_catalog_id	VARCHAR(64)	NOT NULL		, --catalog_id ID
item_category_id	VARCHAR(64)	NOT NULL		, --category_id ID
item_product_id	VARCHAR(64)	NOT NULL		, --product_id ID
item_serial_number	VARCHAR(64)	NULL		, --serial_number
item_quantity	INT	NULL		, --quantity
item_sale_price	NUMERIC(12,2)	NULL		, --sale_price
item_size	INT	NULL		, --size
item_color	VARCHAR(30)	NULL		, --color
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
				
--SELECT * FROM	items;		
--DROP TABLE	items;
























-- ---------------------------------------------------------
-- Venny I/O table: Transactions
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS transactions (				
id	UUID		DEFAULT gen_random_uuid()	,
transaction_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
transaction_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
transaction_email	TEXT	NULL		, --email ID
transaction_firstname	TEXT	NULL		, --first name 
transaction_middlename	TEXT	NULL		, --middle name is where all recipients, template, subject, etc. will be retrieved from
transaction_lastname	TEXT	NULL		, --
transaction_phone	TEXT	NULL		, --
transaction_address1	TEXT	NULL		, --
transaction_address2	TEXT	NULL		, --
transaction_city	TEXT	NULL		, --
transaction_state	VARCHAR(50)	NULL		, --
transaction_zip	VARCHAR(20)	NULL		, --
transaction_country	VARCHAR(80)	NOT NULL	DEFAULT 'United States of America'	, --
transaction_currency	VARCHAR(10)	NOT NULL	DEFAULT 'usd'	, --
transaction_subtotal	NUMERIC(12,2)	NOT NULL	DEFAULT 0	, --
transaction_discount	NUMERIC(12,2)	NOT NULL	DEFAULT 0	, --
transaction_tax	NUMERIC(12,2)	NOT NULL	DEFAULT 0	, --
transaction_total	NUMERIC(12,2)	NOT NULL	DEFAULT 0	, --
transaction_attemptcount	INT	NOT NULL	DEFAULT 0	, --
transaction_stripecustomerid	TEXT	NULL		, --
transaction_stripecheckoutsessionid	TEXT	NULL		, --
transaction_stripepaymentintentid	TEXT	NULL		, --
transaction_stripechargeid	TEXT	NULL		, --
transaction_cardbrand	VARCHAR(40)	NULL		, --
transaction_cardfunding	VARCHAR(40)	NULL		, --card funding 
transaction_cardlast4	VARCHAR(4)	NULL		, --
transaction_cardcountry	VARCHAR(10)	NULL		, --card country 
transaction_paidat	TIMESTAMPTZ	NULL		, --
transaction_failedat	TIMESTAMPTZ	NULL		, --failed at 
transaction_cancelledat	TIMESTAMPTZ	NULL		, --cancelled at 
transaction_refundedat	TIMESTAMPTZ	NULL		, --refunded at 
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
				
--SELECT * FROM	transactions;			
--DROP table transactions;






















-- ---------------------------------------------------------
-- Venny I/O table: Orders
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS orders (				
id	UUID		DEFAULT gen_random_uuid()	,
order_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
order_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
order_customer_id	VARCHAR(64)	NULL		, --used to capture future triggers and/or filters
order_totalproduct	DECIMAL(12,2)	NOT NULL		, --total product 
order_totaltax	DECIMAL(12,2)	NOT NULL		, --total tax 
order_totalshipping	DECIMAL(12,2)	NOT NULL		, --total shipping 
order_totaltaxshipping	DECIMAL(12,2)	NOT NULL		, --total tax shipping 
order_totaladjustment	DECIMAL(12,2)	NULL		, --total adjustment 
order_description	TEXT	NULL		, --description 
order_currency	TEXT	NOT NULL		, --currency 
order_locked	BOOLEAN			, --locked 
order_address	TEXT	NULL		, --address 
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
				
--SELECT * FROM	orders;		
--DROP TABLE	orders;






















-- ---------------------------------------------------------
-- Venny I/O table: Coupons
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS coupons (				
id	UUID		DEFAULT gen_random_uuid()	,
coupon_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
coupon_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
thread_id	VARCHAR(40)	NOT NULL		, --
coupon_sender_id	VARCHAR(255)	NOT NULL		, --
coupon_code	VARCHAR(30)	NOT NULL	UNIQUE	, --code ID
coupon_description	TEXT	NOT NULL	DEFAULT ''	, --description 
coupon_discounttype	VARCHAR(30)	NOT NULL		, --discount type is where all recipients, template, subject, etc. will be retrieved from
coupon_percent	NUMERIC(5,2)	NULL		, --
coupon_amount	NUMERIC(12,2)	NULL		, --
coupon_currency	VARCHAR(10)	NOT NULL	DEFAULT 'usd'	, --
coupon_minimumamount	NUMERIC(12,2)	NULL		, --
coupon_maximumamount	NUMERIC(12,2)	NULL		, --
coupon_startsat	TIMESTAMPTZ	NULL		, --
coupon_expiresat	TIMESTAMPTZ	NULL		, --
coupon_maximumredemptions	INT	NULL		, --
coupon_redemptions	INT	NOT NULL	DEFAULT 0	, --
coupon_subtotal	NUMERIC(12,2)	NOT NULL	DEFAULT 0	, --
coupon_discount	NUMERIC(12,2)	NOT NULL	DEFAULT 0	, --
coupon_tax	NUMERIC(12,2)	NOT NULL	DEFAULT 0	, --
coupon_total	NUMERIC(12,2)	NOT NULL	DEFAULT 0	, --
coupon_attemptcount	INT	NOT NULL	DEFAULT 0	, --
coupon_stripecustomerid	TEXT	NULL		, --
coupon_stripecheckoutsessionid	TEXT	NULL		, --
coupon_stripepaymentintentid	TEXT	NULL		, --
coupon_stripechargeid	TEXT	NULL		, --
coupon_cardbrand	VARCHAR(40)	NULL		, --
coupon_cardfunding	VARCHAR(40)	NULL		, --card funding 
coupon_cardlast4	VARCHAR(4)	NULL		, --
coupon_cardcountry	VARCHAR(10)	NULL		, --card country 
coupon_paidat	TIMESTAMPTZ	NULL		, --
coupon_failedat	TIMESTAMPTZ	NULL		, --failed at 
coupon_cancelledat	TIMESTAMPTZ	NULL		, --cancelled at 
coupon_refundedat	TIMESTAMPTZ	NULL		, --refunded at 
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

--SELECT * FROM	coupons;
--DROP TABLE	coupons;























-- ---------------------------------------------------------
-- Venny I/O table: Customers
-- ---------------------------------------------------------				
				
CREATE TABLE IF NOT EXISTS customers (				
id	UUID		DEFAULT gen_random_uuid()	,
customer_id	VARCHAR(64)	NOT NULL	UNIQUE PRIMARY KEY	, --reference # for this database entry on this table
customer_attributes	JSONB	NOT NULL	DEFAULT '{}'::jsonb	, --used to capture future triggers and/or filters
customer_firstname	VARCHAR(255)	NOT NULL		, --First Name ID
customer_middlename	VARCHAR(255)	NULL		, --Middle Name 
customer_lastname	VARCHAR(255)	NOT NULL		, --Last Name is where all recipients, template, subject, etc. will be retrieved from
customer_telephone	VARCHAR(30)	NULL		, --Telephone is where all recipients, template, subject, etc. will be retrieved from
customer_initialcontact	TIMESTAMP	NULL		, --Initial Contact is where all recipients, template, subject, etc. will be retrieved from
customer_email	VARCHAR(255)	NULL		, --Email is where all recipients, template, subject, etc. will be retrieved from
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
				
--SELECT * FROM	customers;			
--DROP TABLE	customers;