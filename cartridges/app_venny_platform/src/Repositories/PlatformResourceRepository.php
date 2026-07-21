<?php

declare(strict_types=1);

namespace VennyIO\Repositories;

use PDO;

final class PlatformResourceRepository
{
    public function __construct(
        private PDO $db,
        private string $table,
        private string $primaryKey,
        private array $columns,
        private array $jsonFields = [],
        private array $hiddenFields = []
    ) {
    }

    /*public function all(): array
    {
        $sql = 'SELECT ' . $this->selectColumns() . ' FROM ' . $this->table . ' ORDER BY time_started DESC;';
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll() ?: [];

        return array_map([$this, 'normalizeRow'], $rows);
    }*/

    public function all(array $filters = []): array {
        $where = [];
        $params = [];

        // Chatio: /threads?participant_user_id=user_123
        // thread_participants shape:
        // {
        //   "users": ["user_123", "user_456"]
        // }
        if ($this->table === 'threads') {
            $participantUserId = trim((string) ($filters['participant_user_id'] ?? ''));

            if ($participantUserId !== '') {
                $where[] = 'thread_participants @> CAST(:participant_user_filter AS jsonb)';
                $params[':participant_user_filter'] = json_encode([
                    'users' => [$participantUserId],
                ], JSON_UNESCAPED_SLASHES);
            }
        }

        // Chatio: /messages?thread_id=thread_123
        // Return only messages that belong to the selected thread.
        if ($this->table === 'messages') {
            $threadId = trim((string) ($filters['thread_id'] ?? ''));

            if ($threadId !== '') {
                $where[] = 'thread_id = :thread_id';
                $params[':thread_id'] = $threadId;
            }
        }

        // Chatio: /profiles?search=ada
        // Public people discovery should use profiles rather than raw users.
        if ($this->table === 'profiles') {
            $search = trim((string) ($filters['search'] ?? ''));

            if ($search !== '') {
                $where[] = '(profile_username ILIKE :profile_search OR profile_displayname ILIKE :profile_search OR profile_bio ILIKE :profile_search)';
                $params[':profile_search'] = '%' . $this->escapeLike($search) . '%';
            }

            if (array_key_exists('profile_username', $filters)) {
                $profileUsername = strtolower(trim((string) $filters['profile_username']));

                if ($profileUsername !== '') {
                    $where[] = 'profile_username = :profile_username';
                    $params[':profile_username'] = $profileUsername;
                }
            }

            if (array_key_exists('profile_biopublished', $filters)) {
                $profileBioPublished = $this->booleanFilterValue($filters['profile_biopublished']);

                if ($profileBioPublished !== null) {
                    $where[] = 'profile_biopublished = :profile_biopublished';
                    $params[':profile_biopublished'] = $profileBioPublished;
                }
            }
        }

        // Chatio: /followships?user_id=user_123&followship_status=accepted
        // Return relationship records where the current user is either side.
        // Direct sender / recipient / status filters remain available for diagnostics and admin-style lookups.
        if ($this->table === 'followships') {
            $userId = trim((string) ($filters['user_id'] ?? ''));

            if ($userId !== '') {
                $where[] = '(followship_sender_id = :followship_user_id OR followship_recipient_id = :followship_user_id)';
                $params[':followship_user_id'] = $userId;
            }

            $this->addExactTextFilter($where, $params, $filters, 'followship_sender_id');
            $this->addExactTextFilter($where, $params, $filters, 'followship_recipient_id');
            $this->addExactTextFilter($where, $params, $filters, 'followship_status', true);
        }

        if (array_key_exists('access', $filters) && in_array('access', $this->columns, true)) {
            $access = trim((string) $filters['access']);
            if ($access !== '') {
                $where[] = 'access = :access';
                $params[':access'] = strtolower($access);
            }
        }

        if (array_key_exists('created_by_user_id', $filters) && in_array('created_by_user_id', $this->columns, true)) {
            $createdByUserId = trim((string) $filters['created_by_user_id']);
            if ($createdByUserId !== '') {
                $where[] = 'created_by_user_id = :created_by_user_id';
                $params[':created_by_user_id'] = $createdByUserId;
            }
        }

        if (array_key_exists('active', $filters) && in_array('active', $this->columns, true)) {
            $active = (string) $filters['active'];
            if (in_array($active, ['0', '1'], true)) {
                $where[] = 'active = :active';
                $params[':active'] = (int) $active;
            }
        }

        if (array_key_exists('status', $filters) && in_array('status', $this->columns, true)) {
            $status = trim((string) $filters['status']);
            if ($status !== '') {
                $where[] = 'status = :status';
                $params[':status'] = strtolower($status);
            }
        }

        $sort = (string) ($filters['sort'] ?? 'time_started');
        if (!in_array($sort, $this->columns, true)) {
            $sort = 'time_started';
        }

        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $perPage = (int) ($filters['per_page'] ?? 100);
        $perPage = max(1, min($perPage, 100));

        $page = (int) ($filters['page'] ?? 1);
        $page = max(1, $page);

        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT ' . $this->selectColumns() . ' FROM ' . $this->table;

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY ' . $sort . ' ' . $direction . ' LIMIT :limit OFFSET :offset;';

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $rows = $stmt->fetchAll() ?: [];

        return array_map([$this, 'normalizeRow'], $rows);
    }

    

