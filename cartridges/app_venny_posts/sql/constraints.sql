-- app_venny_posts constraints

SELECT venny_add_constraint('posts', 'ck_posts_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('posts', 'ck_posts_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('posts', 'ck_posts_attributes_object', 'CHECK (jsonb_typeof(post_attributes) = ''object'')');
SELECT venny_add_constraint('posts', 'ck_posts_body_nonblank', 'CHECK (btrim(post_body) <> '''')');
SELECT venny_add_constraint('posts', 'ck_posts_images_json', 'CHECK (post_images IS NULL OR jsonb_typeof(post_images) IN (''array'', ''object''))');
SELECT venny_add_constraint('posts', 'fk_posts_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');

-- Optional after backfill:
-- ALTER TABLE posts VALIDATE CONSTRAINT fk_posts_created_for_app;
