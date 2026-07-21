<?php

declare(strict_types=1);

namespace VennyIO\Repositories;

use PDO;
use VennyIO\Support\Ids;

final class AccountRepository
{
    private const USER_PUBLIC_COLUMNS = <<<'SQL'
        user_id,
        user_attributes,
        user_email,
        user_addresses,
        user_phones,
        user_optins,
        user_username,
        user_displayname,
        user_bio,
        user_avatarurl,
        user_theme,
        user_biopublished,
        user_lastlogin,
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

    private const USER_PRIVATE_COLUMNS = <<<'SQL'
        user_id,
        user_attributes,
        user_email,
        user_addresses,
        user_phones,
        user_optins,
        user_passwordhash,
        user_username,
        user_displayname,
        user_bio,
        user_avatarurl,
        user_theme,
        user_biopublished,
        user_lastlogin,
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

    private const PERSON_COLUMNS = <<<'SQL'
        person_id,
        person_attributes,
        person_firstname,
        person_middlename,
        person_lastname,
        person_emails,
        person_phones,
        person_addresses,
        person_dateofbirth,
        person_smsoptindate,
        person_source,
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

    private const PROFILE_COLUMNS = <<<'SQL'
        profile_id,
        profile_attributes,
        profile_username,
        profile_displayname,
        profile_bio,
        profile_avatarurl,
        profile_theme,
        profile_biopublished,
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

    private const SESSION_COLUMNS = <<<'SQL'
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

    public function account(string $userId, string $appId): array
    {
        return [
            'user' => $this->findUser($userId, $appId, false),
            'person' => $this->findPersonForUser($userId, $appId),
            'profile' => $this->findProfileForUser($userId, $appId),
        ];
    }

    public function findUser(string $userId, string $appId, bool $includePasswordHash = false): array
    {
        $columns = $includePasswordHash ? self::USER_PRIVATE_COLUMNS : self::USER_PUBLIC_COLUMNS;
        $stmt = $this->db->prepare('SELECT ' . $columns . ' FROM users WHERE user_id = :user_id AND created_for_app_id = :app_id AND active = 1 AND status = \'active\' LIMIT 1;');
        $stmt->execute([
            ':user_id' => $userId,
            ':app_id' => $appId,
        ]);

        return $this->normalizeUser($stmt->fetch() ?: [], $includePasswordHash);
    }

    public function findPersonForUser(string $userId, string $appId): array
    {
        $stmt = $this->db->prepare('SELECT ' . self::PERSON_COLUMNS . ' FROM persons WHERE created_by_user_id = :user_id AND created_for_app_id = :app_id AND active = 1 AND status = \'active\' ORDER BY time_updated DESC LIMIT 1;');
        $stmt->execute([
            ':user_id' => $userId,
            ':app_id' => $appId,
        ]);

        return $this->normalizePerson($stmt->fetch() ?: []);
    }

    public function findProfileForUser(string $userId, string $appId): array
    {
        $stmt = $this->db->prepare('SELECT ' . self::PROFILE_COLUMNS . ' FROM profiles WHERE created_by_user_id = :user_id AND created_for_app_id = :app_id AND active = 1 AND status = \'active\' ORDER BY time_updated DESC LIMIT 1;');
        $stmt->execute([
            ':user_id' => $userId,
            ':app_id' => $appId,
        ]);

        return $this->normalizeProfile($stmt->fetch() ?: []);
    }

    public function updateAccount(string $userId, string $appId, array $changes): array
    {
        $this->db->beginTransaction();

        try {
            $user = $this->findUser($userId, $appId, false);
            if ($user === []) {
                $this->db->rollBack();
                return [];
            }

            $person = $this->findPersonForUser($userId, $appId);
            if ($person === []) {
                $person = $this->createPersonForUser($user, $changes);
            }

            $profile = $this->findProfileForUser($userId, $appId);
            if ($profile === []) {
                $profile = $this->createProfileForUser($user, $changes, $person);
            }

            $updatedPerson = $this->updatePerson($person['person_id'], $userId, $appId, $changes);
            $updatedUser = $this->updateUser($userId, $appId, $changes);
            $updatedProfile = $this->updateProfile($profile['profile_id'], $userId, $appId, $changes);

            $this->linkIdentityAttributes(
                $userId,
                $appId,
                (string) ($updatedPerson['person_id'] ?? $person['person_id']),
                (string) ($updatedProfile['profile_id'] ?? $profile['profile_id'])
            );

            $this->db->commit();

            return $this->account($userId, $appId);
        } catch (\Throwable $throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $throwable;
        }
    }

