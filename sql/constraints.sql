-- =========================================================
-- Venny I/O Constraints v2 - steps table aligned
-- Purpose: domain integrity, tenant/app linkage, safe values, and key relationships.
--
-- Notes:
-- - The setup-step table is named steps.
-- - The current schema does not include created_for_app_id or installation_id on installations/steps,
--   so this file does not add app/install foreign keys for those two setup tables.
-- - Primary keys and inline UNIQUE columns already exist in schema.sql and are not repeated here.
-- - Foreign keys are added as NOT VALID where appropriate so this file can be applied to an existing dev database.
-- =========================================================

-- ---------------------------------------------------------
-- Setup installer tables: installations + steps
-- These use direct DO blocks because they are setup-level tables and currently sit outside the created_for_app_id tenant pattern.
-- ---------------------------------------------------------

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_installations_active_boolean') THEN
        ALTER TABLE installations
            ADD CONSTRAINT ck_installations_active_boolean
            CHECK (active IN (0, 1));
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_steps_active_boolean') THEN
        ALTER TABLE steps
            ADD CONSTRAINT ck_steps_active_boolean
            CHECK (active IN (0, 1));
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_installations_attributes_object') THEN
        ALTER TABLE installations
            ADD CONSTRAINT ck_installations_attributes_object
            CHECK (jsonb_typeof(installation_attributes) = 'object');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_steps_attributes_object') THEN
        ALTER TABLE steps
            ADD CONSTRAINT ck_steps_attributes_object
            CHECK (jsonb_typeof(step_attributes) = 'object');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_installations_modules_array') THEN
        ALTER TABLE installations
            ADD CONSTRAINT ck_installations_modules_array
            CHECK (jsonb_typeof(installation_modules) = 'array');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_installations_summary_object') THEN
        ALTER TABLE installations
            ADD CONSTRAINT ck_installations_summary_object
            CHECK (jsonb_typeof(installation_summary) = 'object');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_steps_summary_object') THEN
        ALTER TABLE steps
            ADD CONSTRAINT ck_steps_summary_object
            CHECK (jsonb_typeof(step_summary) = 'object');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_installations_status_valid') THEN
        ALTER TABLE installations
            ADD CONSTRAINT ck_installations_status_valid
            CHECK (installation_status IN ('pending', 'running', 'completed', 'failed', 'cancelled', 'rolled_back'));
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_steps_status_valid') THEN
        ALTER TABLE steps
            ADD CONSTRAINT ck_steps_status_valid
            CHECK (step_status IN ('pending', 'running', 'completed', 'failed', 'skipped', 'rolled_back'));
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_steps_order_nonnegative') THEN
        ALTER TABLE steps
            ADD CONSTRAINT ck_steps_order_nonnegative
            CHECK (step_order >= 0);
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_installations_finished_after_started') THEN
        ALTER TABLE installations
            ADD CONSTRAINT ck_installations_finished_after_started
            CHECK (installation_finished_at IS NULL OR installation_finished_at >= installation_started_at);
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_steps_finished_after_started') THEN
        ALTER TABLE steps
            ADD CONSTRAINT ck_steps_finished_after_started
            CHECK (step_finished_at IS NULL OR step_finished_at >= step_started_at);
    END IF;
END $$;

CREATE OR REPLACE FUNCTION venny_add_constraint(
    p_table_name TEXT,
    p_constraint_name TEXT,
    p_constraint_definition TEXT
) RETURNS void AS $$
BEGIN
    IF to_regclass(p_table_name) IS NULL THEN
        RAISE NOTICE 'Skipping constraint %. Table % does not exist.', p_constraint_name, p_table_name;
        RETURN;
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint c
        JOIN pg_class t ON t.oid = c.conrelid
        JOIN pg_namespace n ON n.oid = t.relnamespace
        WHERE c.conname = p_constraint_name
          AND t.relname = p_table_name
          AND n.nspname = current_schema()
    ) THEN
        EXECUTE format('ALTER TABLE %I ADD CONSTRAINT %I %s',
            p_table_name,
            p_constraint_name,
            p_constraint_definition
        );
    END IF;
END;
$$ LANGUAGE plpgsql;

-- =========================================================
-- 01. Common platform constraints
-- =========================================================

