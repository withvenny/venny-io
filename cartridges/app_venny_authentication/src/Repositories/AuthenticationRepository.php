<?php

declare(strict_types=1);

namespace VennyIO\Repositories;

use PDO;

final class AuthenticationRepository
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

    private const SESSION_PUBLIC_COLUMNS = <<<'SQL'
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

    public function __construct(private PDO $db)
    {
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

    public function findActiveUserByEmail(string $email, string $appId, bool $includePasswordHash = false): array
    {
        $columns = $includePasswordHash ? self::USER_PRIVATE_COLUMNS : self::USER_PUBLIC_COLUMNS;
        $sql = 'SELECT ' . $columns . '
            FROM users
            WHERE lower(user_email::text) = lower(:email)
              AND created_for_app_id = :app_id
              AND active = 1
              AND status = \'active\'
            LIMIT 1;';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':app_id' => $appId,
        ]);

        return $this->normalizeUser($stmt->fetch() ?: []);
    }

    public function findActiveUserById(string $userId, string $appId, bool $includePasswordHash = false): array
    {
        $columns = $includePasswordHash ? self::USER_PRIVATE_COLUMNS : self::USER_PUBLIC_COLUMNS;
        $sql = 'SELECT ' . $columns . '
            FROM users
            WHERE user_id = :user_id
              AND created_for_app_id = :app_id
              AND active = 1
              AND status = \'active\'
            LIMIT 1;';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':app_id' => $appId,
        ]);

        return $this->normalizeUser($stmt->fetch() ?: []);
    }

    public function createUser(array $user): array
    {
        $sql = <<<'SQL'
            INSERT INTO users (
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
                active
            ) VALUES (
                :user_id,
                CAST(:user_attributes AS jsonb),
                :user_email,
                CAST(:user_addresses AS jsonb),
                CAST(:user_phones AS jsonb),
                CAST(:user_optins AS jsonb),
                :user_passwordhash,
                :user_username,
                :user_displayname,
                :user_bio,
                :user_avatarurl,
                :user_theme,
                CAST(:user_biopublished AS boolean),
                :user_lastlogin,
                :created_by_user_id,
                :created_for_app_id,
                :event_id,
                :process_id,
                :access,
                :status,
                :active
            ) RETURNING
        SQL;
        $sql .= self::USER_PUBLIC_COLUMNS . ';';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $user['user_id'],
            ':user_attributes' => $user['user_attributes'] ?? '{}',
            ':user_email' => $user['user_email'],
            ':user_addresses' => $user['user_addresses'] ?? '{}',
            ':user_phones' => $user['user_phones'] ?? '{}',
            ':user_optins' => $user['user_optins'] ?? '{}',
            ':user_passwordhash' => $user['user_passwordhash'],
            ':user_username' => $user['user_username'] ?? null,
            ':user_displayname' => $user['user_displayname'] ?? '',
            ':user_bio' => $user['user_bio'] ?? '',
            ':user_avatarurl' => $user['user_avatarurl'] ?? '',
            ':user_theme' => $user['user_theme'] ?? 'salt',
            ':user_biopublished' => $this->dbBoolean($user['user_biopublished'] ?? true, true),
            ':user_lastlogin' => $user['user_lastlogin'] ?? null,
            ':created_by_user_id' => $user['created_by_user_id'] ?? $user['user_id'],
            ':created_for_app_id' => $user['created_for_app_id'],
            ':event_id' => $user['event_id'] ?? 'event_8301',
            ':process_id' => $user['process_id'] ?? 'process_8301',
            ':access' => $user['access'] ?? 'private',
            ':status' => $user['status'] ?? 'active',
            ':active' => $this->dbActive($user['active'] ?? 1, 1),
        ]);

        return $this->normalizeUser($stmt->fetch() ?: []);
    }



    public function createPerson(array $person): array
    {
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
        $sql .= self::PERSON_COLUMNS . ';';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':person_id' => $person['person_id'],
            ':person_attributes' => $person['person_attributes'] ?? '{}',
            ':person_firstname' => $person['person_firstname'] ?? null,
            ':person_middlename' => $person['person_middlename'] ?? null,
            ':person_lastname' => $person['person_lastname'] ?? null,
            ':person_emails' => $person['person_emails'] ?? '{}',
            ':person_phones' => $person['person_phones'] ?? '{}',
            ':person_addresses' => $person['person_addresses'] ?? '{}',
            ':person_source' => $person['person_source'] ?? 'registration',
            ':created_by_user_id' => $person['created_by_user_id'],
            ':created_for_app_id' => $person['created_for_app_id'],
            ':event_id' => $person['event_id'] ?? 'event_8301',
            ':process_id' => $person['process_id'] ?? 'process_8301',
            ':access' => $person['access'] ?? 'private',
            ':status' => $person['status'] ?? 'active',
            ':active' => $this->dbActive($person['active'] ?? 1, 1),
        ]);

        return $this->normalizePerson($stmt->fetch() ?: []);
    }

    public function createProfile(array $profile): array
    {
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
        $sql .= self::PROFILE_COLUMNS . ';';

        $profileUsername = isset($profile['profile_username']) ? trim((string) $profile['profile_username']) : '';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':profile_id' => $profile['profile_id'],
            ':profile_attributes' => $profile['profile_attributes'] ?? '{}',
            ':profile_username' => $profileUsername === '' ? null : strtolower($profileUsername),
            ':profile_displayname' => $profile['profile_displayname'] ?? '',
            ':profile_bio' => $profile['profile_bio'] ?? '',
            ':profile_avatarurl' => $profile['profile_avatarurl'] ?? '',
            ':profile_theme' => $profile['profile_theme'] ?? 'salt',
            ':profile_biopublished' => $this->dbBoolean($profile['profile_biopublished'] ?? true, true),
            ':created_by_user_id' => $profile['created_by_user_id'],
            ':created_for_app_id' => $profile['created_for_app_id'],
            ':event_id' => $profile['event_id'] ?? 'event_8301',
            ':process_id' => $profile['process_id'] ?? 'process_8301',
            ':access' => $profile['access'] ?? 'public',
            ':status' => $profile['status'] ?? 'active',
            ':active' => $this->dbActive($profile['active'] ?? 1, 1),
        ]);

        return $this->normalizeProfile($stmt->fetch() ?: []);
    }

    public function markUserLoggedIn(string $userId, string $appId): array
    {
        $sql = 'UPDATE users
            SET user_lastlogin = now(), time_updated = now()
            WHERE user_id = :user_id
              AND created_for_app_id = :app_id
              AND active = 1
              AND status = \'active\'
            RETURNING ' . self::USER_PUBLIC_COLUMNS . ';';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':app_id' => $appId,
        ]);

        return $this->normalizeUser($stmt->fetch() ?: []);
    }

    public function createSession(array $session): array
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
                now(),
                :created_by_user_id,
                :created_for_app_id,
                :event_id,
                :process_id,
                :access,
                :status,
                :active
            ) RETURNING
        SQL;
        $sql .= self::SESSION_PUBLIC_COLUMNS . ';';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':session_id' => $session['session_id'],
            ':session_attributes' => $session['session_attributes'] ?? '{}',
            ':session_refreshtokenhash' => $session['session_refreshtokenhash'],
            ':session_ipaddresshash' => $session['session_ipaddresshash'] ?? null,
            ':session_ipcountryhash' => $session['session_ipcountryhash'] ?? null,
            ':session_user_id' => $session['session_user_id'],
            ':session_useragent' => $session['session_useragent'],
            ':session_expiresat' => $session['session_expiresat'],
            ':session_revokedat' => $session['session_revokedat'] ?? null,
            ':created_by_user_id' => $session['created_by_user_id'],
            ':created_for_app_id' => $session['created_for_app_id'],
            ':event_id' => $session['event_id'] ?? 'event_8301',
            ':process_id' => $session['process_id'] ?? 'process_8301',
            ':access' => $session['access'] ?? 'private',
            ':status' => $session['status'] ?? 'active',
            ':active' => $this->dbActive($session['active'] ?? 1, 1),
        ]);

        return $this->normalizeSession($stmt->fetch() ?: []);
    }

    public function revokeSession(string $sessionId, string $appId, ?string $userId = null): array
    {
        $params = [
            ':session_id' => $sessionId,
            ':app_id' => $appId,
        ];

        $userClause = '';
        if ($userId !== null && $userId !== '') {
            $userClause = ' AND session_user_id = :user_id';
            $params[':user_id'] = $userId;
        }

        $sql = 'UPDATE sessions
            SET status = \'archived\',
                active = 0,
                session_revokedat = COALESCE(session_revokedat, now()),
                time_updated = now()
            WHERE session_id = :session_id
              AND created_for_app_id = :app_id' . $userClause . '
            RETURNING ' . self::SESSION_PUBLIC_COLUMNS . ';';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->normalizeSession($stmt->fetch() ?: []);
    }

    public function setPasswordReset(array $user, string $selector, string $secretHash, string $expiresAt): array
    {
        $attributes = $this->attributesFromUser($user);
        $attributes['auth'] = is_array($attributes['auth'] ?? null) && !array_is_list($attributes['auth'])
            ? $attributes['auth']
            : [];

        $attributes['auth']['password_reset'] = [
            'selector' => $selector,
            'secret_hash' => $secretHash,
            'requested_at' => gmdate('c'),
            'expires_at' => $expiresAt,
            'used_at' => null,
        ];

        $sql = 'UPDATE users
            SET user_attributes = CAST(:user_attributes AS jsonb),
                time_updated = now()
            WHERE user_id = :user_id
              AND created_for_app_id = :app_id
              AND active = 1
              AND status = \'active\'
            RETURNING ' . self::USER_PUBLIC_COLUMNS . ';';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_attributes' => json_encode($attributes, JSON_UNESCAPED_SLASHES) ?: '{}',
            ':user_id' => (string) $user['user_id'],
            ':app_id' => (string) $user['created_for_app_id'],
        ]);

        return $this->normalizeUser($stmt->fetch() ?: []);
    }

    public function findUserByResetSelector(string $selector, string $appId): array
    {
        $sql = 'SELECT ' . self::USER_PRIVATE_COLUMNS . '
            FROM users
            WHERE created_for_app_id = :app_id
              AND active = 1
              AND status = \'active\'
              AND user_attributes #>> \'{auth,password_reset,selector}\' = :selector
            LIMIT 1;';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':app_id' => $appId,
            ':selector' => $selector,
        ]);

        return $this->normalizeUser($stmt->fetch() ?: []);
    }

    public function resetUserPassword(array $user, string $newPasswordHash): array
    {
        $attributes = $this->attributesFromUser($user);
        $attributes['auth'] = is_array($attributes['auth'] ?? null) && !array_is_list($attributes['auth'])
            ? $attributes['auth']
            : [];
        $attributes['auth']['password_set_at'] = gmdate('c');
        unset($attributes['auth']['password_reset']);

        $sql = 'UPDATE users
            SET user_passwordhash = :password_hash,
                user_attributes = CAST(:user_attributes AS jsonb),
                time_updated = now()
            WHERE user_id = :user_id
              AND created_for_app_id = :app_id
              AND active = 1
              AND status = \'active\'
            RETURNING ' . self::USER_PUBLIC_COLUMNS . ';';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':password_hash' => $newPasswordHash,
            ':user_attributes' => json_encode($attributes, JSON_UNESCAPED_SLASHES) ?: '{}',
            ':user_id' => (string) $user['user_id'],
            ':app_id' => (string) $user['created_for_app_id'],
        ]);

        return $this->normalizeUser($stmt->fetch() ?: []);
    }



    private function normalizePerson(array $row): array
    {
        if ($row === []) {
            return [];
        }

        foreach (['person_attributes', 'person_emails', 'person_phones', 'person_addresses'] as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $decoded = json_decode($row[$field], true);
                $row[$field] = is_array($decoded) ? $decoded : new \stdClass();
            }
        }

        return $row;
    }

    private function normalizeProfile(array $row): array
    {
        if ($row === []) {
            return [];
        }

        if (array_key_exists('profile_attributes', $row) && is_string($row['profile_attributes'])) {
            $decoded = json_decode($row['profile_attributes'], true);
            $row['profile_attributes'] = is_array($decoded) ? $decoded : new \stdClass();
        }

        return $row;
    }

    private function normalizeUser(array $row): array
    {
        if ($row === []) {
            return [];
        }

        foreach (['user_attributes', 'user_addresses', 'user_phones', 'user_optins'] as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $decoded = json_decode($row[$field], true);
                $row[$field] = is_array($decoded) ? $decoded : new \stdClass();
            }
        }

        return $row;
    }

    private function normalizeSession(array $row): array
    {
        if ($row === []) {
            return [];
        }

        if (array_key_exists('session_attributes', $row) && is_string($row['session_attributes'])) {
            $decoded = json_decode($row['session_attributes'], true);
            $row['session_attributes'] = is_array($decoded) ? $decoded : new \stdClass();
        }

        return $row;
    }

    private function attributesFromUser(array $user): array
    {
        $attributes = $user['user_attributes'] ?? [];

        if (is_string($attributes)) {
            $decoded = json_decode($attributes, true);
            $attributes = is_array($decoded) ? $decoded : [];
        }

        return is_array($attributes) && !array_is_list($attributes) ? $attributes : [];
    }

    private function dbBoolean(mixed $value, bool $default): string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return $default ? 'true' : 'false';
        }

        if (in_array($value, [1, '1', true, 'true', 'TRUE', 'yes', 'YES'], true)) {
            return 'true';
        }

        if (in_array($value, [0, '0', false, 'false', 'FALSE', 'no', 'NO'], true)) {
            return 'false';
        }

        return $default ? 'true' : 'false';
    }

    private function dbActive(mixed $value, int $default): int
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return $default;
        }

        if (in_array($value, [1, '1', true, 'true', 'TRUE'], true)) {
            return 1;
        }

        if (in_array($value, [0, '0', false, 'false', 'FALSE'], true)) {
            return 0;
        }

        return $default;
    }

}