    public function updatePassword(string $userId, string $appId, string $passwordHash): array
    {
        $stmt = $this->db->prepare('UPDATE users SET user_passwordhash = :password_hash, time_updated = now() WHERE user_id = :user_id AND created_for_app_id = :app_id AND active = 1 AND status = \'active\' RETURNING ' . self::USER_PUBLIC_COLUMNS . ';');
        $stmt->execute([
            ':password_hash' => $passwordHash,
            ':user_id' => $userId,
            ':app_id' => $appId,
        ]);

        return $this->normalizeUser($stmt->fetch() ?: []);
    }

    public function revokeSession(string $sessionId, string $userId, string $appId): array
    {
        $stmt = $this->db->prepare('UPDATE sessions SET status = \'archived\', active = 0, session_revokedat = COALESCE(session_revokedat, now()), time_updated = now() WHERE session_id = :session_id AND session_user_id = :user_id AND created_for_app_id = :app_id RETURNING ' . self::SESSION_COLUMNS . ';');
        $stmt->execute([
            ':session_id' => $sessionId,
            ':user_id' => $userId,
            ':app_id' => $appId,
        ]);

        return $this->normalizeSession($stmt->fetch() ?: []);
    }

    private function createPersonForUser(array $user, array $changes): array
    {
        $email = strtolower(trim((string) ($changes['email'] ?? $changes['user_email'] ?? $user['user_email'] ?? '')));
        $emails = $this->objectValue($changes['person_emails'] ?? null);
        if ($emails === [] && $email !== '') {
            $emails = ['primary' => $email];
        }

        $personId = Ids::generate('person');
        $personAttributes = $this->objectValue($changes['person_attributes'] ?? []);
        $personAttributes['identity'] = [
            'user_id' => $user['user_id'],
            'created_from' => 'account_facade',
        ];

        $sql = <<<'SQL'
            INSERT INTO persons (
                person_id,
                person_attributes,
                person_firstname,
                person_middlename,
                person_lastname,
                person_emails,
                person_phones,
                person_addresses,
                person_source,
                created_by_user_id,
                created_for_app_id,
                event_id,
                process_id,
                access,
                status,
                active
            ) VALUES (
                :person_id,
                CAST(:person_attributes AS jsonb),
                :person_firstname,
                :person_middlename,
                :person_lastname,
                CAST(:person_emails AS jsonb),
                CAST(:person_phones AS jsonb),
                CAST(:person_addresses AS jsonb),
                :person_source,
                :created_by_user_id,
                :created_for_app_id,
                :event_id,
                :process_id,
                :access,
                :status,
                :active
            ) RETURNING
        SQL;
        $sql .= ' ' . self::PERSON_COLUMNS . ';';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':person_id' => $personId,
            ':person_attributes' => json_encode($personAttributes, JSON_UNESCAPED_SLASHES) ?: '{}',
            ':person_firstname' => $this->text($changes['first_name'] ?? $changes['person_firstname'] ?? null),
            ':person_middlename' => $this->text($changes['middle_name'] ?? $changes['person_middlename'] ?? null),
            ':person_lastname' => $this->text($changes['last_name'] ?? $changes['person_lastname'] ?? null),
            ':person_emails' => json_encode($emails, JSON_UNESCAPED_SLASHES) ?: '{}',
            ':person_phones' => json_encode($this->phoneObject($changes), JSON_UNESCAPED_SLASHES) ?: '{}',
            ':person_addresses' => json_encode($this->addressObject($changes), JSON_UNESCAPED_SLASHES) ?: '{}',
            ':person_source' => 'registration',
            ':created_by_user_id' => $user['user_id'],
            ':created_for_app_id' => $user['created_for_app_id'],
            ':event_id' => 'event_8301',
            ':process_id' => 'process_8301',
            ':access' => 'private',
            ':status' => 'active',
            ':active' => 1,
        ]);

        return $this->normalizePerson($stmt->fetch() ?: []);
    }

    private function createProfileForUser(array $user, array $changes, array $person): array
    {
        $profileId = Ids::generate('profile');
        $attributes = $this->objectValue($changes['profile_attributes'] ?? []);
        $attributes['identity'] = [
            'user_id' => $user['user_id'],
            'person_id' => $person['person_id'] ?? null,
            'created_from' => 'account_facade',
        ];
        $attributes['social'] = $this->objectValue($changes['social'] ?? $changes['handles'] ?? []);

        $sql = <<<'SQL'
            INSERT INTO profiles (
                profile_id,
                profile_attributes,
                profile_username,
                profile_displayname,
                profile_bio,
                profile_avatarurl,
                profile_theme,
                profile_biopublished,
                created_by_user_id,
                created_for_app_id,
                event_id,
                process_id,
                access,
                status,
                active
            ) VALUES (
                :profile_id,
                CAST(:profile_attributes AS jsonb),
                :profile_username,
                :profile_displayname,
                :profile_bio,
                :profile_avatarurl,
                :profile_theme,
                CAST(:profile_biopublished AS boolean),
                :created_by_user_id,
                :created_for_app_id,
                :event_id,
                :process_id,
                :access,
                :status,
                :active
            ) RETURNING
        SQL;
        $sql .= ' ' . self::PROFILE_COLUMNS . ';';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':profile_id' => $profileId,
            ':profile_attributes' => json_encode($attributes, JSON_UNESCAPED_SLASHES) ?: '{}',
            ':profile_username' => strtolower((string) ($changes['profile_username'] ?? $changes['screen_name'] ?? $user['user_username'] ?? '')) ?: null,
            ':profile_displayname' => $this->text($changes['profile_displayname'] ?? $changes['display_name'] ?? $user['user_displayname'] ?? '') ?? '',
            ':profile_bio' => $this->text($changes['profile_bio'] ?? $changes['bio'] ?? null) ?? '',
            ':profile_avatarurl' => $this->text($changes['profile_avatarurl'] ?? $changes['avatar_url'] ?? null) ?? '',
            ':profile_theme' => strtolower($this->text($changes['profile_theme'] ?? null) ?? 'salt'),
            ':profile_biopublished' => $this->boolText($changes['profile_biopublished'] ?? true, true),
            ':created_by_user_id' => $user['user_id'],
            ':created_for_app_id' => $user['created_for_app_id'],
            ':event_id' => 'event_8301',
            ':process_id' => 'process_8301',
            ':access' => 'public',
            ':status' => 'active',
            ':active' => 1,
        ]);

        return $this->normalizeProfile($stmt->fetch() ?: []);
    }

    private function updatePerson(string $personId, string $userId, string $appId, array $changes): array
    {
        $sets = [];
        $params = [':person_id' => $personId, ':user_id' => $userId, ':app_id' => $appId];

        $this->setText($sets, $params, $changes, ['first_name', 'person_firstname'], 'person_firstname');
        $this->setText($sets, $params, $changes, ['middle_name', 'person_middlename'], 'person_middlename');
        $this->setText($sets, $params, $changes, ['last_name', 'person_lastname'], 'person_lastname');

        if (isset($changes['email']) || isset($changes['user_email']) || isset($changes['person_emails'])) {
            $emails = $this->objectValue($changes['person_emails'] ?? null);
            $email = strtolower(trim((string) ($changes['email'] ?? $changes['user_email'] ?? '')));
            if ($email !== '') {
                $emails['primary'] = $email;
            }
            $sets[] = 'person_emails = CAST(:person_emails AS jsonb)';
            $params[':person_emails'] = json_encode($emails, JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        if (isset($changes['phone']) || isset($changes['user_phones']) || isset($changes['person_phones'])) {
            $sets[] = 'person_phones = CAST(:person_phones AS jsonb)';
            $params[':person_phones'] = json_encode($this->phoneObject($changes), JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        if (isset($changes['address']) || isset($changes['user_addresses']) || isset($changes['person_addresses'])) {
            $sets[] = 'person_addresses = CAST(:person_addresses AS jsonb)';
            $params[':person_addresses'] = json_encode($this->addressObject($changes), JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        if (isset($changes['person_attributes'])) {
            $sets[] = 'person_attributes = CAST(:person_attributes AS jsonb)';
            $params[':person_attributes'] = json_encode($this->objectValue($changes['person_attributes']), JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        if ($sets === []) {
            return $this->findPersonForUser($userId, $appId);
        }

        $sets[] = 'time_updated = now()';
        $stmt = $this->db->prepare('UPDATE persons SET ' . implode(', ', $sets) . ' WHERE person_id = :person_id AND created_by_user_id = :user_id AND created_for_app_id = :app_id AND active = 1 AND status = \'active\' RETURNING ' . self::PERSON_COLUMNS . ';');
        $stmt->execute($params);

        return $this->normalizePerson($stmt->fetch() ?: []);
    }

    private function updateUser(string $userId, string $appId, array $changes): array
    {
        $sets = [];
        $params = [':user_id' => $userId, ':app_id' => $appId];

        if (isset($changes['email']) || isset($changes['user_email'])) {
            $sets[] = 'user_email = :user_email';
            $params[':user_email'] = strtolower(trim((string) ($changes['email'] ?? $changes['user_email'])));
        }

        $this->setText($sets, $params, $changes, ['user_username', 'username'], 'user_username', true);
        $this->setText($sets, $params, $changes, ['user_displayname', 'display_name'], 'user_displayname');

        if (isset($changes['user_optins']) || isset($changes['communication_preferences'])) {
            $sets[] = 'user_optins = CAST(:user_optins AS jsonb)';
            $params[':user_optins'] = json_encode($this->objectValue($changes['user_optins'] ?? $changes['communication_preferences']), JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        if (isset($changes['user_attributes'])) {
            $sets[] = 'user_attributes = CAST(:user_attributes AS jsonb)';
            $params[':user_attributes'] = json_encode($this->objectValue($changes['user_attributes']), JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        if ($sets === []) {
            return $this->findUser($userId, $appId, false);
        }

        $sets[] = 'time_updated = now()';
        $stmt = $this->db->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE user_id = :user_id AND created_for_app_id = :app_id AND active = 1 AND status = \'active\' RETURNING ' . self::USER_PUBLIC_COLUMNS . ';');
        $stmt->execute($params);

        return $this->normalizeUser($stmt->fetch() ?: []);
    }

    private function updateProfile(string $profileId, string $userId, string $appId, array $changes): array
    {
        $sets = [];
        $params = [':profile_id' => $profileId, ':user_id' => $userId, ':app_id' => $appId];

        $this->setText($sets, $params, $changes, ['profile_username', 'screen_name'], 'profile_username', true);
        $this->setText($sets, $params, $changes, ['profile_displayname', 'display_name'], 'profile_displayname');
        $this->setText($sets, $params, $changes, ['profile_bio', 'bio'], 'profile_bio');
        $this->setText($sets, $params, $changes, ['profile_avatarurl', 'avatar_url'], 'profile_avatarurl');
        $this->setText($sets, $params, $changes, ['profile_theme'], 'profile_theme', true);

        if (isset($changes['profile_biopublished'])) {
            $sets[] = 'profile_biopublished = CAST(:profile_biopublished AS boolean)';
            $params[':profile_biopublished'] = $this->boolText($changes['profile_biopublished'], true);
        }

        if (isset($changes['profile_attributes']) || isset($changes['social']) || isset($changes['handles'])) {
            $attributes = $this->objectValue($changes['profile_attributes'] ?? []);
            if (isset($changes['social']) || isset($changes['handles'])) {
                $attributes['social'] = $this->objectValue($changes['social'] ?? $changes['handles']);
            }
            $sets[] = 'profile_attributes = CAST(:profile_attributes AS jsonb)';
            $params[':profile_attributes'] = json_encode($attributes, JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        if ($sets === []) {
            return $this->findProfileForUser($userId, $appId);
        }

        $sets[] = 'time_updated = now()';
        $stmt = $this->db->prepare('UPDATE profiles SET ' . implode(', ', $sets) . ' WHERE profile_id = :profile_id AND created_by_user_id = :user_id AND created_for_app_id = :app_id AND active = 1 AND status = \'active\' RETURNING ' . self::PROFILE_COLUMNS . ';');
        $stmt->execute($params);

        return $this->normalizeProfile($stmt->fetch() ?: []);
    }

    private function linkIdentityAttributes(string $userId, string $appId, string $personId, string $profileId): void
    {
        $user = $this->findUser($userId, $appId, false);
        if ($user === []) {
            return;
        }

        $attributes = $this->objectValue($user['user_attributes'] ?? []);
        $attributes['identity'] = [
            'person_id' => $personId,
            'profile_id' => $profileId,
        ];

        $stmt = $this->db->prepare('UPDATE users SET user_attributes = CAST(:attributes AS jsonb), time_updated = now() WHERE user_id = :user_id AND created_for_app_id = :app_id');
        $stmt->execute([
            ':attributes' => json_encode($attributes, JSON_UNESCAPED_SLASHES) ?: '{}',
            ':user_id' => $userId,
            ':app_id' => $appId,
        ]);
    }

    private function setText(array &$sets, array &$params, array $changes, array $inputKeys, string $column, bool $lower = false): void
    {
        foreach ($inputKeys as $inputKey) {
            if (array_key_exists($inputKey, $changes)) {
                $value = $this->text($changes[$inputKey]);
                $sets[] = $column . ' = :' . $column;
                $params[':' . $column] = $value === null ? null : ($lower ? strtolower($value) : $value);
                return;
            }
        }
    }

    private function objectValue(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        return is_array($value) && !array_is_list($value) ? $value : [];
    }

    private function phoneObject(array $changes): array
    {
        $phones = $this->objectValue($changes['person_phones'] ?? $changes['user_phones'] ?? null);
        $phone = trim((string) ($changes['phone'] ?? ''));
        if ($phone !== '') {
            $phones['primary'] = $phone;
        }

        return $phones;
    }

    private function addressObject(array $changes): array
    {
        $addresses = $this->objectValue($changes['person_addresses'] ?? $changes['user_addresses'] ?? null);
        $address = $this->objectValue($changes['address'] ?? null);
        if ($address !== []) {
            $addresses['primary'] = $address;
        }

        return $addresses;
    }

    private function text(mixed $value): ?string
    {
        $clean = trim((string) ($value ?? ''));
        return $clean === '' ? null : $clean;
    }

    private function boolText(mixed $value, bool $default): string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return $default ? 'true' : 'false';
        }

        return in_array($value, [1, '1', true, 'true', 'TRUE', 'yes', 'YES'], true) ? 'true' : 'false';
    }

    private function normalizeUser(array $row, bool $includePasswordHash = false): array
    {
        if ($row === []) {
            return [];
        }

        foreach (['user_attributes', 'user_addresses', 'user_phones', 'user_optins'] as $field) {
            $row[$field] = $this->decodeJsonField($row[$field] ?? null);
        }

        if (!$includePasswordHash) {
            unset($row['user_passwordhash']);
        }

        return $row;
    }

    private function normalizePerson(array $row): array
    {
        if ($row === []) {
            return [];
        }

        foreach (['person_attributes', 'person_emails', 'person_phones', 'person_addresses'] as $field) {
            $row[$field] = $this->decodeJsonField($row[$field] ?? null);
        }

        return $row;
    }

    private function normalizeProfile(array $row): array
    {
        if ($row === []) {
            return [];
        }

        $row['profile_attributes'] = $this->decodeJsonField($row['profile_attributes'] ?? null);
        return $row;
    }

    private function normalizeSession(array $row): array
    {
        if ($row === []) {
            return [];
        }

        $row['session_attributes'] = $this->decodeJsonField($row['session_attributes'] ?? null);
        return $row;
    }

    private function decodeJsonField(mixed $value): array|\stdClass
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : new \stdClass();
        }

        return is_array($value) ? $value : new \stdClass();
    }
}