-- All current tables use active INT. Keep it binary until/unless this becomes BOOLEAN.
SELECT venny_add_constraint('apps', 'ck_apps_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('keys', 'ck_keys_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('sessions', 'ck_sessions_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('windows', 'ck_windows_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('persons', 'ck_persons_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('users', 'ck_users_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('profiles', 'ck_profiles_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('assets', 'ck_assets_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('content', 'ck_content_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('contacts', 'ck_contacts_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('companies', 'ck_companies_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('deals', 'ck_deals_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('pipelines', 'ck_pipelines_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('stages', 'ck_stages_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('activities', 'ck_activities_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('tasks', 'ck_tasks_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('notes', 'ck_notes_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('tags', 'ck_tags_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('communications', 'ck_communications_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('deliveries', 'ck_deliveries_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('threads', 'ck_threads_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('messages', 'ck_messages_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('posts', 'ck_posts_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('followships', 'ck_followships_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('groups', 'ck_groups_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('acknowledgements', 'ck_acknowledgements_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('comments', 'ck_comments_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('catalogs', 'ck_catalogs_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('categories', 'ck_categories_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('products', 'ck_products_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('items', 'ck_items_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('transactions', 'ck_transactions_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('orders', 'ck_orders_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('coupons', 'ck_coupons_active_binary', 'CHECK (active IN (0, 1))');
SELECT venny_add_constraint('customers', 'ck_customers_active_binary', 'CHECK (active IN (0, 1))');

-- Basic nonblank lifecycle fields. Avoid hard-coding status/access enums this early.
SELECT venny_add_constraint('apps', 'ck_apps_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('keys', 'ck_keys_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('sessions', 'ck_sessions_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('windows', 'ck_windows_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('persons', 'ck_persons_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('users', 'ck_users_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('profiles', 'ck_profiles_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('assets', 'ck_assets_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('content', 'ck_content_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('contacts', 'ck_contacts_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('companies', 'ck_companies_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('deals', 'ck_deals_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('pipelines', 'ck_pipelines_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('stages', 'ck_stages_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('activities', 'ck_activities_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('tasks', 'ck_tasks_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('notes', 'ck_notes_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('tags', 'ck_tags_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('communications', 'ck_communications_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('deliveries', 'ck_deliveries_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('threads', 'ck_threads_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('messages', 'ck_messages_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('posts', 'ck_posts_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('followships', 'ck_followships_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('groups', 'ck_groups_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('acknowledgements', 'ck_acknowledgements_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('comments', 'ck_comments_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('catalogs', 'ck_catalogs_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('categories', 'ck_categories_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('products', 'ck_products_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('items', 'ck_items_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('transactions', 'ck_transactions_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('orders', 'ck_orders_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('coupons', 'ck_coupons_status_nonblank', 'CHECK (btrim(status) <> '''')');
SELECT venny_add_constraint('customers', 'ck_customers_status_nonblank', 'CHECK (btrim(status) <> '''')');

-- JSONB attributes should remain objects. This keeps flexible metadata flexible without letting arrays/scalars slip in.
SELECT venny_add_constraint('apps', 'ck_apps_attributes_object', 'CHECK (jsonb_typeof(app_attributes) = ''object'')');
SELECT venny_add_constraint('keys', 'ck_keys_attributes_object', 'CHECK (jsonb_typeof(key_attributes) = ''object'')');
SELECT venny_add_constraint('sessions', 'ck_sessions_attributes_object', 'CHECK (jsonb_typeof(session_attributes) = ''object'')');
SELECT venny_add_constraint('windows', 'ck_windows_attributes_object', 'CHECK (jsonb_typeof(window_attributes) = ''object'')');
SELECT venny_add_constraint('persons', 'ck_persons_attributes_object', 'CHECK (jsonb_typeof(person_attributes) = ''object'')');
SELECT venny_add_constraint('users', 'ck_users_attributes_object', 'CHECK (jsonb_typeof(user_attributes) = ''object'')');
SELECT venny_add_constraint('profiles', 'ck_profiles_attributes_object', 'CHECK (jsonb_typeof(profile_attributes) = ''object'')');
SELECT venny_add_constraint('assets', 'ck_assets_attributes_object', 'CHECK (jsonb_typeof(asset_attributes) = ''object'')');
SELECT venny_add_constraint('content', 'ck_content_attributes_object', 'CHECK (jsonb_typeof(content_attributes) = ''object'')');
SELECT venny_add_constraint('contacts', 'ck_contacts_attributes_object', 'CHECK (jsonb_typeof(contact_attributes) = ''object'')');
SELECT venny_add_constraint('companies', 'ck_companies_attributes_object', 'CHECK (jsonb_typeof(company_attributes) = ''object'')');
SELECT venny_add_constraint('deals', 'ck_deals_attributes_object', 'CHECK (jsonb_typeof(deal_attributes) = ''object'')');
SELECT venny_add_constraint('pipelines', 'ck_pipelines_attributes_object', 'CHECK (jsonb_typeof(pipeline_attributes) = ''object'')');
SELECT venny_add_constraint('stages', 'ck_stages_attributes_object', 'CHECK (jsonb_typeof(stage_attributes) = ''object'')');
SELECT venny_add_constraint('activities', 'ck_activities_attributes_object', 'CHECK (jsonb_typeof(activity_attributes) = ''object'')');
SELECT venny_add_constraint('tasks', 'ck_tasks_attributes_object', 'CHECK (jsonb_typeof(task_attributes) = ''object'')');
SELECT venny_add_constraint('notes', 'ck_notes_attributes_object', 'CHECK (jsonb_typeof(note_attributes) = ''object'')');
SELECT venny_add_constraint('tags', 'ck_tags_attributes_object', 'CHECK (jsonb_typeof(tag_attributes) = ''object'')');
SELECT venny_add_constraint('communications', 'ck_communications_attributes_object', 'CHECK (jsonb_typeof(communication_attributes) = ''object'')');
SELECT venny_add_constraint('deliveries', 'ck_deliveries_attributes_object', 'CHECK (jsonb_typeof(delivery_attributes) = ''object'')');
SELECT venny_add_constraint('threads', 'ck_threads_attributes_object', 'CHECK (jsonb_typeof(thread_attributes) = ''object'')');
SELECT venny_add_constraint('messages', 'ck_messages_attributes_object', 'CHECK (jsonb_typeof(message_attributes) = ''object'')');
SELECT venny_add_constraint('posts', 'ck_posts_attributes_object', 'CHECK (jsonb_typeof(post_attributes) = ''object'')');
SELECT venny_add_constraint('followships', 'ck_followships_attributes_object', 'CHECK (jsonb_typeof(followship_attributes) = ''object'')');
SELECT venny_add_constraint('groups', 'ck_groups_attributes_object', 'CHECK (jsonb_typeof(group_attributes) = ''object'')');
SELECT venny_add_constraint('acknowledgements', 'ck_acknowledgements_attributes_object', 'CHECK (jsonb_typeof(acknowledgement_attributes) = ''object'')');
SELECT venny_add_constraint('comments', 'ck_comments_attributes_object', 'CHECK (jsonb_typeof(comment_attributes) = ''object'')');
SELECT venny_add_constraint('catalogs', 'ck_catalogs_attributes_object', 'CHECK (jsonb_typeof(catalog_attributes) = ''object'')');
SELECT venny_add_constraint('categories', 'ck_categories_attributes_object', 'CHECK (jsonb_typeof(category_attributes) = ''object'')');
SELECT venny_add_constraint('products', 'ck_products_attributes_object', 'CHECK (jsonb_typeof(product_attributes) = ''object'')');
SELECT venny_add_constraint('items', 'ck_items_attributes_object', 'CHECK (jsonb_typeof(item_attributes) = ''object'')');
SELECT venny_add_constraint('transactions', 'ck_transactions_attributes_object', 'CHECK (jsonb_typeof(transaction_attributes) = ''object'')');
SELECT venny_add_constraint('orders', 'ck_orders_attributes_object', 'CHECK (jsonb_typeof(order_attributes) = ''object'')');
SELECT venny_add_constraint('coupons', 'ck_coupons_attributes_object', 'CHECK (jsonb_typeof(coupon_attributes) = ''object'')');
SELECT venny_add_constraint('customers', 'ck_customers_attributes_object', 'CHECK (jsonb_typeof(customer_attributes) = ''object'')');

-- Every tenant-scoped table should point back to apps.
SELECT venny_add_constraint('keys', 'fk_keys_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('sessions', 'fk_sessions_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('windows', 'fk_windows_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('persons', 'fk_persons_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('users', 'fk_users_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('profiles', 'fk_profiles_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('assets', 'fk_assets_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('content', 'fk_content_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('contacts', 'fk_contacts_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('companies', 'fk_companies_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('deals', 'fk_deals_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('pipelines', 'fk_pipelines_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('stages', 'fk_stages_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('activities', 'fk_activities_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('tasks', 'fk_tasks_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('notes', 'fk_notes_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('tags', 'fk_tags_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('communications', 'fk_communications_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('deliveries', 'fk_deliveries_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('threads', 'fk_threads_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('messages', 'fk_messages_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('posts', 'fk_posts_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('followships', 'fk_followships_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('groups', 'fk_groups_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('acknowledgements', 'fk_acknowledgements_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('comments', 'fk_comments_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('catalogs', 'fk_catalogs_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('categories', 'fk_categories_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('products', 'fk_products_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('items', 'fk_items_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('transactions', 'fk_transactions_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('orders', 'fk_orders_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('coupons', 'fk_coupons_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('customers', 'fk_customers_created_for_app', 'FOREIGN KEY (created_for_app_id) REFERENCES apps(app_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');

-- =========================================================
-- 02. Apps, API keys, sessions, and rate-limit windows
-- =========================================================

SELECT venny_add_constraint('apps', 'ck_apps_slug_nonblank', 'CHECK (btrim(app_slug) <> '''')');
SELECT venny_add_constraint('apps', 'ck_apps_name_nonblank', 'CHECK (btrim(app_name) <> '''')');
SELECT venny_add_constraint('apps', 'ck_apps_environment_known', 'CHECK (app_environment IN (''production'', ''staging'', ''development'', ''test''))');
SELECT venny_add_constraint('apps', 'ck_apps_type_nonblank', 'CHECK (btrim(app_type) <> '''')');

SELECT venny_add_constraint('keys', 'ck_keys_rate_limit_positive', 'CHECK (key_ratelimit > 0)');
SELECT venny_add_constraint('keys', 'ck_keys_window_size_positive', 'CHECK (key_windowsize > 0)');
SELECT venny_add_constraint('keys', 'ck_keys_prefix_nonblank', 'CHECK (btrim(key_prefix) <> '''')');
SELECT venny_add_constraint('keys', 'ck_keys_name_nonblank', 'CHECK (btrim(key_name) <> '''')');

SELECT venny_add_constraint('sessions', 'fk_sessions_user', 'FOREIGN KEY (session_user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('sessions', 'ck_sessions_expiry_after_created', 'CHECK (session_expiresat > session_createdat)');
SELECT venny_add_constraint('sessions', 'ck_sessions_revoked_after_created', 'CHECK (session_revokedat IS NULL OR session_revokedat >= session_createdat)');
SELECT venny_add_constraint('sessions', 'ck_sessions_lastseen_after_created', 'CHECK (session_lastseenat IS NULL OR session_lastseenat >= session_createdat)');

SELECT venny_add_constraint('windows', 'fk_windows_key', 'FOREIGN KEY (window_key_id) REFERENCES keys(key_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('windows', 'ck_windows_valid_range', 'CHECK (window_end > window_start)');
SELECT venny_add_constraint('windows', 'ck_windows_count_nonnegative', 'CHECK (window_count >= 0)');
SELECT venny_add_constraint('windows', 'uq_windows_app_key_start', 'UNIQUE (created_for_app_id, window_key_id, window_start)');

-- =========================================================
-- 03. Identity, users, and profiles
-- =========================================================

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

-- =========================================================
-- 04. Assets and CMS content
-- =========================================================

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

SELECT venny_add_constraint('content', 'ck_content_slug_nonblank', 'CHECK (btrim(content_slug) <> '''')');
SELECT venny_add_constraint('content', 'ck_content_title_nonblank', 'CHECK (btrim(content_title) <> '''')');
SELECT venny_add_constraint('content', 'ck_content_valid_date_range', 'CHECK (content_enddate IS NULL OR content_startdate IS NULL OR content_enddate >= content_startdate)');
SELECT venny_add_constraint('content', 'ck_content_tags_json_or_null', 'CHECK (content_tags IS NULL OR jsonb_typeof(content_tags) IN (''array'', ''object''))');
SELECT venny_add_constraint('content', 'uq_content_app_slug', 'UNIQUE (created_for_app_id, content_slug)');

-- =========================================================
-- 05. CRM relationships
-- =========================================================

SELECT venny_add_constraint('contacts', 'fk_contacts_company', 'FOREIGN KEY (contact_company_id) REFERENCES companies(company_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('contacts', 'ck_contacts_emails_object', 'CHECK (jsonb_typeof(contact_emails) = ''object'')');
SELECT venny_add_constraint('contacts', 'ck_contacts_phones_object_or_null', 'CHECK (contact_phones IS NULL OR jsonb_typeof(contact_phones) = ''object'')');

SELECT venny_add_constraint('companies', 'ck_companies_name_nonblank', 'CHECK (btrim(company_name) <> '''')');

SELECT venny_add_constraint('deals', 'fk_deals_contact', 'FOREIGN KEY (deal_contact_id) REFERENCES contacts(contact_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('deals', 'fk_deals_company', 'FOREIGN KEY (deal_company_id) REFERENCES companies(company_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('deals', 'fk_deals_pipeline', 'FOREIGN KEY (deal_pipeline_id) REFERENCES pipelines(pipeline_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('deals', 'fk_deals_stage', 'FOREIGN KEY (deal_stage_id) REFERENCES stages(stage_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('deals', 'ck_deals_amount_nonnegative', 'CHECK (deal_amount IS NULL OR deal_amount >= 0)');
SELECT venny_add_constraint('deals', 'ck_deals_status_nonblank', 'CHECK (btrim(deal_status) <> '''')');

SELECT venny_add_constraint('pipelines', 'ck_pipelines_name_nonblank', 'CHECK (btrim(pipeline_name) <> '''')');
SELECT venny_add_constraint('pipelines', 'uq_pipelines_app_name', 'UNIQUE (created_for_app_id, pipeline_name)');

SELECT venny_add_constraint('stages', 'fk_stages_pipeline', 'FOREIGN KEY (stage_pipeline_id) REFERENCES pipelines(pipeline_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('stages', 'ck_stages_name_nonblank', 'CHECK (btrim(stage_name) <> '''')');
SELECT venny_add_constraint('stages', 'ck_stages_position_nonnegative', 'CHECK (stage_position >= 0)');
SELECT venny_add_constraint('stages', 'ck_stages_probability_range', 'CHECK (stage_probability IS NULL OR (stage_probability >= 0 AND stage_probability <= 100))');
SELECT venny_add_constraint('stages', 'ck_stages_won_requires_closed', 'CHECK (stage_is_won IS NULL OR stage_is_won = false OR stage_is_closed = true)');
SELECT venny_add_constraint('stages', 'uq_stages_pipeline_position', 'UNIQUE (stage_pipeline_id, stage_position)');
SELECT venny_add_constraint('stages', 'uq_stages_pipeline_name', 'UNIQUE (stage_pipeline_id, stage_name)');

SELECT venny_add_constraint('activities', 'fk_activities_contact', 'FOREIGN KEY (activity_contact_id) REFERENCES contacts(contact_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('activities', 'fk_activities_company', 'FOREIGN KEY (activity_company_id) REFERENCES companies(company_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('activities', 'fk_activities_deal', 'FOREIGN KEY (activity_deal_id) REFERENCES deals(deal_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('activities', 'ck_activities_type_nonblank', 'CHECK (btrim(activity_activity_type) <> '''')');
SELECT venny_add_constraint('activities', 'ck_activities_subject_nonblank', 'CHECK (btrim(activity_subject) <> '''')');

SELECT venny_add_constraint('tasks', 'fk_tasks_assigned_to_user', 'FOREIGN KEY (task_assigned_to_user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('tasks', 'fk_tasks_contact', 'FOREIGN KEY (task_contact_id) REFERENCES contacts(contact_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('tasks', 'fk_tasks_company', 'FOREIGN KEY (task_company_id) REFERENCES companies(company_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('tasks', 'fk_tasks_deal', 'FOREIGN KEY (task_deal_id) REFERENCES deals(deal_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('tasks', 'ck_tasks_title_nonblank', 'CHECK (btrim(task_title) <> '''')');
SELECT venny_add_constraint('tasks', 'ck_tasks_completed_after_started', 'CHECK (task_completed_at IS NULL OR task_completed_at >= time_started)');

SELECT venny_add_constraint('notes', 'fk_notes_contact', 'FOREIGN KEY (note_contact_id) REFERENCES contacts(contact_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('notes', 'fk_notes_company', 'FOREIGN KEY (note_company_id) REFERENCES companies(company_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('notes', 'fk_notes_deal', 'FOREIGN KEY (note_deal_id) REFERENCES deals(deal_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('notes', 'ck_notes_body_nonblank', 'CHECK (btrim(note_body) <> '''')');

SELECT venny_add_constraint('tags', 'fk_tags_contact', 'FOREIGN KEY (tag_contact_id) REFERENCES contacts(contact_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('tags', 'fk_tags_company', 'FOREIGN KEY (tag_company_id) REFERENCES companies(company_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('tags', 'fk_tags_deal', 'FOREIGN KEY (tag_deal_id) REFERENCES deals(deal_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('tags', 'ck_tags_name_nonblank', 'CHECK (btrim(tag_name) <> '''')');
SELECT venny_add_constraint('tags', 'ck_tags_one_target_present', 'CHECK (num_nonnulls(tag_related_id, tag_contact_id, tag_company_id, tag_deal_id) >= 1)');

-- =========================================================
-- 06. Communications and delivery queue
-- =========================================================

SELECT venny_add_constraint('communications', 'fk_communications_initiated_by', 'FOREIGN KEY (communication_initiatedby) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('communications', 'ck_communications_recipients_json', 'CHECK (jsonb_typeof(communication_recipients) IN (''array'', ''object''))');

SELECT venny_add_constraint('deliveries', 'fk_deliveries_communication', 'FOREIGN KEY (delivery_communication) REFERENCES communications(communication_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('deliveries', 'ck_deliveries_channel_nonblank', 'CHECK (btrim(delivery_channel) <> '''')');
SELECT venny_add_constraint('deliveries', 'ck_deliveries_attempts_nonnegative', 'CHECK (delivery_attempts >= 0)');
SELECT venny_add_constraint('deliveries', 'ck_deliveries_metadata_object', 'CHECK (jsonb_typeof(delivery_metadata) = ''object'')');

-- =========================================================
-- 07. Chat and messaging
-- =========================================================

SELECT venny_add_constraint('threads', 'fk_threads_author', 'FOREIGN KEY (thread_author_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('threads', 'ck_threads_subject_nonblank', 'CHECK (btrim(thread_subject) <> '''')');
SELECT venny_add_constraint('threads', 'ck_threads_participants_json', 'CHECK (jsonb_typeof(thread_participants) IN (''array'', ''object''))');

SELECT venny_add_constraint('messages', 'fk_messages_thread', 'FOREIGN KEY (thread_id) REFERENCES threads(thread_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('messages', 'fk_messages_sender', 'FOREIGN KEY (message_sender_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('messages', 'ck_messages_body_nonblank', 'CHECK (btrim(message_body) <> '''')');
SELECT venny_add_constraint('messages', 'ck_messages_attachments_json', 'CHECK (jsonb_typeof(message_attachments) IN (''array'', ''object''))');
SELECT venny_add_constraint('messages', 'ck_messages_readby_json', 'CHECK (jsonb_typeof(message_readby) IN (''array'', ''object''))');

-- =========================================================
-- 08. Social/community primitives
-- =========================================================

SELECT venny_add_constraint('posts', 'ck_posts_body_nonblank', 'CHECK (btrim(post_body) <> '''')');
SELECT venny_add_constraint('posts', 'ck_posts_images_json', 'CHECK (post_images IS NULL OR jsonb_typeof(post_images) IN (''array'', ''object''))');

SELECT venny_add_constraint('followships', 'fk_followships_sender', 'FOREIGN KEY (followship_sender_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('followships', 'fk_followships_recipient', 'FOREIGN KEY (followship_recipient_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('followships', 'ck_followships_no_self_follow', 'CHECK (followship_sender_id <> followship_recipient_id)');
SELECT venny_add_constraint('followships', 'ck_followships_status_nonblank', 'CHECK (btrim(followship_status) <> '''')');
SELECT venny_add_constraint('followships', 'uq_followships_app_sender_recipient', 'UNIQUE (created_for_app_id, followship_sender_id, followship_recipient_id)');

SELECT venny_add_constraint('groups', 'fk_groups_sender', 'FOREIGN KEY (group_sender_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('groups', 'fk_groups_recipient', 'FOREIGN KEY (group_recipient_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('groups', 'ck_groups_title_nonblank', 'CHECK (btrim(group_title) <> '''')');
SELECT venny_add_constraint('groups', 'ck_groups_access_nonblank', 'CHECK (btrim(group_access) <> '''')');
SELECT venny_add_constraint('groups', 'ck_groups_participants_json', 'CHECK (group_participants IS NULL OR jsonb_typeof(group_participants) IN (''array'', ''object''))');
SELECT venny_add_constraint('groups', 'ck_groups_images_json', 'CHECK (group_images IS NULL OR jsonb_typeof(group_images) IN (''array'', ''object''))');

SELECT venny_add_constraint('acknowledgements', 'ck_acknowledgements_type_nonblank', 'CHECK (btrim(acknowledgement_type) <> '''')');
SELECT venny_add_constraint('comments', 'ck_comments_body_nonblank', 'CHECK (btrim(comment_body) <> '''')');

-- =========================================================
-- 09. Catalog, categories, products, and inventory items
-- =========================================================

SELECT venny_add_constraint('catalogs', 'ck_catalogs_name_nonblank', 'CHECK (btrim(catalog_name) <> '''')');
SELECT venny_add_constraint('catalogs', 'ck_catalogs_slug_nonblank', 'CHECK (btrim(catalog_slug) <> '''')');
SELECT venny_add_constraint('catalogs', 'ck_catalogs_images_json_or_null', 'CHECK (catalog_images IS NULL OR jsonb_typeof(catalog_images) IN (''array'', ''object''))');
SELECT venny_add_constraint('catalogs', 'uq_catalogs_app_slug', 'UNIQUE (created_for_app_id, catalog_slug)');

SELECT venny_add_constraint('categories', 'fk_categories_catalog', 'FOREIGN KEY (category_catalog_id) REFERENCES catalogs(catalog_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('categories', 'ck_categories_name_nonblank', 'CHECK (btrim(category_name) <> '''')');
SELECT venny_add_constraint('categories', 'ck_categories_slug_nonblank', 'CHECK (btrim(category_slug) <> '''')');
SELECT venny_add_constraint('categories', 'ck_categories_images_json_or_null', 'CHECK (category_images IS NULL OR jsonb_typeof(category_images) IN (''array'', ''object''))');
SELECT venny_add_constraint('categories', 'uq_categories_catalog_slug', 'UNIQUE (category_catalog_id, category_slug)');

SELECT venny_add_constraint('products', 'fk_products_catalog', 'FOREIGN KEY (product_catalog_id) REFERENCES catalogs(catalog_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('products', 'fk_products_category', 'FOREIGN KEY (product_category_id) REFERENCES categories(category_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('products', 'ck_products_name_nonblank', 'CHECK (btrim(product_name) <> '''')');
SELECT venny_add_constraint('products', 'ck_products_slug_nonblank', 'CHECK (btrim(product_slug) <> '''')');
SELECT venny_add_constraint('products', 'ck_products_sku_nonblank', 'CHECK (btrim(product_sku) <> '''')');
SELECT venny_add_constraint('products', 'ck_products_price_nonnegative', 'CHECK (product_base_price >= 0)');
SELECT venny_add_constraint('products', 'ck_products_inventory_nonnegative', 'CHECK (product_inventory >= 0)');
SELECT venny_add_constraint('products', 'ck_products_weight_nonnegative', 'CHECK (product_weight IS NULL OR product_weight >= 0)');
SELECT venny_add_constraint('products', 'ck_products_images_json_or_null', 'CHECK (product_images IS NULL OR jsonb_typeof(product_images) IN (''array'', ''object''))');
SELECT venny_add_constraint('products', 'uq_products_catalog_slug', 'UNIQUE (product_catalog_id, product_slug)');
SELECT venny_add_constraint('products', 'uq_products_catalog_sku', 'UNIQUE (product_catalog_id, product_sku)');

SELECT venny_add_constraint('items', 'fk_items_catalog', 'FOREIGN KEY (item_catalog_id) REFERENCES catalogs(catalog_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('items', 'fk_items_category', 'FOREIGN KEY (item_category_id) REFERENCES categories(category_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('items', 'fk_items_product', 'FOREIGN KEY (item_product_id) REFERENCES products(product_id) ON UPDATE CASCADE ON DELETE CASCADE NOT VALID');
SELECT venny_add_constraint('items', 'ck_items_quantity_nonnegative', 'CHECK (item_quantity IS NULL OR item_quantity >= 0)');
SELECT venny_add_constraint('items', 'ck_items_sale_price_nonnegative', 'CHECK (item_sale_price IS NULL OR item_sale_price >= 0)');
SELECT venny_add_constraint('items', 'ck_items_size_nonnegative', 'CHECK (item_size IS NULL OR item_size >= 0)');
SELECT venny_add_constraint('items', 'uq_items_app_serial_number', 'UNIQUE (created_for_app_id, item_serial_number)');

-- =========================================================
-- 10. Transactions, orders, coupons, and customers
-- =========================================================

SELECT venny_add_constraint('customers', 'ck_customers_email_basic_shape_or_null', 'CHECK (customer_email IS NULL OR position(''@'' in customer_email) > 1)');

SELECT venny_add_constraint('orders', 'fk_orders_customer', 'FOREIGN KEY (order_customer_id) REFERENCES customers(customer_id) ON UPDATE CASCADE ON DELETE SET NULL NOT VALID');
SELECT venny_add_constraint('orders', 'ck_orders_totals_nonnegative', 'CHECK (order_totalproduct >= 0 AND order_totaltax >= 0 AND order_totalshipping >= 0 AND order_totaltaxshipping >= 0)');
SELECT venny_add_constraint('orders', 'ck_orders_adjustment_not_null_or_reasonable', 'CHECK (order_totaladjustment IS NULL OR order_totaladjustment >= 0)');
SELECT venny_add_constraint('orders', 'ck_orders_currency_nonblank', 'CHECK (btrim(order_currency) <> '''')');

SELECT venny_add_constraint('transactions', 'ck_transactions_email_basic_shape_or_null', 'CHECK (transaction_email IS NULL OR position(''@'' in transaction_email) > 1)');
SELECT venny_add_constraint('transactions', 'ck_transactions_amounts_nonnegative', 'CHECK (transaction_subtotal >= 0 AND transaction_discount >= 0 AND transaction_tax >= 0 AND transaction_total >= 0)');
SELECT venny_add_constraint('transactions', 'ck_transactions_attemptcount_nonnegative', 'CHECK (transaction_attemptcount >= 0)');
SELECT venny_add_constraint('transactions', 'ck_transactions_cardlast4_shape_or_null', 'CHECK (transaction_cardlast4 IS NULL OR transaction_cardlast4 ~ ''^[0-9]{4}$'')');
SELECT venny_add_constraint('transactions', 'ck_transactions_currency_nonblank', 'CHECK (btrim(transaction_currency) <> '''')');

SELECT venny_add_constraint('coupons', 'fk_coupons_thread', 'FOREIGN KEY (thread_id) REFERENCES threads(thread_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('coupons', 'fk_coupons_sender', 'FOREIGN KEY (coupon_sender_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT NOT VALID');
SELECT venny_add_constraint('coupons', 'ck_coupons_code_nonblank', 'CHECK (btrim(coupon_code) <> '''')');
SELECT venny_add_constraint('coupons', 'ck_coupons_discounttype_known', 'CHECK (coupon_discounttype IN (''percent'', ''amount'', ''fixed'', ''free_shipping''))');
SELECT venny_add_constraint('coupons', 'ck_coupons_percent_range', 'CHECK (coupon_percent IS NULL OR (coupon_percent > 0 AND coupon_percent <= 100))');
SELECT venny_add_constraint('coupons', 'ck_coupons_amounts_nonnegative', 'CHECK ((coupon_amount IS NULL OR coupon_amount >= 0) AND (coupon_minimumamount IS NULL OR coupon_minimumamount >= 0) AND (coupon_maximumamount IS NULL OR coupon_maximumamount >= 0) AND coupon_subtotal >= 0 AND coupon_discount >= 0 AND coupon_tax >= 0 AND coupon_total >= 0)');
SELECT venny_add_constraint('coupons', 'ck_coupons_min_le_max', 'CHECK (coupon_minimumamount IS NULL OR coupon_maximumamount IS NULL OR coupon_maximumamount >= coupon_minimumamount)');
SELECT venny_add_constraint('coupons', 'ck_coupons_valid_date_range', 'CHECK (coupon_startsat IS NULL OR coupon_expiresat IS NULL OR coupon_expiresat > coupon_startsat)');
SELECT venny_add_constraint('coupons', 'ck_coupons_redemptions_nonnegative', 'CHECK (coupon_redemptions >= 0 AND (coupon_maximumredemptions IS NULL OR coupon_maximumredemptions >= 0))');
SELECT venny_add_constraint('coupons', 'ck_coupons_redemptions_within_max', 'CHECK (coupon_maximumredemptions IS NULL OR coupon_redemptions <= coupon_maximumredemptions)');
SELECT venny_add_constraint('coupons', 'ck_coupons_attemptcount_nonnegative', 'CHECK (coupon_attemptcount >= 0)');
SELECT venny_add_constraint('coupons', 'ck_coupons_cardlast4_shape_or_null', 'CHECK (coupon_cardlast4 IS NULL OR coupon_cardlast4 ~ ''^[0-9]{4}$'')');
SELECT venny_add_constraint('coupons', 'ck_coupons_currency_nonblank', 'CHECK (btrim(coupon_currency) <> '''')');

-- =========================================================
-- 11. Validation notes
-- =========================================================
-- When the data is clean, validate NOT VALID constraints in controlled batches, for example:
-- ALTER TABLE sessions VALIDATE CONSTRAINT fk_sessions_user;
-- ALTER TABLE contacts VALIDATE CONSTRAINT fk_contacts_company;
-- ALTER TABLE messages VALIDATE CONSTRAINT fk_messages_thread;
--
-- I intentionally did not add universal created_by_user_id foreign keys yet.
-- That can create startup/seed-order friction because users themselves also have created_by_user_id.
-- Prefer adding those after you finalize the seed user and migration order.

DROP FUNCTION IF EXISTS venny_add_constraint(TEXT, TEXT, TEXT);

COMMIT;








































