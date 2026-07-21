-- =========================================================
-- Venny I/O Indexes v2 - steps table aligned
-- Purpose: performance-oriented indexes by logical domain.
-- Notes:
--   - Primary keys and inline UNIQUE columns already create indexes.
--   - This file avoids indexing every column; write-heavy tables need restraint.
--   - created_for_app_id is treated as the tenant/app boundary for app-owned tables.
--   - installations and steps currently do not include created_for_app_id in schema.v2.
--   - Run after schema creation. Review before production on very large tables.
-- =========================================================

-- =========================================================
-- Indexes
-- =========================================================

-- ---------------------------------------------------------
-- Setup installer tracking
-- ---------------------------------------------------------
-- Find installation runs by lifecycle status.
CREATE INDEX IF NOT EXISTS idx_installations_status_started
ON installations (installation_status, installation_started_at DESC);

-- Find installation runs by selected experience.
CREATE INDEX IF NOT EXISTS idx_installations_experience_started
ON installations (installation_experience, installation_started_at DESC);

-- Admin lookup for active installation records.
CREATE INDEX IF NOT EXISTS idx_installations_active_status_time
ON installations (active, status, time_started DESC);

-- Find setup steps by status and planned order.
CREATE INDEX IF NOT EXISTS idx_steps_status_order
ON steps (step_status, step_order);

-- Timeline lookup for setup-step execution history.
CREATE INDEX IF NOT EXISTS idx_steps_started_status
ON steps (step_started_at DESC, step_status);

-- Lookup steps by SQL hash during retry/idempotency checks.
CREATE INDEX IF NOT EXISTS idx_steps_sql_hash
ON steps (step_sql_hash)
WHERE step_sql_hash IS NOT NULL;

-- Admin lookup for active step records.
CREATE INDEX IF NOT EXISTS idx_steps_active_status_time
ON steps (active, status, time_started DESC);

-- ---------------------------------------------------------
-- 0. Apps / Platform
-- ---------------------------------------------------------
-- App lookup by environment/type for admin dashboards and platform routing.
CREATE INDEX IF NOT EXISTS idx_apps_environment_type_active
ON apps (app_environment, app_type, active, status);

-- Domain-based app lookup when resolving hostnames or app websites.
CREATE INDEX IF NOT EXISTS idx_apps_domain_active
ON apps (app_domain, active, status)
WHERE app_domain IS NOT NULL;

-- ---------------------------------------------------------
-- 1. API Keys / Rate Limiting / Sessions
-- ---------------------------------------------------------
-- Find active keys for an app during API-key authentication.
CREATE INDEX IF NOT EXISTS idx_keys_app_active_expires
ON keys (created_for_app_id, active, status, key_expires);

-- Fast lookup by key prefix before comparing key_hash.
-- key_hash is already UNIQUE, but key_prefix helps narrow the candidate key family.
CREATE INDEX IF NOT EXISTS idx_keys_prefix_app_active
ON keys (key_prefix, created_for_app_id, active, status);

-- Operational review: most recently used keys per app.
CREATE INDEX IF NOT EXISTS idx_keys_app_lastused
ON keys (created_for_app_id, key_lastused DESC)
WHERE key_lastused IS NOT NULL;

-- Active session lookup by user/app.
CREATE INDEX IF NOT EXISTS idx_sessions_user_app_active
ON sessions (session_user_id, created_for_app_id, session_expiresat)
WHERE session_revokedat IS NULL AND active = 1;

-- Cleanup/expiration scans for sessions.
CREATE INDEX IF NOT EXISTS idx_sessions_expiresat
ON sessions (session_expiresat)
WHERE session_revokedat IS NULL;

-- Security/audit lookup by hashed IP and app.
CREATE INDEX IF NOT EXISTS idx_sessions_iphash_app_time
ON sessions (session_ipaddresshash, created_for_app_id, time_started DESC)
WHERE session_ipaddresshash IS NOT NULL;

-- Rate-limit window lookup/increment path.
CREATE INDEX IF NOT EXISTS idx_windows_key_start_end
ON windows (window_key_id, window_start, window_end);

