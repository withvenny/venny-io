<?php

declare(strict_types=1);

namespace VennyIO\Repositories;

use PDO;

final class KeysRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * key_hash is deliberately excluded. API responses must never expose it.
     */
    private const SELECT_COLUMNS = <<<'SQL'
        key_id,
        key_attributes,
        key_name,
        key_prefix,
        key_ratelimit,
        key_windowsize,
        key_lastused,
        key_expires,
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

    public function all(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM keys ORDER BY time_started DESC;';

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll() ?: [];

        return array_map([$this, 'normalizeKeyRow'], $rows);
    }

    public function findByKeyId(string $keyId): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM keys WHERE key_id = :key_id LIMIT 1;';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':key_id' => $keyId]);

        $row = $stmt->fetch() ?: [];

        return $this->normalizeKeyRow($row);
    }

    public function create(array $key): array
    {
        $sql = <<<'SQL'
            INSERT INTO keys (
                key_id,
                key_attributes,
                key_name,
                key_prefix,
                key_hash,
                key_ratelimit,
                key_windowsize,
                key_lastused,
                key_expires,
                created_by_user_id,
                created_for_app_id,
                event_id,
                process_id,
                access,
                status,
                active
            ) VALUES (
                :key_id,
                CAST(:key_attributes AS jsonb),
                :key_name,
                :key_prefix,
                :key_hash,
                :key_ratelimit,
                :key_windowsize,
                :key_lastused,
                :key_expires,
                :created_by_user_id,
                :created_for_app_id,
                :event_id,
                :process_id,
                :access,
                :status,
                :active
            )
            RETURNING
                key_id,
                key_attributes,
                key_name,
                key_prefix,
                key_ratelimit,
                key_windowsize,
                key_lastused,
                key_expires,
                created_by_user_id,
                created_for_app_id,
                event_id,
                process_id,
                access,
                status,
                active,
                time_started,
                time_updated;
        SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':key_id' => $key['key_id'],
            ':key_attributes' => $key['key_attributes'] ?? '{}',
            ':key_name' => $key['key_name'],
            ':key_prefix' => $key['key_prefix'],
            ':key_hash' => $key['key_hash'],
            ':key_ratelimit' => $key['key_ratelimit'] ?? 1000,
            ':key_windowsize' => $key['key_windowsize'] ?? 60,
            ':key_lastused' => $key['key_lastused'] ?? null,
            ':key_expires' => $key['key_expires'] ?? null,
            ':created_by_user_id' => $key['created_by_user_id'] ?? 'user_8301',
            ':created_for_app_id' => $key['created_for_app_id'] ?? 'app_8301',
            ':event_id' => $key['event_id'] ?? 'event_8301',
            ':process_id' => $key['process_id'] ?? 'process_8301',
            ':access' => $key['access'] ?? 'private',
            ':status' => $key['status'] ?? 'active',
            ':active' => $key['active'] ?? 1,
        ]);

        $row = $stmt->fetch() ?: [];

        return $this->normalizeKeyRow($row);
    }

    public function update(string $keyId, array $updates): array
    {
        $allowed = [
            'key_attributes',
            'key_name',
            'key_ratelimit',
            'key_windowsize',
            'key_expires',
            'created_by_user_id',
            'created_for_app_id',
            'event_id',
            'process_id',
            'access',
            'status',
            'active',
        ];

        $sets = [];
        $params = [':key_id' => $keyId];

        foreach ($updates as $field => $value) {
            if (!in_array($field, $allowed, true)) {
                continue;
            }

            $placeholder = ':' . $field;
            if ($field === 'key_attributes') {
                $sets[] = $field . ' = CAST(' . $placeholder . ' AS jsonb)';
            } else {
                $sets[] = $field . ' = ' . $placeholder;
            }
            $params[$placeholder] = $value;
        }

        if ($sets === []) {
            return [];
        }

        $sets[] = 'time_updated = now()';

        $sql = sprintf(
            'UPDATE keys SET %s WHERE key_id = :key_id RETURNING %s;',
            implode(', ', $sets),
            self::SELECT_COLUMNS
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch() ?: [];

        return $this->normalizeKeyRow($row);
    }

    public function softDelete(string $keyId): array
    {
        $sql = 'UPDATE keys SET status = \'archived\', active = 0, time_updated = now() WHERE key_id = :key_id RETURNING ' . self::SELECT_COLUMNS . ';';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':key_id' => $keyId]);

        $row = $stmt->fetch() ?: [];

        return $this->normalizeKeyRow($row);
    }

    /**
     * PostgreSQL returns jsonb columns as JSON strings through PDO pgsql.
     * Decode key_attributes before sending API responses so clients see a JSON
     * object instead of escaped JSON text.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeKeyRow(array $row): array
    {
        if ($row === []) {
            return [];
        }

        if (isset($row['key_attributes']) && is_string($row['key_attributes'])) {
            $decoded = json_decode($row['key_attributes'], true);
            $row['key_attributes'] = is_array($decoded) && !array_is_list($decoded) ? $decoded : new \stdClass();
        }

        unset($row['key_hash']);

        return $row;
    }
}
