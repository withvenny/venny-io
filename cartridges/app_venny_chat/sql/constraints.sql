-- app_venny_chat constraints

SELECT venny_add_constraint('threads', 'ck_threads_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('messages', 'ck_messages_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('threads', 'ck_threads_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('messages', 'ck_messages_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('threads', 'ck_threads_attributes_object', 'CHECK (jsonb_typeof(thread_attributes) = ''object'')');
SELECT venny_add_constraint('messages', 'ck_messages_attributes_object', 'CHECK (jsonb_typeof(message_attributes) = ''object'')');
SELECT venny_add_constraint('threads', 'fk_threads_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('messages', 'fk_messages_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('threads', 'fk_threads_author', 'FOREIGN KEY (thread_author_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('threads', 'ck_threads_subject_nonblank', 'CHECK (btrim(thread_subject) <> '''')');
SELECT venny_add_constraint('threads', 'ck_threads_participants_json', 'CHECK (jsonb_typeof(thread_participants) IN (''array'', ''object''))');
SELECT venny_add_constraint('messages', 'fk_messages_thread', 'FOREIGN KEY (thread_id) REFERENCES threads(thread_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('messages', 'fk_messages_sender', 'FOREIGN KEY (message_sender_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('messages', 'ck_messages_body_nonblank', 'CHECK (btrim(message_body) <> '''')');
SELECT venny_add_constraint('messages', 'ck_messages_attachments_json', 'CHECK (jsonb_typeof(message_attachments) IN (''array'', ''object''))');
SELECT venny_add_constraint('messages', 'ck_messages_readby_json', 'CHECK (jsonb_typeof(message_readby) IN (''array'', ''object''))');

-- ALTER TABLE messages VALIDATE CONSTRAINT fk_messages_thread;
