-- app_venny_account v1
-- Recommended support indexes for account lookups.
CREATE INDEX IF NOT EXISTS idx_persons_account_user_app
ON persons (created_for_app_id, created_by_user_id, active, status, time_updated DESC);

CREATE INDEX IF NOT EXISTS idx_profiles_account_user_app
ON profiles (created_for_app_id, created_by_user_id, active, status, time_updated DESC);

CREATE INDEX IF NOT EXISTS idx_sessions_account_active
ON sessions (created_for_app_id, session_id, session_user_id, active, status, session_expiresat)
WHERE active = 1 AND status = 'active' AND session_revokedat IS NULL;