-- Rate-limit dashboard and cleanup by app/time.
CREATE INDEX IF NOT EXISTS idx_windows_app_time
ON windows (created_for_app_id, window_start DESC);

-- ---------------------------------------------------------
-- 2. Persons / Users / Profiles
-- ---------------------------------------------------------
-- Person intake/search by app/source/time.
CREATE INDEX IF NOT EXISTS idx_persons_app_source_time
ON persons (created_for_app_id, person_source, time_started DESC);

-- Person name lookup within an app.
CREATE INDEX IF NOT EXISTS idx_persons_app_name
ON persons (created_for_app_id, person_lastname, person_firstname)
WHERE active = 1;

-- JSONB email/phone containment lookups, e.g. person_emails @> '{"primary":"x@y.com"}'.
CREATE INDEX IF NOT EXISTS idx_persons_emails_gin
ON persons USING GIN (person_emails);

CREATE INDEX IF NOT EXISTS idx_persons_phones_gin
ON persons USING GIN (person_phones)
WHERE person_phones IS NOT NULL;

-- User login/user listing inside an app.
-- user_email and user_username are globally UNIQUE in current schema, but these help tenant-scoped list/search patterns.
CREATE INDEX IF NOT EXISTS idx_users_app_email_active
ON users (created_for_app_id, user_email, active, status);

CREATE INDEX IF NOT EXISTS idx_users_app_username_active
ON users (created_for_app_id, user_username, active, status)
WHERE user_username IS NOT NULL;

-- Recent login/admin review.
CREATE INDEX IF NOT EXISTS idx_users_app_lastlogin
ON users (created_for_app_id, user_lastlogin DESC)
WHERE user_lastlogin IS NOT NULL;

-- Public profile lookup within app.
CREATE INDEX IF NOT EXISTS idx_profiles_app_username_active
ON profiles (created_for_app_id, profile_username, active, status)
WHERE profile_username IS NOT NULL;

-- Published profile pages.
CREATE INDEX IF NOT EXISTS idx_profiles_app_published
ON profiles (created_for_app_id, profile_biopublished, active, status);

-- ---------------------------------------------------------
-- 3. Assets / Files
-- ---------------------------------------------------------
-- Object attachment lookup: images, credit reports, favicons, cover photos, etc.
CREATE INDEX IF NOT EXISTS idx_assets_object_lookup
ON assets (asset_object_type, asset_object_id, created_for_app_id, active, status);

-- Storage lookup by bucket/key. Useful for dedupe, retrieval, and delete/archive actions.
CREATE INDEX IF NOT EXISTS idx_assets_storage_key
ON assets (asset_storageprovider, asset_bucket, asset_key);

-- App-level asset browsing by category/purpose.
CREATE INDEX IF NOT EXISTS idx_assets_app_category_purpose
ON assets (created_for_app_id, asset_category, asset_purpose, time_started DESC);

-- Processing queue for uploads that need parsing, thumbnails, virus scan, OCR, etc.
CREATE INDEX IF NOT EXISTS idx_assets_processing_queue
ON assets (asset_processingstatus, asset_processingattempts, time_started)
WHERE active = 1 AND asset_processingstatus IS NOT NULL;

-- Uploaded/archived/deleted lifecycle cleanup.
CREATE INDEX IF NOT EXISTS idx_assets_app_lifecycle
ON assets (created_for_app_id, asset_uploadedat DESC, asset_archivedat, asset_deletedat);

-- Checksum lookup for dedupe or integrity checks.
CREATE INDEX IF NOT EXISTS idx_assets_checksum
ON assets (asset_checksum_sha265)
WHERE asset_checksum_sha265 IS NOT NULL;

-- ---------------------------------------------------------
-- 4. CMS Content
-- ---------------------------------------------------------
-- Public content lookup by app/slug.
CREATE INDEX IF NOT EXISTS idx_content_app_slug_visible_active
ON content (created_for_app_id, content_slug, content_visible, active, status);

