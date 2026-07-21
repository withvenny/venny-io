-- app_venny_identity constraints

SELECT venny_add_constraint('persons', 'ck_persons_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('users', 'ck_users_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('profiles', 'ck_profiles_active_binary', 'CHECK (active IN (0, 1))');

SELECT venny_add_constraint('persons', 'ck_persons_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('users', 'ck_users_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('profiles', 'ck_profiles_status_nonblank', 'CHECK (btrim(status) <> '''')');

SELECT venny_add_constraint('persons', 'ck_persons_attributes_object', 'CHECK (jsonb_typeof(person_attributes) = ''object'')');
SELECT venny_add_constraint('users', 'ck_users_attributes_object', 'CHECK (jsonb_typeof(user_attributes) = ''object'')');
SELECT venny_add_constraint('profiles', 'ck_profiles_attributes_object', 'CHECK (jsonb_typeof(profile_attributes) = ''object'')');

SELECT venny_add_constraint('persons', 'fk_persons_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('users', 'fk_users_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('profiles', 'fk_profiles_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');

SELECT venny_add_constraint('persons', 'ck_persons_email_object', 'CHECK (jsonb_typeof(person_emails) = ''object'')');
SELECT venny_add_constraint('persons', 'ck_persons_phones_object_or_null', 'CHECK (person_phones IS NULL OR jsonb_typeof(person_phones) = ''object'')');
SELECT venny_add_constraint('persons', 'ck_persons_addresses_object_or_null', 'CHECK (person_addresses IS NULL OR jsonb_typeof(person_addresses) = ''object'')');
SELECT venny_add_constraint('persons', 'ck_persons_dob_not_future', 'CHECK (person_dateofbirth IS NULL OR person_dateofbirth <= CURRENT_DATE)');
SELECT venny_add_constraint('persons', 'ck_persons_source_nonblank', 'CHECK (btrim(person_source) <> '''')');

SELECT venny_add_constraint('users', 'ck_users_email_basic_shape', 'CHECK (position(''@'' in user_email::text) > 1)');
SELECT venny_add_constraint('users', 'ck_users_addresses_object_or_null', 'CHECK (user_addresses IS NULL OR jsonb_typeof(user_addresses) = ''object'')');
SELECT venny_add_constraint('users', 'ck_users_phones_object_or_null', 'CHECK (user_phones IS NULL OR jsonb_typeof(user_phones) = ''object'')');
SELECT venny_add_constraint('users', 'ck_users_optins_object_or_null', 'CHECK (user_optins IS NULL OR jsonb_typeof(user_optins) = ''object'')');

SELECT venny_add_constraint('profiles', 'ck_profiles_username_nonblank_or_null', 'CHECK (profile_username IS NULL OR btrim(profile_username::text) <> '''')');
SELECT venny_add_constraint('profiles', 'ck_profiles_displayname_nonblank_or_null', 'CHECK (profile_displayname IS NULL OR btrim(profile_displayname) <> '''')');
