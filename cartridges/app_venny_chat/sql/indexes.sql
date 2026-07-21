-- app_venny_chat indexes

CREATE INDEX IF NOT EXISTS idx_threads_app_lastmessage
ON threads (created_for_app_id, thread_lastmessageat DESC, active, status);

CREATE INDEX IF NOT EXISTS idx_threads_author_time
ON threads (thread_author_id, created_for_app_id, time_started DESC)
WHERE thread_author_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_threads_participants_gin
ON threads USING GIN (thread_participants)
WHERE thread_participants IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_messages_thread_time
ON messages (thread_id, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_messages_sender_time
ON messages (message_sender_id, created_for_app_id, time_started DESC)
WHERE message_sender_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_messages_readby_gin
ON messages USING GIN (message_readby)
WHERE message_readby IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_threads_process_event
ON threads (process_id, event_id, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_messages_process_event
ON messages (process_id, event_id, time_started DESC);

-- Chatio read/security support
CREATE INDEX IF NOT EXISTS idx_threads_direct_kind
ON threads (created_for_app_id, (thread_attributes->>'kind'), active, status, time_updated DESC);

CREATE INDEX IF NOT EXISTS idx_messages_app_thread_status_time
ON messages (created_for_app_id, thread_id, active, status, time_started ASC);

CREATE INDEX IF NOT EXISTS idx_messages_app_sender_status_time
ON messages (created_for_app_id, message_sender_id, active, status, time_updated DESC);
