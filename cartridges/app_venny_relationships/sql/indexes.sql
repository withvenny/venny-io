-- app_venny_relationships indexes

CREATE INDEX IF NOT EXISTS idx_followships_sender_status
ON followships (followship_sender_id, followship_status, created_for_app_id, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_followships_recipient_status
ON followships (followship_recipient_id, followship_status, created_for_app_id, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_groups_app_access_time
ON groups (created_for_app_id, group_access, active, status, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_groups_sender_time
ON groups (group_sender_id, created_for_app_id, time_started DESC)
WHERE active = 1;

CREATE INDEX IF NOT EXISTS idx_groups_participants_gin
ON groups USING GIN (group_participants)
WHERE active = 1;
