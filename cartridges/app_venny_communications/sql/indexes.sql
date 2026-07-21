-- app_venny_communications indexes

CREATE INDEX IF NOT EXISTS idx_communications_object_lookup
ON communications (communication_parentobject, communication_object_id, created_for_app_id, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_communications_unprocessed_queue
ON communications (created_for_app_id, time_started)
WHERE communication_processed IS NULL AND active = 1;

CREATE INDEX IF NOT EXISTS idx_deliveries_channel_pending
ON deliveries (delivery_channel, created_for_app_id, time_started)
WHERE delivery_sentat IS NULL AND active = 1;

CREATE INDEX IF NOT EXISTS idx_deliveries_attempts_pending
ON deliveries (delivery_attempts, time_started)
WHERE delivery_sentat IS NULL AND active = 1;

CREATE INDEX IF NOT EXISTS idx_deliveries_communication_time
ON deliveries (delivery_communication, time_started DESC)
WHERE delivery_communication IS NOT NULL;

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

CREATE INDEX IF NOT EXISTS idx_communications_process_event
ON communications (process_id, event_id, time_started DESC);

CREATE INDEX IF NOT EXISTS idx_deliveries_process_event
ON deliveries (process_id, event_id, time_started DESC);
