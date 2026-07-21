<?php

declare(strict_types=1);

namespace VennyIO\Repositories;

use PDO;

final class ChatRepository
{
    private const THREAD_COLUMNS = <<<'SQL'
        thread_id,
        thread_attributes,
        thread_subject,
        thread_participants,
        thread_lastmessagepreview,
        thread_lastmessageat,
        thread_author_id,
        created_by_user_id,
        created_for_app_id,
        event_id,
        process_id,
        access,
        status,
        active,
        time_started,
        time_updated
    SQL;

    private const MESSAGE_COLUMNS = <<<'SQL'
        message_id,
        message_attributes,
        thread_id,
        message_sender_id,
        message_body,
        message_attachments,
        message_readby,
        created_by_user_id,
        created_for_app_id,
        event_id,
        process_id,
        access,
        status,
        active,
        time_started,
        time_updated
    SQL;

    public function __construct(private PDO $db)
    {
    }

    public function listThreads(string $appId, string $userId, array $filters = []): array
    {
        $where = [
            'created_for_app_id = :app_id',
            'thread_participants @> CAST(:participant AS jsonb)',
        ];
        $params = [
            ':app_id' => $appId,
            ':participant' => json_encode(['users' => [$userId]], JSON_UNESCAPED_SLASHES),
        ];

        $this->applyCommonCollectionFilters($where, $params, $filters);

        $sort = $this->safeSort($filters, [
            'thread_lastmessageat',
            'time_updated',
            'time_started',
            'thread_subject',
        ], 'time_updated');
        $direction = $this->safeDirection($filters);
        [$limit, $offset] = $this->pagination($filters);

        $sql = 'SELECT ' . self::THREAD_COLUMNS . '
            FROM threads
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $sort . ' ' . $direction . ', time_updated DESC
            LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        $this->bindAll($stmt, $params);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'normalizeThread'], $stmt->fetchAll() ?: []);
    }

    public function findThreadForParticipant(string $threadId, string $appId, string $userId, bool $activeOnly = true): array
    {
        $where = [
            'thread_id = :thread_id',
            'created_for_app_id = :app_id',
            'thread_participants @> CAST(:participant AS jsonb)',
        ];
        $params = [
            ':thread_id' => $threadId,
            ':app_id' => $appId,
            ':participant' => json_encode(['users' => [$userId]], JSON_UNESCAPED_SLASHES),
        ];

        if ($activeOnly) {
            $where[] = 'active = 1';
            $where[] = "status = 'active'";
        }

        $stmt = $this->db->prepare('SELECT ' . self::THREAD_COLUMNS . ' FROM threads WHERE ' . implode(' AND ', $where) . ' LIMIT 1');
        $stmt->execute($params);

        return $this->normalizeThread($stmt->fetch() ?: []);
    }

    public function createThread(array $thread): array
    {
        $sql = <<<'SQL'
            INSERT INTO threads (
                thread_id,
                thread_attributes,
                thread_subject,
                thread_participants,
                thread_lastmessagepreview,
                thread_lastmessageat,
                thread_author_id,
                created_by_user_id,
                created_for_app_id,
                event_id,
                process_id,
                access,
                status,
                active
            ) VALUES (
                :thread_id,
                CAST(:thread_attributes AS jsonb),
                :thread_subject,
                CAST(:thread_participants AS jsonb),
                :thread_lastmessagepreview,
                :thread_lastmessageat,
                :thread_author_id,
                :created_by_user_id,
                :created_for_app_id,
                :event_id,
                :process_id,
                :access,
                :status,
                :active
            ) RETURNING
        SQL;
        $sql .= self::THREAD_COLUMNS;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':thread_id' => $thread['thread_id'],
            ':thread_attributes' => $this->jsonObjectString($thread['thread_attributes'] ?? []),
            ':thread_subject' => $thread['thread_subject'],
            ':thread_participants' => $this->jsonObjectString($thread['thread_participants'] ?? []),
            ':thread_lastmessagepreview' => $thread['thread_lastmessagepreview'] ?? '',
            ':thread_lastmessageat' => $thread['thread_lastmessageat'] ?? null,
            ':thread_author_id' => $thread['thread_author_id'],
            ':created_by_user_id' => $thread['created_by_user_id'],
            ':created_for_app_id' => $thread['created_for_app_id'],
            ':event_id' => $thread['event_id'] ?? 'event_8301',
            ':process_id' => $thread['process_id'] ?? 'process_8301',
            ':access' => $thread['access'] ?? 'private',
            ':status' => $thread['status'] ?? 'active',
            ':active' => (int) ($thread['active'] ?? 1),
        ]);

        return $this->normalizeThread($stmt->fetch() ?: []);
    }

    public function updateThreadForParticipant(string $threadId, string $appId, string $userId, array $updates): array
    {
        $allowed = ['thread_subject', 'thread_attributes', 'access', 'status', 'active'];
        $sets = [];
        $params = [
            ':thread_id' => $threadId,
            ':app_id' => $appId,
            ':participant' => json_encode(['users' => [$userId]], JSON_UNESCAPED_SLASHES),
        ];

        foreach ($updates as $column => $value) {
            if (!in_array($column, $allowed, true)) {
                continue;
            }

            $placeholder = ':' . $column;
            if ($column === 'thread_attributes') {
                $sets[] = $column . ' = CAST(' . $placeholder . ' AS jsonb)';
                $params[$placeholder] = $this->jsonObjectString($value);
            } else {
                $sets[] = $column . ' = ' . $placeholder;
                $params[$placeholder] = $column === 'active' ? (int) $value : (is_string($value) ? trim($value) : $value);
            }
        }

        if ($sets === []) {
            return [];
        }

        $sets[] = 'time_updated = now()';

        $sql = 'UPDATE threads
            SET ' . implode(', ', $sets) . '
            WHERE thread_id = :thread_id
              AND created_for_app_id = :app_id
              AND thread_participants @> CAST(:participant AS jsonb)
            RETURNING ' . self::THREAD_COLUMNS;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->normalizeThread($stmt->fetch() ?: []);
    }

    public function archiveThreadForParticipant(string $threadId, string $appId, string $userId): array
    {
        $stmt = $this->db->prepare(<<<'SQL'
            UPDATE threads
            SET status = 'archived',
                active = 0,
                time_updated = now()
            WHERE thread_id = :thread_id
              AND created_for_app_id = :app_id
              AND thread_participants @> CAST(:participant AS jsonb)
              AND active = 1
              AND status = 'active'
            RETURNING
        SQL . self::THREAD_COLUMNS);

        $stmt->execute([
            ':thread_id' => $threadId,
            ':app_id' => $appId,
            ':participant' => json_encode(['users' => [$userId]], JSON_UNESCAPED_SLASHES),
        ]);

        return $this->normalizeThread($stmt->fetch() ?: []);
    }

    public function listMessagesForThread(string $threadId, string $appId, string $userId, array $filters = []): array
    {
        $where = [
            'm.thread_id = :thread_id',
            'm.created_for_app_id = :app_id',
            't.created_for_app_id = :app_id',
            't.thread_participants @> CAST(:participant AS jsonb)',
            't.active = 1',
            "t.status = 'active'",
        ];
        $params = [
            ':thread_id' => $threadId,
            ':app_id' => $appId,
            ':participant' => json_encode(['users' => [$userId]], JSON_UNESCAPED_SLASHES),
        ];

        $this->applyCommonCollectionFilters($where, $params, $filters, 'm.');

        $sort = $this->safeSort($filters, ['time_started', 'time_updated'], 'time_started', 'm.');
        $direction = $this->safeDirection($filters, 'asc');
        [$limit, $offset] = $this->pagination($filters);

        $stmt = $this->db->prepare('SELECT ' . $this->prefixedColumns(self::MESSAGE_COLUMNS, 'm.') . '
            FROM messages m
            INNER JOIN threads t ON t.thread_id = m.thread_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $sort . ' ' . $direction . '
            LIMIT :limit OFFSET :offset');
        $this->bindAll($stmt, $params);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'normalizeMessage'], $stmt->fetchAll() ?: []);
    }

    public function findMessageForParticipant(string $messageId, string $appId, string $userId, bool $activeOnly = true): array
    {
        $where = [
            'm.message_id = :message_id',
            'm.created_for_app_id = :app_id',
            't.created_for_app_id = :app_id',
            't.thread_participants @> CAST(:participant AS jsonb)',
        ];
        $params = [
            ':message_id' => $messageId,
            ':app_id' => $appId,
            ':participant' => json_encode(['users' => [$userId]], JSON_UNESCAPED_SLASHES),
        ];

        if ($activeOnly) {
            $where[] = 'm.active = 1';
            $where[] = "m.status = 'active'";
            $where[] = 't.active = 1';
            $where[] = "t.status = 'active'";
        }

        $stmt = $this->db->prepare('SELECT ' . $this->prefixedColumns(self::MESSAGE_COLUMNS, 'm.') . '
            FROM messages m
            INNER JOIN threads t ON t.thread_id = m.thread_id
            WHERE ' . implode(' AND ', $where) . '
            LIMIT 1');
        $stmt->execute($params);

        return $this->normalizeMessage($stmt->fetch() ?: []);
    }

    public function createMessage(array $message): array
    {
        $sql = <<<'SQL'
            INSERT INTO messages (
                message_id,
                message_attributes,
                thread_id,
                message_sender_id,
                message_body,
                message_attachments,
                message_readby,
                created_by_user_id,
                created_for_app_id,
                event_id,
                process_id,
                access,
                status,
                active
            ) VALUES (
                :message_id,
                CAST(:message_attributes AS jsonb),
                :thread_id,
                :message_sender_id,
                :message_body,
                CAST(:message_attachments AS jsonb),
                CAST(:message_readby AS jsonb),
                :created_by_user_id,
                :created_for_app_id,
                :event_id,
                :process_id,
                :access,
                :status,
                :active
            ) RETURNING
        SQL;
        $sql .= self::MESSAGE_COLUMNS;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':message_id' => $message['message_id'],
            ':message_attributes' => $this->jsonObjectString($message['message_attributes'] ?? []),
            ':thread_id' => $message['thread_id'],
            ':message_sender_id' => $message['message_sender_id'],
            ':message_body' => $message['message_body'],
            ':message_attachments' => $this->jsonObjectString($message['message_attachments'] ?? []),
            ':message_readby' => $this->jsonObjectString($message['message_readby'] ?? []),
            ':created_by_user_id' => $message['created_by_user_id'],
            ':created_for_app_id' => $message['created_for_app_id'],
            ':event_id' => $message['event_id'] ?? 'event_8301',
            ':process_id' => $message['process_id'] ?? 'process_8301',
            ':access' => $message['access'] ?? 'private',
            ':status' => $message['status'] ?? 'active',
            ':active' => (int) ($message['active'] ?? 1),
        ]);

        $created = $this->normalizeMessage($stmt->fetch() ?: []);
        if ($created !== []) {
            $this->touchThreadPreview((string) $created['thread_id'], (string) $created['created_for_app_id'], (string) $created['message_body']);
        }

        return $created;
    }

    public function updateOwnMessage(string $messageId, string $appId, string $userId, array $updates): array
    {
        $allowed = ['message_body', 'message_attributes', 'message_attachments'];
        $sets = [];
        $params = [
            ':message_id' => $messageId,
            ':app_id' => $appId,
            ':user_id' => $userId,
        ];

        foreach ($updates as $column => $value) {
            if (!in_array($column, $allowed, true)) {
                continue;
            }

            $placeholder = ':' . $column;
            if (in_array($column, ['message_attributes', 'message_attachments'], true)) {
                $sets[] = $column . ' = CAST(' . $placeholder . ' AS jsonb)';
                $params[$placeholder] = $this->jsonObjectString($value);
            } else {
                $sets[] = $column . ' = ' . $placeholder;
                $params[$placeholder] = trim((string) $value);
            }
        }

        if ($sets === []) {
            return [];
        }

        $sets[] = 'time_updated = now()';

        $sql = 'UPDATE messages
            SET ' . implode(', ', $sets) . '
            WHERE message_id = :message_id
              AND created_for_app_id = :app_id
              AND message_sender_id = :user_id
              AND active = 1
              AND status = \'active\'
            RETURNING ' . self::MESSAGE_COLUMNS;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->normalizeMessage($stmt->fetch() ?: []);
    }

    public function archiveOwnMessage(string $messageId, string $appId, string $userId): array
    {
        $stmt = $this->db->prepare('UPDATE messages
            SET status = \'archived\',
                active = 0,
                message_body = \'[deleted]\',
                time_updated = now()
            WHERE message_id = :message_id
              AND created_for_app_id = :app_id
              AND message_sender_id = :user_id
              AND active = 1
              AND status = \'active\'
            RETURNING ' . self::MESSAGE_COLUMNS);
        $stmt->execute([
            ':message_id' => $messageId,
            ':app_id' => $appId,
            ':user_id' => $userId,
        ]);

        return $this->normalizeMessage($stmt->fetch() ?: []);
    }

    public function markMessageRead(string $messageId, string $appId, string $userId): array
    {
        $message = $this->findMessageForParticipant($messageId, $appId, $userId, true);
        if ($message === []) {
            return [];
        }

        $readBy = $message['message_readby'];
        if (!is_array($readBy) || array_is_list($readBy)) {
            $readBy = [];
        }
        $readBy[$userId] = gmdate('c');

        $stmt = $this->db->prepare('UPDATE messages
            SET message_readby = CAST(:message_readby AS jsonb),
                time_updated = now()
            WHERE message_id = :message_id
              AND created_for_app_id = :app_id
            RETURNING ' . self::MESSAGE_COLUMNS);
        $stmt->execute([
            ':message_readby' => json_encode($readBy, JSON_UNESCAPED_SLASHES) ?: '{}',
            ':message_id' => $messageId,
            ':app_id' => $appId,
        ]);

        return $this->normalizeMessage($stmt->fetch() ?: []);
    }

    public function findExistingDirectThread(string $appId, string $userId, string $recipientUserId): array
    {
        $stmt = $this->db->prepare('SELECT ' . self::THREAD_COLUMNS . '
            FROM threads
            WHERE created_for_app_id = :app_id
              AND active = 1
              AND status = \'active\'
              AND thread_participants @> CAST(:me AS jsonb)
              AND thread_participants @> CAST(:them AS jsonb)
              AND COALESCE(thread_attributes->>\'kind\', \'\') = \'direct_message\'
            ORDER BY time_updated DESC
            LIMIT 1');
        $stmt->execute([
            ':app_id' => $appId,
            ':me' => json_encode(['users' => [$userId]], JSON_UNESCAPED_SLASHES),
            ':them' => json_encode(['users' => [$recipientUserId]], JSON_UNESCAPED_SLASHES),
        ]);

        return $this->normalizeThread($stmt->fetch() ?: []);
    }

    public function canStartDirectThread(string $appId, string $userId, string $recipientUserId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users
            WHERE user_id = :recipient_user_id
              AND created_for_app_id = :app_id
              AND active = 1
              AND status = \'active\'
            LIMIT 1');
        $stmt->execute([
            ':recipient_user_id' => $recipientUserId,
            ':app_id' => $appId,
        ]);
        if (!$stmt->fetch()) {
            return false;
        }

        $stmt = $this->db->prepare('SELECT 1 FROM followships
            WHERE created_for_app_id = :app_id
              AND active = 1
              AND status = \'active\'
              AND followship_status = \'accepted\'
              AND (
                (followship_sender_id = :user_id AND followship_recipient_id = :recipient_user_id)
                OR
                (followship_sender_id = :recipient_user_id AND followship_recipient_id = :user_id)
              )
            LIMIT 1');
        $stmt->execute([
            ':app_id' => $appId,
            ':user_id' => $userId,
            ':recipient_user_id' => $recipientUserId,
        ]);
        if ($stmt->fetch()) {
            return true;
        }

        $stmt = $this->db->prepare('SELECT 1 FROM profiles
            WHERE created_for_app_id = :app_id
              AND created_by_user_id = :recipient_user_id
              AND access = \'public\'
              AND profile_biopublished = true
              AND active = 1
              AND status = \'active\'
            LIMIT 1');
        $stmt->execute([
            ':app_id' => $appId,
            ':recipient_user_id' => $recipientUserId,
        ]);

        return (bool) $stmt->fetch();
    }

    public function touchThreadPreview(string $threadId, string $appId, string $messageBody): void
    {
        $preview = substr(trim($messageBody), 0, 240);

        $stmt = $this->db->prepare('UPDATE threads
            SET thread_lastmessagepreview = :preview,
                thread_lastmessageat = now(),
                time_updated = now()
            WHERE thread_id = :thread_id
              AND created_for_app_id = :app_id');
        $stmt->execute([
            ':preview' => $preview,
            ':thread_id' => $threadId,
            ':app_id' => $appId,
        ]);
    }

    public function transaction(callable $callback): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $callback();
            $this->db->commit();
            return $result;
        } catch (\Throwable $throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $throwable;
        }
    }

    private function applyCommonCollectionFilters(array &$where, array &$params, array $filters, string $prefix = ''): void
    {
        if (array_key_exists('active', $filters)) {
            $active = (string) $filters['active'];
            if (in_array($active, ['0', '1'], true)) {
                $where[] = $prefix . 'active = :active';
                $params[':active'] = (int) $active;
            }
        } else {
            $where[] = $prefix . 'active = 1';
        }

        if (array_key_exists('status', $filters)) {
            $status = strtolower(trim((string) $filters['status']));
            if ($status !== '') {
                $where[] = $prefix . 'status = :status';
                $params[':status'] = $status;
            }
        } else {
            $where[] = $prefix . "status = 'active'";
        }
    }

    private function safeSort(array $filters, array $allowed, string $default, string $prefix = ''): string
    {
        $sort = trim((string) ($filters['sort'] ?? $default));
        if (!in_array($sort, $allowed, true)) {
            $sort = $default;
        }

        return $prefix . $sort;
    }

    private function safeDirection(array $filters, string $default = 'desc'): string
    {
        $direction = strtolower(trim((string) ($filters['direction'] ?? $default)));
        return $direction === 'asc' ? 'ASC' : 'DESC';
    }

    private function pagination(array $filters): array
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 100), 100));
        $page = max(1, (int) ($filters['page'] ?? 1));

        return [$perPage, ($page - 1) * $perPage];
    }


    private function prefixedColumns(string $columns, string $prefix): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $columns))));
        return implode(', ', array_map(static fn (string $column): string => $prefix . $column, $parts));
    }

    private function bindAll(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
    }

    private function jsonObjectString(mixed $value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (!is_array($value) || array_is_list($value)) {
            $value = [];
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function normalizeThread(array $row): array
    {
        return $this->normalizeJsonFields($row, ['thread_attributes', 'thread_participants']);
    }

    private function normalizeMessage(array $row): array
    {
        return $this->normalizeJsonFields($row, ['message_attributes', 'message_attachments', 'message_readby']);
    }

    private function normalizeJsonFields(array $row, array $fields): array
    {
        if ($row === []) {
            return [];
        }

        foreach ($fields as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $decoded = json_decode($row[$field], true);
                $row[$field] = is_array($decoded) ? $decoded : new \stdClass();
            }
        }

        return $row;
    }
}
