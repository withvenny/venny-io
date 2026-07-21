-- app_venny_chat schema

CREATE TABLE IF NOT EXISTS threads (
id UUID DEFAULT gen_random_uuid(),
thread_id VARCHAR(64) NOT NULL UNIQUE PRIMARY KEY,
thread_attributes JSONB NOT NULL DEFAULT '{}'::jsonb,
thread_subject VARCHAR(255) NOT NULL,
thread_participants JSONB NOT NULL DEFAULT '{}'::jsonb,
thread_lastmessagepreview TEXT NULL DEFAULT '',
thread_lastmessageat TIMESTAMPTZ NULL,
thread_author_id VARCHAR(40) NOT NULL,
created_by_user_id VARCHAR(64) NOT NULL DEFAULT 'user_8301',
created_for_app_id VARCHAR(64) NOT NULL DEFAULT 'app_8301',
event_id VARCHAR(64) NOT NULL DEFAULT 'event_8301',
process_id VARCHAR(64) NOT NULL DEFAULT 'process_8301',
access VARCHAR(30) NOT NULL DEFAULT 'private',
status VARCHAR(30) NOT NULL DEFAULT 'active',
active INT NOT NULL DEFAULT 1,
time_started TIMESTAMPTZ NOT NULL DEFAULT NOW(),
time_updated TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS messages (
id UUID DEFAULT gen_random_uuid(),
message_id VARCHAR(64) NOT NULL UNIQUE PRIMARY KEY,
message_attributes JSONB NOT NULL DEFAULT '{}'::jsonb,
thread_id VARCHAR(64) NOT NULL,
message_sender_id VARCHAR(64) NOT NULL,
message_body TEXT NOT NULL,
message_attachments JSONB NOT NULL DEFAULT '{}'::jsonb,
message_readby JSONB NOT NULL DEFAULT '{}'::jsonb,
created_by_user_id VARCHAR(64) NOT NULL DEFAULT 'user_8301',
created_for_app_id VARCHAR(64) NOT NULL DEFAULT 'app_8301',
event_id VARCHAR(64) NOT NULL DEFAULT 'event_8301',
process_id VARCHAR(64) NOT NULL DEFAULT 'process_8301',
access VARCHAR(30) NOT NULL DEFAULT 'private',
status VARCHAR(30) NOT NULL DEFAULT 'active',
active INT NOT NULL DEFAULT 1,
time_started TIMESTAMPTZ NOT NULL DEFAULT NOW(),
time_updated TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
