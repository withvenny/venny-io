-- ---------------------------------------------------------
-- app_venny_cms constraints
-- ---------------------------------------------------------

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_proc WHERE proname = 'venny_add_constraint') THEN
    CREATE OR REPLACE FUNCTION venny_add_constraint(p_table text, p_name text, p_constraint text)
    RETURNS void AS $fn$
    BEGIN
      IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = p_name
      ) THEN
        EXECUTE format('ALTER TABLE %I ADD CONSTRAINT %I %s', p_table, p_name, p_constraint);
      END IF;
    END;
    $fn$ LANGUAGE plpgsql;
  END IF;
END $$;

SELECT venny_add_constraint('assets', 'ck_assets_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('assets', 'ck_assets_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('assets', 'ck_assets_attributes_object', 'CHECK (jsonb_typeof(asset_attributes) = ''object'')');
SELECT venny_add_constraint('assets', 'fk_assets_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('assets', 'ck_assets_object_type_nonblank', 'CHECK (btrim(asset_object_type) <> '''')');
SELECT venny_add_constraint('assets', 'ck_assets_originalfilename_nonblank', 'CHECK (btrim(asset_originalfilename) <> '''')');
SELECT venny_add_constraint('assets', 'ck_assets_storageprovider_nonblank', 'CHECK (btrim(asset_storageprovider) <> '''')');
SELECT venny_add_constraint('assets', 'ck_assets_bucket_nonblank', 'CHECK (btrim(asset_bucket) <> '''')');
SELECT venny_add_constraint('assets', 'ck_assets_key_nonblank', 'CHECK (btrim(asset_key) <> '''')');
SELECT venny_add_constraint('assets', 'ck_assets_size_nonnegative', 'CHECK (asset_size_bytes IS NULL OR asset_size_bytes >= 0)');
SELECT venny_add_constraint('assets', 'ck_assets_processing_attempts_nonnegative', 'CHECK (asset_processingattempts >= 0)');
SELECT venny_add_constraint('assets', 'ck_assets_processing_dates', 'CHECK (asset_processedat IS NULL OR asset_processingstartedat IS NULL OR asset_processedat >= asset_processingstartedat)');
SELECT venny_add_constraint('assets', 'ck_assets_archive_delete_dates', 'CHECK (asset_deletedat IS NULL OR asset_archivedat IS NULL OR asset_deletedat >= asset_archivedat)');
SELECT venny_add_constraint('assets', 'uq_assets_storage_key', 'UNIQUE (asset_storageprovider, asset_bucket, asset_key)');

SELECT venny_add_constraint('content', 'ck_content_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('content', 'ck_content_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('content', 'ck_content_attributes_object', 'CHECK (jsonb_typeof(content_attributes) = ''object'')');
SELECT venny_add_constraint('content', 'fk_content_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('content', 'ck_content_slug_nonblank', 'CHECK (btrim(content_slug) <> '''')');
SELECT venny_add_constraint('content', 'ck_content_title_nonblank', 'CHECK (btrim(content_title) <> '''')');
SELECT venny_add_constraint('content', 'ck_content_body_nonblank', 'CHECK (btrim(content_body) <> '''')');
SELECT venny_add_constraint('content', 'ck_content_tags_json', 'CHECK (content_tags IS NULL OR jsonb_typeof(content_tags) IN (''array'', ''object''))');
SELECT venny_add_constraint('content', 'ck_content_date_range', 'CHECK (content_enddate IS NULL OR content_startdate IS NULL OR content_enddate >= content_startdate)');