-- Published content window lookup.
CREATE INDEX IF NOT EXISTS idx_content_app_visibility_dates
ON content (created_for_app_id, content_visible, content_startdate, content_enddate)
WHERE active = 1;

-- Tag filtering for CMS assets.
CREATE INDEX IF NOT EXISTS idx_content_tags_gin
ON content USING GIN (content_tags)
WHERE content_tags IS NOT NULL;

-- Recent content management dashboard.
CREATE INDEX IF NOT EXISTS idx_content_app_time
ON content (created_for_app_id, time_updated DESC, time_started DESC);

-- ---------------------------------------------------------
-- 5. CRM: Contacts / Companies / Deals / Pipelines / Stages
-- ---------------------------------------------------------
-- Contact list/search within app.
CREATE INDEX IF NOT EXISTS idx_contacts_app_name
ON contacts (created_for_app_id, contact_lastname, contact_firstname, active, status);

-- Contacts by company.
CREATE INDEX IF NOT EXISTS idx_contacts_company_app
ON contacts (contact_company_id, created_for_app_id, active, status)
WHERE contact_company_id IS NOT NULL;

-- JSONB email/phone lookups for CRM contact matching.
CREATE INDEX IF NOT EXISTS idx_contacts_emails_gin
ON contacts USING GIN (contact_emails);

CREATE INDEX IF NOT EXISTS idx_contacts_phones_gin
ON contacts USING GIN (contact_phones)
WHERE contact_phones IS NOT NULL;

-- Company list/search within app.
CREATE INDEX IF NOT EXISTS idx_companies_app_name
ON companies (created_for_app_id, company_name, active, status);

-- Company filters for CRM segmentation.
CREATE INDEX IF NOT EXISTS idx_companies_app_industry_state
ON companies (created_for_app_id, company_industry, company_state, active, status);

-- Deal pipeline board: app -> pipeline -> stage -> expected close.
CREATE INDEX IF NOT EXISTS idx_deals_app_pipeline_stage_close
ON deals (created_for_app_id, deal_pipeline_id, deal_stage_id, deal_expectedclosedate, active, status);

-- Deals by contact/company.
CREATE INDEX IF NOT EXISTS idx_deals_contact_app
ON deals (deal_contact_id, created_for_app_id, active, status)
WHERE deal_contact_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_deals_company_app
ON deals (deal_company_id, created_for_app_id, active, status)
WHERE deal_company_id IS NOT NULL;

-- Deal reporting by status and amount.
CREATE INDEX IF NOT EXISTS idx_deals_app_status_amount
ON deals (created_for_app_id, deal_status, deal_amount DESC, time_started DESC);

-- Pipelines by app/name.
CREATE INDEX IF NOT EXISTS idx_pipelines_app_name
ON pipelines (created_for_app_id, pipeline_name, active, status);

-- Ordered stages inside a pipeline.
CREATE INDEX IF NOT EXISTS idx_stages_pipeline_position
ON stages (stage_pipeline_id, stage_position, active, status);

-- Stage reporting by closed/won state.
CREATE INDEX IF NOT EXISTS idx_stages_app_closed_won
ON stages (created_for_app_id, stage_is_closed, stage_is_won, active, status);

-- ---------------------------------------------------------
-- 6. CRM Activity / Tasks / Notes / Tags
-- ---------------------------------------------------------
-- Activity timelines by related object.
CREATE INDEX IF NOT EXISTS idx_activities_related_timeline
ON activities (activity_related_type, activity_related_id, activity_occurred_at DESC, time_started DESC)
WHERE activity_related_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_activities_contact_time
ON activities (activity_contact_id, activity_occurred_at DESC)
WHERE activity_contact_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_activities_company_time
ON activities (activity_company_id, activity_occurred_at DESC)
WHERE activity_company_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_activities_deal_time
ON activities (activity_deal_id, activity_occurred_at DESC)
WHERE activity_deal_id IS NOT NULL;

-- Task queue: assigned open tasks due soon.
CREATE INDEX IF NOT EXISTS idx_tasks_assignee_due_open
ON tasks (task_assigned_to_user_id, task_due_at, task_priority, created_for_app_id)
WHERE task_completed_at IS NULL AND active = 1;

