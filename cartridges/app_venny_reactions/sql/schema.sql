-- app_venny_reactions schema

CREATE TABLE IF NOT EXISTS acknowledgements (
    id UUID DEFAULT gen_random_uuid(),
    acknowledgement_id VARCHAR(64) NOT NULL UNIQUE PRIMARY KEY,
    acknowledgement_attributes JSONB NOT NULL DEFAULT '{}'::jsonb,
    acknowledgement_object_id VARCHAR(64) NOT NULL,
    acknowledgement_parent_object_id VARCHAR(64) NULL,
    acknowledgement_type VARCHAR(30) NOT NULL,
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

CREATE TABLE IF NOT EXISTS comments (
    id UUID DEFAULT gen_random_uuid(),
    comment_id VARCHAR(64) NOT NULL UNIQUE PRIMARY KEY,
    comment_attributes JSONB NOT NULL DEFAULT '{}'::jsonb,
    comment_object_id VARCHAR(64) NOT NULL,
    comment_parent_object_id VARCHAR(64) NULL,
    comment_body TEXT NOT NULL,
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
