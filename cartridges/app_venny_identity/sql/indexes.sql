-- app_venny_identity indexes

CREATE INDEX IF NOT EXISTS idx_persons_app_source_time
ON persons (created_for_app_id, person_source, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_persons_app_name
ON persons (created_for_app_id, person_lastname, person_firstname)
WHERE active = 1 AND status = 'active';

CREATE INDEX IF NOT EXISTS idx_persons_emails_gin
ON persons USING GIN (person_emails);

CREATE INDEX IF NOT EXISTS idx_persons_phones_gin
ON persons USING GIN (person_phones)
WHERE person_phones IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_users_app_email_active
ON users (created_for_app_id, user_email, active, status);

CREATE INDEX IF NOT EXISTS idx_users_app_username_active
ON users (created_for_app_id, user_username, active, status)
WHERE user_username IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_users_app_lastlogin
ON users (created_for_app_id, user_lastlogin DESC)
WHERE user_lastlogin IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_profiles_app_username_active
ON profiles (created_for_app_id, profile_username, active, status)
WHERE profile_username IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_profiles_app_published
ON profiles (created_for_app_id, profile_biopublished, active, status);