    public function find(string $id): array
    {
        $sql = 'SELECT ' . $this->selectColumns() . ' FROM ' . $this->table . ' WHERE ' . $this->primaryKey . ' = :id LIMIT 1;';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $this->normalizeRow($stmt->fetch() ?: []);
    }

    public function findOneBy(array $criteria): array
    {
        $where = [];
        $params = [];

        foreach ($criteria as $column => $value) {
            if (!in_array($column, $this->columns, true)) {
                continue;
            }

            $placeholder = ':' . $column;
            $where[] = $column . ' = ' . $placeholder;
            $params[$placeholder] = $this->normalizeParamValue($value);
        }

        if ($where === []) {
            return [];
        }

        $sql = 'SELECT ' . $this->selectColumns() . ' FROM ' . $this->table . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY time_updated DESC LIMIT 1;';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->normalizeRow($stmt->fetch() ?: []);
    }

    public function create(array $payload): array
    {
        $insertColumns = array_values(array_filter($this->columns, static fn (string $column): bool => $column !== 'time_started' && $column !== 'time_updated'));
        $columnSql = implode(', ', $insertColumns);
        $valueSql = implode(', ', array_map(function (string $column): string {
            $placeholder = ':' . $column;
            return in_array($column, $this->jsonFields, true) ? 'CAST(' . $placeholder . ' AS jsonb)' : $placeholder;
        }, $insertColumns));

        $sql = 'INSERT INTO ' . $this->table . ' (' . $columnSql . ') VALUES (' . $valueSql . ') RETURNING ' . $this->selectColumns() . ';';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->bindParams($insertColumns, $payload));

        return $this->normalizeRow($stmt->fetch() ?: []);
    }

    public function update(string $id, array $updates, array $allowedColumns): array
    {
        $sets = [];
        $params = [':id' => $id];

        foreach ($updates as $column => $value) {
            if (!in_array($column, $allowedColumns, true) || !in_array($column, $this->columns, true)) {
                continue;
            }

            $placeholder = ':' . $column;
            $sets[] = in_array($column, $this->jsonFields, true)
                ? $column . ' = CAST(' . $placeholder . ' AS jsonb)'
                : $column . ' = ' . $placeholder;
            $params[$placeholder] = $this->normalizeParamValue($value);
        }

        if ($sets === []) {
            return [];
        }

        $sets[] = 'time_updated = now()';
        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $this->primaryKey . ' = :id RETURNING ' . $this->selectColumns() . ';';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->normalizeRow($stmt->fetch() ?: []);
    }

    public function softDelete(string $id): array
    {
        $sql = 'UPDATE ' . $this->table . ' SET status = \'archived\', active = 0, time_updated = now() WHERE ' . $this->primaryKey . ' = :id RETURNING ' . $this->selectColumns() . ';';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $this->normalizeRow($stmt->fetch() ?: []);
    }

    private function selectColumns(): string
    {
        return implode(', ', $this->columns);
    }

    private function bindParams(array $columns, array $payload): array
    {
        $params = [];
        foreach ($columns as $column) {
            $params[':' . $column] = $this->normalizeParamValue($payload[$column] ?? null);
        }
        return $params;
    }

    private function normalizeParamValue(mixed $value): mixed
    {
        // PDO pgsql can send PHP false as an empty string when using execute($params).
        // Postgres rejects "" for boolean columns. Send explicit boolean text.
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $value;
    }


    private function addExactTextFilter(array &$where, array &$params, array $filters, string $column, bool $lowercase = false): void
    {
        if (!array_key_exists($column, $filters)) {
            return;
        }

        $value = trim((string) $filters[$column]);
        if ($value === '') {
            return;
        }

        $where[] = $column . ' = :' . $column;
        $params[':' . $column] = $lowercase ? strtolower($value) : $value;
    }

    private function booleanFilterValue(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '1', 'true', 'yes', 'on' => 'true',
            '0', 'false', 'no', 'off' => 'false',
            default => null,
        };
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function normalizeRow(array $row): array
    {
        if ($row === []) {
            return [];
        }

        foreach ($this->jsonFields as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $decoded = json_decode($row[$field], true);
                if (is_array($decoded)) {
                    $row[$field] = $decoded;
                } else {
                    $row[$field] = new \stdClass();
                }
            }
        }

        foreach ($this->hiddenFields as $field) {
            unset($row[$field]);
        }

        return $row;
    }
}
