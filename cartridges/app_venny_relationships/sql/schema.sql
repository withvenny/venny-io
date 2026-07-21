-- app_venny_relationships schema

-- Existing Venny I/O schema tables owned by this cartridge:
--   followships
--   groups

CREATE TABLE IF NOT EXISTS followships (
id UUID DEFAULT gen_random_uuid(),
followship_id VARCHAR(64) NOT NULL UNIQUE PRIMARY KEY,
followship_attributes JSONB NOT NULL DEFAULT '{}'::jsonb,
followship_sender_id VARCHAR(64) NOT NULL,
followship_recipient_id VARCHAR(64) NOT NULL,
followship_status VARCHAR(30) NOT NULL,
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

CREATE TABLE IF NOT EXISTS groups (
id UUID DEFAULT gen_random_uuid(),
group_id VARCHAR(64) NOT NULL UNIQUE PRIMARY KEY,
group_attributes JSONB NOT NULL DEFAULT '{}'::jsonb,
group_sender_id VARCHAR(64) NOT NULL,
group_recipient_id VARCHAR(64) NOT NULL,
group_title VARCHAR(100) NOT NULL,
group_headline VARCHAR(280) NULL,
group_access VARCHAR(30) NOT NULL DEFAULT 'private',
group_participants JSONB NOT NULL,
group_images JSONB NULL,
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
