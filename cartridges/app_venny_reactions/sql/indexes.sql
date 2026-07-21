-- app_venny_reactions indexes

CREATE INDEX IF NOT EXISTS idx_acknowledgements_object_type_time
ON acknowledgements (acknowledgement_object_id, acknowledgement_type, created_for_app_id, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_acknowledgements_parent_time
ON acknowledgements (acknowledgement_parent_object_id, time_started DESC)
WHERE acknowledgement_parent_object_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_acknowledgements_attributes_gin
ON acknowledgements USING GIN (acknowledgement_attributes);

CREATE INDEX IF NOT EXISTS idx_comments_object_time
ON comments (comment_object_id, created_for_app_id, time_started DESC)
WHERE active = 1;

CREATE INDEX IF NOT EXISTS idx_comments_parent_time
ON comments (comment_parent_object_id, time_started DESC)
WHERE comment_parent_object_id IS NOT NULL AND active = 1;

CREATE INDEX IF NOT EXISTS idx_comments_attributes_gin
ON comments USING GIN (comment_attributes);
