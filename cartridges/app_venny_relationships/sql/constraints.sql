-- app_venny_relationships constraints

SELECT venny_add_constraint('followships', 'ck_followships_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('followships', 'ck_followships_record_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('followships', 'ck_followships_attributes_object', 'CHECK (jsonb_typeof(followship_attributes) = ''object'')');
SELECT venny_add_constraint('followships', 'ck_followships_sender_nonblank', 'CHECK (btrim(followship_sender_id) <> '''')');
SELECT venny_add_constraint('followships', 'ck_followships_recipient_nonblank', 'CHECK (btrim(followship_recipient_id) <> '''')');
SELECT venny_add_constraint('followships', 'ck_followships_no_self_follow', 'CHECK (followship_sender_id <> followship_recipient_id)');
SELECT venny_add_constraint('followships', 'ck_followships_status_nonblank', 'CHECK (btrim(followship_status) <> '''')');
SELECT venny_add_constraint('followships', 'uq_followships_app_sender_recipient', 'UNIQUE (created_for_app_id, followship_sender_id, followship_recipient_id)');
SELECT venny_add_constraint('followships', 'fk_followships_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('followships', 'fk_followships_sender', 'FOREIGN KEY (followship_sender_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('followships', 'fk_followships_recipient', 'FOREIGN KEY (followship_recipient_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');

SELECT venny_add_constraint('groups', 'ck_groups_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('groups', 'ck_groups_record_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('groups', 'ck_groups_attributes_object', 'CHECK (jsonb_typeof(group_attributes) = ''object'')');
SELECT venny_add_constraint('groups', 'ck_groups_sender_nonblank', 'CHECK (btrim(group_sender_id) <> '''')');
SELECT venny_add_constraint('groups', 'ck_groups_recipient_nonblank', 'CHECK (btrim(group_recipient_id) <> '''')');
SELECT venny_add_constraint('groups', 'ck_groups_title_nonblank', 'CHECK (btrim(group_title) <> '''')');
SELECT venny_add_constraint('groups', 'ck_groups_access_nonblank', 'CHECK (btrim(group_access) <> '''')');
SELECT venny_add_constraint('groups', 'ck_groups_participants_json', 'CHECK (jsonb_typeof(group_participants) IN (''array'', ''object''))');
SELECT venny_add_constraint('groups', 'ck_groups_images_json', 'CHECK (group_images IS NULL OR jsonb_typeof(group_images) IN (''array'', ''object''))');
SELECT venny_add_constraint('groups', 'fk_groups_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('groups', 'fk_groups_sender', 'FOREIGN KEY (group_sender_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('groups', 'fk_groups_recipient', 'FOREIGN KEY (group_recipient_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');

-- Optional after backfill:
-- ALTER TABLE followships VALIDATE CONSTRAINT fk_followships_created_for_app;
-- ALTER TABLE followships VALIDATE CONSTRAINT fk_followships_sender;
-- ALTER TABLE followships VALIDATE CONSTRAINT fk_followships_recipient;
-- ALTER TABLE groups VALIDATE CONSTRAINT fk_groups_created_for_app;
-- ALTER TABLE groups VALIDATE CONSTRAINT fk_groups_sender;
-- ALTER TABLE groups VALIDATE CONSTRAINT fk_groups_recipient;