-- Tasks by CRM object.
CREATE INDEX IF NOT EXISTS idx_tasks_contact_due
ON tasks (task_contact_id, task_due_at)
WHERE task_contact_id IS NOT NULL AND task_completed_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_tasks_company_due
ON tasks (task_company_id, task_due_at)
WHERE task_company_id IS NOT NULL AND task_completed_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_tasks_deal_due
ON tasks (task_deal_id, task_due_at)
WHERE task_deal_id IS NOT NULL AND task_completed_at IS NULL;

-- Notes by CRM object.
CREATE INDEX IF NOT EXISTS idx_notes_contact_time
ON notes (note_contact_id, time_started DESC)
WHERE note_contact_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_notes_company_time
ON notes (note_company_id, time_started DESC)
WHERE note_company_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_notes_deal_time
ON notes (note_deal_id, time_started DESC)
WHERE note_deal_id IS NOT NULL;

-- Tag lookup and object tag lists.
CREATE INDEX IF NOT EXISTS idx_tags_app_name_type
ON tags (created_for_app_id, tag_name, tag_type, active, status);

CREATE INDEX IF NOT EXISTS idx_tags_related_lookup
ON tags (tag_related_id, tag_type, created_for_app_id)
WHERE tag_related_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_tags_contact_lookup
ON tags (tag_contact_id, tag_name, created_for_app_id)
WHERE tag_contact_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_tags_company_lookup
ON tags (tag_company_id, tag_name, created_for_app_id)
WHERE tag_company_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_tags_deal_lookup
ON tags (tag_deal_id, tag_name, created_for_app_id)
WHERE tag_deal_id IS NOT NULL;

-- ---------------------------------------------------------
-- 7. Communications / Deliveries
-- ---------------------------------------------------------
-- Find communication records for a parent/object.
CREATE INDEX IF NOT EXISTS idx_communications_object_lookup
ON communications (communication_parentobject, communication_object_id, created_for_app_id, time_started DESC);

-- Queue lookup for unprocessed communications.
CREATE INDEX IF NOT EXISTS idx_communications_unprocessed_queue
ON communications (created_for_app_id, time_started)
WHERE communication_processed IS NULL AND active = 1;

-- Delivery queue by channel for workers.
CREATE INDEX IF NOT EXISTS idx_deliveries_channel_pending
ON deliveries (delivery_channel, created_for_app_id, time_started)
WHERE delivery_sentat IS NULL AND active = 1;

-- Delivery retry management.
CREATE INDEX IF NOT EXISTS idx_deliveries_attempts_pending
ON deliveries (delivery_attempts, time_started)
WHERE delivery_sentat IS NULL AND active = 1;

-- Delivery lookup from communication.
CREATE INDEX IF NOT EXISTS idx_deliveries_communication_time
ON deliveries (delivery_communication, time_started DESC)
WHERE delivery_communication IS NOT NULL;

-- ---------------------------------------------------------
-- 8. Chat / Messaging
-- ---------------------------------------------------------
-- Thread inbox/dashboard: latest thread activity per app/user-created context.
CREATE INDEX IF NOT EXISTS idx_threads_app_lastmessage
ON threads (created_for_app_id, thread_lastmessageat DESC, active, status);

-- Threads authored by a user.
CREATE INDEX IF NOT EXISTS idx_threads_author_time
ON threads (thread_author_id, created_for_app_id, time_started DESC)
WHERE thread_author_id IS NOT NULL;

-- Participant lookup. Useful if thread_participants stores JSON array/object containment.
CREATE INDEX IF NOT EXISTS idx_threads_participants_gin
ON threads USING GIN (thread_participants)
WHERE thread_participants IS NOT NULL;

-- Message timeline inside a thread.
CREATE INDEX IF NOT EXISTS idx_messages_thread_time
ON messages (thread_id, time_started DESC);

-- Messages sent by user.
CREATE INDEX IF NOT EXISTS idx_messages_sender_time
ON messages (message_sender_id, created_for_app_id, time_started DESC)
WHERE message_sender_id IS NOT NULL;

