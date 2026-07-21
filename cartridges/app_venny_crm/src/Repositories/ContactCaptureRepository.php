<?php

declare(strict_types=1);

namespace VennyIO\Repositories;

use PDO;
use VennyIO\Support\Ids;

final class ContactCaptureRepository
{
    private const CONTACT_COLUMNS = <<<'SQL'
        contact_id,
        contact_attributes,
        contact_firstname,
        contact_middlename,
        contact_lastname,
        contact_emails,
        contact_phones,
        contact_company_id,
        contact_source,
        contact_title,
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

    public function createUpdateSignup(array $payload): array
    {
        $contactId = Ids::generate('contact');
        $sql = <<<'SQL'
            INSERT INTO contacts (
                contact_id,
                contact_attributes,
                contact_firstname,
                contact_middlename,
                contact_lastname,
                contact_emails,
                contact_phones,
                contact_source,
                contact_title,
                created_by_user_id,
                created_for_app_id,
                event_id,
                process_id,
                access,
                status,
                active
            ) VALUES (
                :contact_id,
                CAST(:contact_attributes AS jsonb),
                :contact_firstname,
                :contact_middlename,
                :contact_lastname,
                CAST(:contact_emails AS jsonb),
                CAST(:contact_phones AS jsonb),
                CAST(:contact_source AS jsonb),
                :contact_title,
                :created_by_user_id,
                :created_for_app_id,
                :event_id,
                :process_id,
                :access,
                :status,
                :active
            ) RETURNING
        SQL;
        $sql .= ' ' . self::CONTACT_COLUMNS . ';';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':contact_id' => $contactId,
            ':contact_attributes' => $payload['contact_attributes'] ?? '{}',
            ':contact_firstname' => $payload['contact_firstname'] ?? null,
            ':contact_middlename' => $payload['contact_middlename'] ?? null,
            ':contact_lastname' => $payload['contact_lastname'] ?? null,
            ':contact_emails' => $payload['contact_emails'],
            ':contact_phones' => $payload['contact_phones'] ?? '{}',
            ':contact_source' => $payload['contact_source'] ?? '{}',
            ':contact_title' => $payload['contact_title'] ?? null,
            ':created_by_user_id' => $payload['created_by_user_id'] ?? 'user_8301',
            ':created_for_app_id' => $payload['created_for_app_id'],
            ':event_id' => $payload['event_id'] ?? 'event_8301',
            ':process_id' => $payload['process_id'] ?? 'process_8301',
            ':access' => $payload['access'] ?? 'private',
            ':status' => $payload['status'] ?? 'active',
            ':active' => (int) ($payload['active'] ?? 1),
        ]);

        return $this->normalizeContact($stmt->fetch() ?: []);
    }

    private function normalizeContact(array $row): array
    {
        if ($row === []) {
            return [];
        }

        foreach (['contact_attributes', 'contact_emails', 'contact_phones', 'contact_source'] as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $decoded = json_decode($row[$field], true);
                $row[$field] = is_array($decoded) ? $decoded : new \stdClass();
            }
        }

        return $row;
    }
}
