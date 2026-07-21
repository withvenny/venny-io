-- ---------------------------------------------------------
-- app_venny_cms indexes
-- ---------------------------------------------------------

CREATE INDEX IF NOT EXISTS idx_assets_object_lookup
ON assets (asset_object_type, asset_object_id, created_for_app_id, active, status);

CREATE INDEX IF NOT EXISTS idx_assets_storage_key
ON assets (asset_storageprovider, asset_bucket, asset_key);

CREATE INDEX IF NOT EXISTS idx_assets_app_category_purpose
ON assets (created_for_app_id, asset_category, asset_purpose, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_assets_processing_queue
ON assets (asset_processingstatus, asset_processingattempts, time_started)
WHERE active = 1 AND asset_processingstatus IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_assets_app_lifecycle
ON assets (created_for_app_id, asset_uploadedat DESC, asset_archivedat, asset_deletedat);

CREATE INDEX IF NOT EXISTS idx_assets_checksum
ON assets (asset_checksum_sha265)
WHERE asset_checksum_sha265 IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_assets_process_event
ON assets (process_id, event_id, time_started DESC);

CREATE UNIQUE INDEX IF NOT EXISTS uq_content_app_slug_active
ON content (created_for_app_id, lower(content_slug))
WHERE active = 1;

CREATE INDEX IF NOT EXISTS idx_content_app_status_visible
ON content (created_for_app_id, status, active, content_visible, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_content_slug_lookup
ON content (created_for_app_id, lower(content_slug));

CREATE INDEX IF NOT EXISTS idx_content_tags_gin
ON content USING gin (content_tags);

CREATE INDEX IF NOT EXISTS idx_content_process_event
ON content (process_id, event_id, time_started DESC);
