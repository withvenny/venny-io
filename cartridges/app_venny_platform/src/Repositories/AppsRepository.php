<?php

declare(strict_types=1);

namespace VennyIO\Repositories;

use PDO;

final class AppsRepository
{
    public function __construct(private PDO $db)
    {
    }

    private const SELECT_COLUMNS = <<<'SQL'
        app_id,
        app_attributes,
        app_name,
        app_slug,
        app_description,
        app_domain,
        app_website,
        app_contactname,
        app_contactemail,
        app_contactphone,
        app_environment,
        app_type,
        created_by_user_id,
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
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM apps ORDER BY time_started DESC;';

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll() ?: [];

        return array_map([$this, 'normalizeAppRow'], $rows);
    }

    public function findByAppId(string $appId): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM apps WHERE app_id = :app_id LIMIT 1;';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':app_id' => $appId]);

        $row = $stmt->fetch() ?: [];

        return $this->normalizeAppRow($row);
    }

    public function create(array $app): array
    {
        $sql = <<<'SQL'
            INSERT INTO apps (
                app_id,
                app_attributes,
                app_name,
                app_slug,
                app_description,
                app_domain,
                app_website,
                app_contactname,
                app_contactemail,
                app_contactphone,
                app_environment,
                app_type,
                created_by_user_id,
                event_id,
                process_id,
                access,
                status,
                active
            ) VALUES (
                :app_id,
                CAST(:app_attributes AS jsonb),
                :app_name,
                :app_slug,
                :app_description,
                :app_domain,
                :app_website,
                :app_contactname,
                :app_contactemail,
                :app_contactphone,
                :app_environment,
                :app_type,
                :created_by_user_id,
                :event_id,
                :process_id,
                :access,
                :status,
                :active
            )
            RETURNING
                app_id,
                app_attributes,
                app_name,
                app_slug,
                app_description,
                app_domain,
                app_website,
                app_contactname,
                app_contactemail,
                app_contactphone,
                app_environment,
                app_type,
                created_by_user_id,
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
            ':app_id' => $app['app_id'],
            ':app_attributes' => $app['app_attributes'] ?? '{}',
            ':app_name' => $app['app_name'],
            ':app_slug' => $app['app_slug'],
            ':app_description' => $app['app_description'],
            ':app_domain' => $app['app_domain'] ?? null,
            ':app_website' => $app['app_website'] ?? null,
            ':app_contactname' => $app['app_contactname'] ?? null,
            ':app_contactemail' => $app['app_contactemail'] ?? null,
            ':app_contactphone' => $app['app_contactphone'] ?? null,
            ':app_environment' => $app['app_environment'] ?? 'production',
            ':app_type' => $app['app_type'] ?? 'internal',
            ':created_by_user_id' => $app['created_by_user_id'] ?? 'user_8301',
            ':event_id' => $app['event_id'] ?? 'event_8301',
            ':process_id' => $app['process_id'] ?? 'process_8301',
            ':access' => $app['access'] ?? 'private',
            ':status' => $app['status'] ?? 'active',
            ':active' => $app['active'] ?? 1,
        ]);

        $row = $stmt->fetch() ?: [];

        return $this->normalizeAppRow($row);
    }

    public function update(string $appId, array $updates): array
    {
        $allowed = [
            'app_attributes',
            'app_name',
            'app_slug',
            'app_description',
            'app_domain',
            'app_website',
            'app_contactname',
            'app_contactemail',
            'app_contactphone',
            'app_environment',
            'app_type',
            'status',
            'active',
        ];

        $sets = [];
        $params = [':app_id' => $appId];

        foreach ($updates as $field => $value) {
            if (!in_array($field, $allowed, true)) {
                continue;
            }

            $placeholder = ':' . $field;
            if ($field === 'app_attributes') {
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
            'UPDATE apps SET %s WHERE app_id = :app_id RETURNING %s;',
            implode(', ', $sets),
            self::SELECT_COLUMNS
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch() ?: [];

        return $this->normalizeAppRow($row);
    }

    public function softDelete(string $appId): array
    {
        $sql = 'UPDATE apps SET status = \'archived\', active = 0, time_updated = now() WHERE app_id = :app_id RETURNING ' . self::SELECT_COLUMNS . ';';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':app_id' => $appId]);

        $row = $stmt->fetch() ?: [];

        return $this->normalizeAppRow($row);
    }

    /**
     * PostgreSQL returns jsonb columns as JSON strings through PDO pgsql.
     * Decode app_attributes before sending API responses so clients see a JSON
     * object instead of escaped JSON text.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeAppRow(array $row): array
    {
        if ($row === []) {
            return [];
        }

        if (isset($row['app_attributes']) && is_string($row['app_attributes'])) {
            $decoded = json_decode($row['app_attributes'], true);
            $row['app_attributes'] = is_array($decoded) && !array_is_list($decoded) ? $decoded : new \stdClass();
        }

        return $row;
    }
}