-- Read receipt containment if message_readby remains JSONB.
CREATE INDEX IF NOT EXISTS idx_messages_readby_gin
ON messages USING GIN (message_readby)
WHERE message_readby IS NOT NULL;

-- ---------------------------------------------------------
-- 9. Social / Community Primitives
-- ---------------------------------------------------------
-- Posts by object/feed.
CREATE INDEX IF NOT EXISTS idx_posts_object_time
ON posts (post_object_id, created_for_app_id, time_started DESC)
WHERE post_deleted = false AND active = 1;

-- Replies/comments-style parent lookup for posts.
CREATE INDEX IF NOT EXISTS idx_posts_parent_time
ON posts (post_parent_object_id, time_started DESC)
WHERE post_parent_object_id IS NOT NULL AND post_deleted = false;

-- Follow relationship lookup.
CREATE INDEX IF NOT EXISTS idx_followships_sender_status
ON followships (followship_sender_id, followship_status, created_for_app_id, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_followships_recipient_status
ON followships (followship_recipient_id, followship_status, created_for_app_id, time_started DESC);

-- Group discovery and ownership-ish lookups.
CREATE INDEX IF NOT EXISTS idx_groups_app_access_time
ON groups (created_for_app_id, group_access, active, status, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_groups_sender_time
ON groups (group_sender_id, created_for_app_id, time_started DESC)
WHERE group_sender_id IS NOT NULL;

-- Group participant containment if group_participants is JSONB.
CREATE INDEX IF NOT EXISTS idx_groups_participants_gin
ON groups USING GIN (group_participants)
WHERE group_participants IS NOT NULL;

-- Acknowledgements/reactions by object.
CREATE INDEX IF NOT EXISTS idx_acknowledgements_object_type_time
ON acknowledgements (acknowledgement_object_id, acknowledgement_type, created_for_app_id, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_acknowledgements_parent_time
ON acknowledgements (acknowledgement_parent_object_id, time_started DESC)
WHERE acknowledgement_parent_object_id IS NOT NULL;

-- Comments by object/parent.
CREATE INDEX IF NOT EXISTS idx_comments_object_time
ON comments (comment_object_id, created_for_app_id, time_started DESC)
WHERE active = 1;

CREATE INDEX IF NOT EXISTS idx_comments_parent_time
ON comments (comment_parent_object_id, time_started DESC)
WHERE comment_parent_object_id IS NOT NULL AND active = 1;

-- ---------------------------------------------------------
-- 10. Catalog / Commerce
-- ---------------------------------------------------------
-- Public catalog lookup by slug/app.
CREATE INDEX IF NOT EXISTS idx_catalogs_app_slug_public
ON catalogs (created_for_app_id, catalog_slug, catalog_public, catalog_online, active, status);

-- Catalog browsing.
CREATE INDEX IF NOT EXISTS idx_catalogs_app_online_time
ON catalogs (created_for_app_id, catalog_online, catalog_public, time_started DESC)
WHERE active = 1;

-- Category lookup by catalog/slug.
CREATE INDEX IF NOT EXISTS idx_categories_catalog_slug_public
ON categories (category_catalog_id, category_slug, category_public, category_online, active, status);

-- Category browsing inside catalog.
CREATE INDEX IF NOT EXISTS idx_categories_catalog_online
ON categories (category_catalog_id, category_online, category_public, time_started DESC)
WHERE active = 1;

-- Product lookup by catalog/category/slug.
CREATE INDEX IF NOT EXISTS idx_products_catalog_category_slug
ON products (product_catalog_id, product_category_id, product_slug, product_online, product_public, active, status);

-- Product SKU lookup.
CREATE INDEX IF NOT EXISTS idx_products_sku_app
ON products (created_for_app_id, product_sku)
WHERE product_sku IS NOT NULL;

-- Product browsing/filtering by public/online state.
CREATE INDEX IF NOT EXISTS idx_products_app_public_price
ON products (created_for_app_id, product_public, product_online, product_base_price, active, status);

-- Inventory management.
CREATE INDEX IF NOT EXISTS idx_products_inventory
ON products (created_for_app_id, product_inventory)
WHERE product_inventory IS NOT NULL;

-- Items by product/catalog/category.
CREATE INDEX IF NOT EXISTS idx_items_product_app
ON items (item_product_id, created_for_app_id, active, status)
WHERE item_product_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_items_catalog_category
ON items (item_catalog_id, item_category_id, created_for_app_id, active, status);

-- Serial lookup for physical inventory.
CREATE INDEX IF NOT EXISTS idx_items_serial_number
ON items (item_serial_number)
WHERE item_serial_number IS NOT NULL;

-- ---------------------------------------------------------
-- 11. Transactions / Orders / Coupons / Customers
-- ---------------------------------------------------------
-- Payment provider lookup paths.
CREATE INDEX IF NOT EXISTS idx_transactions_stripe_checkout
ON transactions (transaction_stripecheckoutsessionid)
WHERE transaction_stripecheckoutsessionid IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_transactions_stripe_paymentintent
ON transactions (transaction_stripepaymentintentid)
WHERE transaction_stripepaymentintentid IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_transactions_stripe_customer
ON transactions (transaction_stripecustomerid, created_for_app_id)
WHERE transaction_stripecustomerid IS NOT NULL;

-- Transaction state dashboards.
CREATE INDEX IF NOT EXISTS idx_transactions_app_paid_time
ON transactions (created_for_app_id, transaction_paidat DESC)
WHERE transaction_paidat IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_transactions_app_failed_time
ON transactions (created_for_app_id, transaction_failedat DESC)
WHERE transaction_failedat IS NOT NULL;

-- Customer/email lookup for transactions.
CREATE INDEX IF NOT EXISTS idx_transactions_app_email_time
ON transactions (created_for_app_id, transaction_email, time_started DESC)
WHERE transaction_email IS NOT NULL;

-- Orders by customer and recent order dashboards.
CREATE INDEX IF NOT EXISTS idx_orders_customer_time
ON orders (order_customer_id, created_for_app_id, time_started DESC)
WHERE order_customer_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_orders_app_time
ON orders (created_for_app_id, time_started DESC, active, status);

-- Coupon lookup and redemption management.
CREATE INDEX IF NOT EXISTS idx_coupons_app_code_active
ON coupons (created_for_app_id, coupon_code, active, status);

CREATE INDEX IF NOT EXISTS idx_coupons_active_window
ON coupons (created_for_app_id, coupon_startsat, coupon_expiresat, active, status);

CREATE INDEX IF NOT EXISTS idx_coupons_sender_time
ON coupons (coupon_sender_id, created_for_app_id, time_started DESC)
WHERE coupon_sender_id IS NOT NULL;

-- Customer lookup/search.
CREATE INDEX IF NOT EXISTS idx_customers_app_email
ON customers (created_for_app_id, customer_email, active, status)
WHERE customer_email IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_customers_app_name
ON customers (created_for_app_id, customer_lastname, customer_firstname, active, status);

CREATE INDEX IF NOT EXISTS idx_customers_app_initialcontact
ON customers (created_for_app_id, customer_initialcontact DESC)
WHERE customer_initialcontact IS NOT NULL;

-- ---------------------------------------------------------
-- 12. Common audit/process lookups
-- ---------------------------------------------------------
-- These support event/process tracing without indexing every table blindly.
-- Add more only when a table becomes operationally important.
CREATE INDEX IF NOT EXISTS idx_assets_process_event
ON assets (process_id, event_id, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_communications_process_event
ON communications (process_id, event_id, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_deliveries_process_event
ON deliveries (process_id, event_id, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_transactions_process_event
ON transactions (process_id, event_id, time_started DESC);

-- =========================================================
-- End recommended indexes
-- =========================================================

-- Chatio/session-scoped resource access
CREATE INDEX IF NOT EXISTS idx_sessions_app_active_session
ON sessions (created_for_app_id, session_id, active, status, session_expiresat)
WHERE session_revokedat IS NULL;
