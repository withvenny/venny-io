-- app_venny_posts indexes

CREATE INDEX IF NOT EXISTS idx_posts_object_time
ON posts (post_object_id, created_for_app_id, time_started DESC)
WHERE post_deleted = false AND active = 1;

CREATE INDEX IF NOT EXISTS idx_posts_parent_time
ON posts (post_parent_object_id, time_started DESC)
WHERE post_parent_object_id IS NOT NULL AND post_deleted = false;

CREATE INDEX IF NOT EXISTS idx_posts_process_event
ON posts (process_id, event_id, time_started DESC);
