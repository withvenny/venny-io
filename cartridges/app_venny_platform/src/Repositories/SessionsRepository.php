<?php

declare(strict_types=1);

namespace VennyIO\Repositories;

use PDO;

final class SessionsRepository
{
    private const SELECT_COLUMNS = <<<'SQL'
        session_id,
        session_attributes,
        session_ipaddresshash,
        session_ipcountryhash,
        session_user_id,
        session_useragent,
        session_expiresat,
        session_revokedat,
        session_createdat,
        session_lastseenat,
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

    public function all(): array
    {
        $stmt = $this->db->query('SELECT ' . self::SELECT_COLUMNS . ' FROM sessions ORDER BY time_started DESC;');
        $rows = $stmt->fetchAll() ?: [];
        return array_map([$this, 'normalize'], $rows);
    }

    public function find(string $sessionId): array
    {
        $stmt = $this->db->prepare('SELECT ' . self::SELECT_COLUMNS . ' FROM sessions WHERE session_id = :session_id LIMIT 1;');
        $stmt->execute([':session_id' => $sessionId]);
        return $this->normalize($stmt->fetch() ?: []);
    }

    public function create(array $session): array
    {
        $sql = <<<'SQL'
            INSERT INTO sessions (
                session_id,
                session_attributes,
                session_refreshtokenhash,
                session_ipaddresshash,
                session_ipcountryhash,
                session_user_id,
                session_useragent,
                session_expiresat,
                session_revokedat,
                session_createdat,
                session_lastseenat,
                created_by_user_id,
                created_for_app_id,
                event_id,
                process_id,
                access,
                status,
                active
            ) VALUES (
                :session_id,
                CAST(:session_attributes AS jsonb),
                :session_refreshtokenhash,
                :session_ipaddresshash,
                :session_ipcountryhash,
                :session_user_id,
                :session_useragent,
                :session_expiresat,
                :session_revokedat,
                now(),
                :session_lastseenat,
                :created_by_user_id,
                :created_for_app_id,
                :event_id,
                :process_id,
                :access,
                :status,
                :active
            ) RETURNING 
        SQL;
        $sql .= self::SELECT_COLUMNS . ';';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':session_id' => $session['session_id'],
            ':session_attributes' => $session['session_attributes'] ?? '{}',
            ':session_refreshtokenhash' => $session['session_refreshtokenhash'],
            ':session_ipaddresshash' => $session['session_ipaddresshash'] ?? null,
            ':session_ipcountryhash' => $session['session_ipcountryhash'] ?? null,
            ':session_user_id' => $session['session_user_id'] ?? null,
            ':session_useragent' => $session['session_useragent'],
            ':session_expiresat' => $session['session_expiresat'],
            ':session_revokedat' => $session['session_revokedat'] ?? null,
            ':session_lastseenat' => $session['session_lastseenat'] ?? null,
            ':created_by_user_id' => $session['created_by_user_id'] ?? 'user_8301',
            ':created_for_app_id' => $session['created_for_app_id'] ?? 'app_8301',
            ':event_id' => $session['event_id'] ?? 'event_8301',
            ':process_id' => $session['process_id'] ?? 'process_8301',
            ':access' => $session['access'] ?? 'private',
            ':status' => $session['status'] ?? 'active',
            ':active' => $session['active'] ?? 1,
        ]);

        return $this->normalize($stmt->fetch() ?: []);
    }

    public function update(string $sessionId, array $updates): array
    {
        $allowed = [
            'session_attributes',
            'session_ipaddresshash',
            'session_ipcountryhash',
            'session_user_id',
            'session_useragent',
            'session_expiresat',
            'session_revokedat',
            'session_lastseenat',
            'created_by_user_id',
            'created_for_app_id',
            'event_id',
            'process_id',
            'access',
            'status',
            'active',
        ];

        $sets = [];
        $params = [':session_id' => $sessionId];

        foreach ($updates as $field => $value) {
            if (!in_array($field, $allowed, true)) {
                continue;
            }
            $placeholder = ':' . $field;
            $sets[] = $field === 'session_attributes'
                ? $field . ' = CAST(' . $placeholder . ' AS jsonb)'
                : $field . ' = ' . $placeholder;
            $params[$placeholder] = $value;
        }

        if ($sets === []) {
            return [];
        }

        $sets[] = 'time_updated = now()';
        $sql = 'UPDATE sessions SET ' . implode(', ', $sets) . ' WHERE session_id = :session_id RETURNING ' . self::SELECT_COLUMNS . ';';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->normalize($stmt->fetch() ?: []);
    }

    public function softDelete(string $sessionId): array
    {
        $sql = 'UPDATE sessions SET status = \'archived\', active = 0, session_revokedat = COALESCE(session_revokedat, now()), time_updated = now() WHERE session_id = :session_id RETURNING ' . self::SELECT_COLUMNS . ';';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':session_id' => $sessionId]);
        return $this->normalize($stmt->fetch() ?: []);
    }

    private function normalize(array $row): array
    {
        if ($row === []) {
            return [];
        }

        if (isset($row['session_attributes']) && is_string($row['session_attributes'])) {
            $decoded = json_decode($row['session_attributes'], true);
            $row['session_attributes'] = is_array($decoded) && !array_is_list($decoded) ? $decoded : new \stdClass();
        }

        return $row;
    }
}
