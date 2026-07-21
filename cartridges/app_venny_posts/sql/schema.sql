-- app_venny_posts schema

CREATE TABLE IF NOT EXISTS posts (
id UUID DEFAULT gen_random_uuid(),
post_id VARCHAR(64) NOT NULL UNIQUE PRIMARY KEY,
post_attributes JSONB NOT NULL DEFAULT '{}'::jsonb,
post_object_id VARCHAR(64) NOT NULL,
post_parent_object_id VARCHAR(64) NULL,
post_body TEXT NOT NULL,
post_images JSONB NULL,
post_closed BOOLEAN NOT NULL DEFAULT false,
post_deleted BOOLEAN NOT NULL DEFAULT false,
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
