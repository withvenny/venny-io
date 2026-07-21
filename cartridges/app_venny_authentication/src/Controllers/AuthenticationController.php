<?php

declare(strict_types=1);

namespace VennyIO\Controllers;

use DateTimeImmutable;
use PDOException;
use VennyIO\Kernel\Request;
use VennyIO\Repositories\AuthenticationRepository;
use VennyIO\Support\Ids;
use VennyIO\Support\Response;
use VennyIO\Support\TokenHash;

final class AuthenticationController
{
    public function __construct(
        private AuthenticationRepository $repository,
        private array $appContext
    ) {
    }

    public function signUp(Request $request): void
    {
        $input = $request->input();
        $errors = [];

        $email = $this->email($input['user_email'] ?? $input['email'] ?? null, $errors);
        $password = $this->password($input['password'] ?? $input['user_password'] ?? null, $errors);
        $confirmation = $this->nullableText($input['password_confirmation'] ?? $input['user_password_confirmation'] ?? null);

        if ($confirmation !== null && $password !== null && !hash_equals($password, $confirmation)) {
            $errors[] = 'password_confirmation must match password';
        }

        $userId = $this->nullableText($input['user_id'] ?? null) ?? Ids::generate('user');
        if (!Ids::is('user', $userId)) {
            $errors[] = 'user_id must be a valid Venny I/O user id';
        }

        $personId = $this->nullableText($input['person_id'] ?? null) ?? Ids::generate('person');
        if (!Ids::is('person', $personId)) {
            $errors[] = 'person_id must be a valid Venny I/O person id';
        }

        $profileId = $this->nullableText($input['profile_id'] ?? null) ?? Ids::generate('profile');
        if (!Ids::is('profile', $profileId)) {
            $errors[] = 'profile_id must be a valid Venny I/O profile id';
        }

        $displayName = $this->nullableText($input['user_displayname'] ?? $input['display_name'] ?? $input['profile_displayname'] ?? null)
            ?? $this->defaultDisplayName($email ?? '');

        $username = $this->nullableText($input['user_username'] ?? $input['username'] ?? $input['profile_username'] ?? null);
        if ($username !== null) {
            $username = strtolower($username);
        }

        $personPhones = $this->jsonObject($input['person_phones'] ?? $input['phones'] ?? $input['user_phones'] ?? [], 'person_phones', $errors);
        $primaryPhone = $this->nullableText($input['phone'] ?? null);
        if ($primaryPhone !== null) {
            $personPhones['primary'] = $primaryPhone;
        }

        $personAddresses = $this->jsonObject($input['person_addresses'] ?? $input['addresses'] ?? $input['user_addresses'] ?? [], 'person_addresses', $errors);
        $primaryAddress = $this->jsonObject($input['address'] ?? [], 'address', $errors);
        if ($primaryAddress !== []) {
            $personAddresses['primary'] = $primaryAddress;
        }

        $userAttributes = $this->jsonObject($input['user_attributes'] ?? [], 'user_attributes', $errors);
        $userAttributes['identity'] = [
            'person_id' => $personId,
            'profile_id' => $profileId,
        ];
        $userAttributes['auth'] = is_array($userAttributes['auth'] ?? null) && !array_is_list($userAttributes['auth'])
            ? $userAttributes['auth']
            : [];
        $userAttributes['auth']['provider'] = 'password';
        $userAttributes['auth']['password_set_at'] = gmdate('c');

        $personAttributes = $this->jsonObject($input['person_attributes'] ?? [], 'person_attributes', $errors);
        $personAttributes['identity'] = [
            'user_id' => $userId,
            'profile_id' => $profileId,
            'created_from' => 'sign-up',
        ];

        $profileAttributes = $this->jsonObject($input['profile_attributes'] ?? [], 'profile_attributes', $errors);
        $profileAttributes['identity'] = [
            'user_id' => $userId,
            'person_id' => $personId,
            'created_from' => 'sign-up',
        ];
        $social = $this->jsonObject($input['social'] ?? $input['handles'] ?? [], 'social', $errors);
        if ($social !== []) {
            $profileAttributes['social'] = $social;
        }

        $userBioPublished = $this->optionalBooleanValue($input['user_biopublished'] ?? $input['profile_biopublished'] ?? null, 'user_biopublished', true, $errors);
        $active = $this->optionalActiveValue($input['active'] ?? null, 1, $errors);

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        $appId = $this->appId();
        $passwordHash = password_hash((string) $password, PASSWORD_DEFAULT);

        try {
            $result = $this->repository->transaction(function () use (
                $input,
                $personId,
                $profileId,
                $userId,
                $userAttributes,
                $personAttributes,
                $profileAttributes,
                $personPhones,
                $personAddresses,
                $email,
                $passwordHash,
                $username,
                $displayName,
                $userBioPublished,
                $active,
                $appId,
                $request
            ): array {
                $person = $this->repository->createPerson([
                    'person_id' => $personId,
                    'person_attributes' => json_encode($personAttributes, JSON_UNESCAPED_SLASHES) ?: '{}',
                    'person_firstname' => $this->nullableText($input['person_firstname'] ?? $input['first_name'] ?? null),
                    'person_middlename' => $this->nullableText($input['person_middlename'] ?? $input['middle_name'] ?? null),
                    'person_lastname' => $this->nullableText($input['person_lastname'] ?? $input['last_name'] ?? null),
                    'person_emails' => json_encode(['primary' => $email], JSON_UNESCAPED_SLASHES) ?: '{}',
                    'person_phones' => json_encode($personPhones, JSON_UNESCAPED_SLASHES) ?: '{}',
                    'person_addresses' => json_encode($personAddresses, JSON_UNESCAPED_SLASHES) ?: '{}',
                    'person_source' => strtolower($this->nullableText($input['person_source'] ?? null) ?? 'registration'),
                    'created_by_user_id' => $userId,
                    'created_for_app_id' => $appId,
                    'event_id' => $this->nullableText($input['event_id'] ?? null) ?? 'event_8301',
                    'process_id' => $this->nullableText($input['process_id'] ?? null) ?? 'process_8301',
                    'access' => 'private',
                    'status' => strtolower($this->nullableText($input['status'] ?? null) ?? 'active'),
                    'active' => $active,
                ]);

                $user = $this->repository->createUser([
                    'user_id' => $userId,
                    'user_attributes' => json_encode($userAttributes, JSON_UNESCAPED_SLASHES) ?: '{}',
                    'user_email' => $email,
                    'user_addresses' => '{}',
                    'user_phones' => '{}',
                    'user_optins' => $this->jsonObjectString($input['user_optins'] ?? $input['communication_preferences'] ?? []),
                    'user_passwordhash' => $passwordHash,
                    'user_username' => $username,
                    'user_displayname' => $displayName,
                    'user_bio' => '',
                    'user_avatarurl' => '',
                    'user_theme' => strtolower($this->nullableText($input['user_theme'] ?? $input['profile_theme'] ?? null) ?? 'salt'),
                    'user_biopublished' => $userBioPublished,
                    'user_lastlogin' => gmdate('c'),
                    'created_by_user_id' => $userId,
                    'created_for_app_id' => $appId,
                    'event_id' => $this->nullableText($input['event_id'] ?? null) ?? 'event_8301',
                    'process_id' => $this->nullableText($input['process_id'] ?? null) ?? 'process_8301',
                    'access' => strtolower($this->nullableText($input['access'] ?? null) ?? 'private'),
                    'status' => strtolower($this->nullableText($input['status'] ?? null) ?? 'active'),
                    'active' => $active,
                ]);

                $profile = $this->repository->createProfile([
                    'profile_id' => $profileId,
                    'profile_attributes' => json_encode($profileAttributes, JSON_UNESCAPED_SLASHES) ?: '{}',
                    'profile_username' => strtolower($this->nullableText($input['profile_username'] ?? $input['screen_name'] ?? $username ?? null) ?? ''),
                    'profile_displayname' => $this->nullableText($input['profile_displayname'] ?? $input['display_name'] ?? null) ?? $displayName,
                    'profile_bio' => $this->nullableText($input['profile_bio'] ?? $input['bio'] ?? null) ?? '',
                    'profile_avatarurl' => $this->nullableText($input['profile_avatarurl'] ?? $input['avatar_url'] ?? null) ?? '',
                    'profile_theme' => strtolower($this->nullableText($input['profile_theme'] ?? $input['user_theme'] ?? null) ?? 'salt'),
                    'profile_biopublished' => $userBioPublished,
                    'created_by_user_id' => $userId,
                    'created_for_app_id' => $appId,
                    'event_id' => $this->nullableText($input['event_id'] ?? null) ?? 'event_8301',
                    'process_id' => $this->nullableText($input['process_id'] ?? null) ?? 'process_8301',
                    'access' => strtolower($this->nullableText($input['profile_access'] ?? null) ?? 'public'),
                    'status' => strtolower($this->nullableText($input['status'] ?? null) ?? 'active'),
                    'active' => $active,
                ]);

                return [
                    'person' => $person,
                    'user' => $user,
                    'profile' => $profile,
                    'session_result' => $this->createSessionForUser($request, $user, 'sign-up'),
                ];
            });

            $person = $result['person'];
            $user = $result['user'];
            $profile = $result['profile'];
            $sessionResult = $result['session_result'];
        } catch (PDOException $exception) {
            $this->handlePdoException($exception, 'account');
            return;
        }

        Response::json(201, true, 'signed up successfully', [
            'person' => $person,
            'user' => $user,
            'profile' => $profile,
            'session' => $sessionResult['session'],
            'raw_refresh_token' => $sessionResult['raw_refresh_token'],
            'warning' => 'Store this raw refresh token now. It is returned only once and cannot be retrieved later.',
        ]);
    }

    public function signIn(Request $request): void
    {
        $input = $request->input();
        $errors = [];

        $email = $this->email($input['user_email'] ?? $input['email'] ?? null, $errors);
        $password = $this->password($input['password'] ?? $input['user_password'] ?? null, $errors, false);

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        $user = $this->repository->findActiveUserByEmail((string) $email, $this->appId(), true);
        $storedHash = (string) ($user['user_passwordhash'] ?? '');

        if ($user === [] || $storedHash === '' || !password_verify((string) $password, $storedHash)) {
            Response::json(401, false, 'invalid email or password', []);
            return;
        }

        unset($user['user_passwordhash']);
        $updatedUser = $this->repository->markUserLoggedIn((string) $user['user_id'], $this->appId());

        try {
            $sessionResult = $this->createSessionForUser($request, $updatedUser !== [] ? $updatedUser : $user, 'sign-in');
        } catch (PDOException $exception) {
            $this->handlePdoException($exception, 'session');
            return;
        }

        Response::json(200, true, 'signed in successfully', [
            'user' => $updatedUser !== [] ? $updatedUser : $user,
            'session' => $sessionResult['session'],
            'raw_refresh_token' => $sessionResult['raw_refresh_token'],
            'warning' => 'Store this raw refresh token now. It is returned only once and cannot be retrieved later.',
        ]);
    }

    public function signOut(Request $request): void
    {
        $input = $request->input();
        $errors = [];

        $sessionId = $this->nullableText($input['session_id'] ?? null);
        $userId = $this->nullableText($input['user_id'] ?? $input['session_user_id'] ?? null);

        if ($sessionId === null) {
            $errors[] = 'session_id is required';
        } elseif (!Ids::is('session', $sessionId)) {
            $errors[] = 'session_id must be a valid Venny I/O session id';
        }

        if ($userId !== null && !Ids::is('user', $userId)) {
            $errors[] = 'user_id must be a valid Venny I/O user id';
        }

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        $session = $this->repository->revokeSession((string) $sessionId, $this->appId(), $userId);
        if ($session === []) {
            Response::json(404, false, 'session not found', []);
            return;
        }

        Response::json(200, true, 'signed out successfully', ['session' => $session]);
    }

    public function requestPassword(Request $request): void
    {
        $input = $request->input();
        $errors = [];

        $email = $this->email($input['user_email'] ?? $input['email'] ?? null, $errors);

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        $resetToken = null;
        $user = $this->repository->findActiveUserByEmail((string) $email, $this->appId(), false);

        if ($user !== []) {
            $selector = bin2hex(random_bytes(8));
            $secret = bin2hex(random_bytes(32));
            $resetToken = $selector . '.' . $secret;
            $secretHash = TokenHash::hash($secret);
            $expiresAt = (new DateTimeImmutable('+30 minutes'))->format('c');

            $this->repository->setPasswordReset($user, $selector, $secretHash, $expiresAt);
        }

        $data = [
            'email' => $email,
            'delivery' => 'pending_provider_integration',
        ];

        if ($resetToken !== null && $this->exposeResetToken()) {
            $data['reset_token'] = $resetToken;
            $data['warning'] = 'Development only. Do not expose reset tokens in production responses.';
        }

        Response::json(200, true, 'password request accepted', $data);
    }

    public function resetPassword(Request $request): void
    {
        $input = $request->input();
        $errors = [];

        $resetToken = $this->nullableText($input['reset_token'] ?? $input['token'] ?? null);
        $password = $this->password($input['password'] ?? $input['new_password'] ?? $input['user_password'] ?? null, $errors);
        $confirmation = $this->nullableText($input['password_confirmation'] ?? $input['new_password_confirmation'] ?? null);

        if ($resetToken === null) {
            $errors[] = 'reset_token is required';
        }

        if ($confirmation !== null && $password !== null && !hash_equals($password, $confirmation)) {
            $errors[] = 'password_confirmation must match password';
        }

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        [$selector, $secret] = $this->splitResetToken((string) $resetToken);
        if ($selector === null || $secret === null) {
            Response::json(422, false, 'validation failed', ['errors' => ['reset_token is invalid']]);
            return;
        }

        $user = $this->repository->findUserByResetSelector($selector, $this->appId());
        if (!$this->resetTokenMatches($user, $secret)) {
            Response::json(401, false, 'reset token is invalid or expired', []);
            return;
        }

        $updatedUser = $this->repository->resetUserPassword($user, password_hash((string) $password, PASSWORD_DEFAULT));

        try {
            $sessionResult = $this->createSessionForUser($request, $updatedUser, 'reset-password');
        } catch (PDOException $exception) {
            $this->handlePdoException($exception, 'session');
            return;
        }

        Response::json(200, true, 'password reset successfully', [
            'user' => $updatedUser,
            'session' => $sessionResult['session'],
            'raw_refresh_token' => $sessionResult['raw_refresh_token'],
            'warning' => 'Store this raw refresh token now. It is returned only once and cannot be retrieved later.',
        ]);
    }

    private function createSessionForUser(Request $request, array $user, string $source): array
    {
        $rawRefreshToken = 'venny_refresh_' . bin2hex(random_bytes(32));
        $sessionId = Ids::generate('session');
        $userId = (string) $user['user_id'];
        $appId = $this->appId();

        $attributes = [
            'source' => $source,
            'cartridge' => 'app_venny_authentication',
            'app_slug' => $this->appContext['app_slug'] ?? null,
        ];

        $session = $this->repository->createSession([
            'session_id' => $sessionId,
            'session_attributes' => json_encode($attributes, JSON_UNESCAPED_SLASHES) ?: '{}',
            'session_refreshtokenhash' => TokenHash::hash($rawRefreshToken),
            'session_ipaddresshash' => $this->hashServerValue($request->server['REMOTE_ADDR'] ?? null),
            'session_ipcountryhash' => null,
            'session_user_id' => $userId,
            'session_useragent' => $this->nullableText($request->server['HTTP_USER_AGENT'] ?? null) ?? 'unknown',
            'session_expiresat' => (new DateTimeImmutable('+30 days'))->format('c'),
            'session_lastseenat' => gmdate('c'),
            'created_by_user_id' => $userId,
            'created_for_app_id' => $appId,
            'event_id' => 'event_8301',
            'process_id' => 'process_8301',
            'access' => 'private',
            'status' => 'active',
            'active' => 1,
        ]);

        return [
            'session' => $session,
            'raw_refresh_token' => $rawRefreshToken,
        ];
    }

    private function resetTokenMatches(array $user, string $secret): bool
    {
        if ($user === []) {
            return false;
        }

        $attributes = $user['user_attributes'] ?? [];
        if (!is_array($attributes)) {
            return false;
        }

        $reset = $attributes['auth']['password_reset'] ?? null;
        if (!is_array($reset)) {
            return false;
        }

        $secretHash = (string) ($reset['secret_hash'] ?? '');
        $expiresAt = (string) ($reset['expires_at'] ?? '');
        $usedAt = $reset['used_at'] ?? null;

        if ($secretHash === '' || $expiresAt === '' || $usedAt !== null) {
            return false;
        }

        try {
            if (new DateTimeImmutable($expiresAt) < new DateTimeImmutable('now')) {
                return false;
            }
        } catch (\Throwable) {
            return false;
        }

        return TokenHash::verify($secret, $secretHash);
    }

    private function splitResetToken(string $token): array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
            return [null, null];
        }

        return [$parts[0], $parts[1]];
    }

    private function email(mixed $value, array &$errors): ?string
    {
        $email = strtolower(trim((string) ($value ?? '')));
        if ($email === '') {
            $errors[] = 'user_email is required';
            return null;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'user_email must be a valid email address';
            return null;
        }

        return $email;
    }

    private function password(mixed $value, array &$errors, bool $enforceLength = true): ?string
    {
        $password = (string) ($value ?? '');
        if ($password === '') {
            $errors[] = 'password is required';
            return null;
        }

        if ($enforceLength && strlen($password) < 8) {
            $errors[] = 'password must be at least 8 characters';
            return null;
        }

        return $password;
    }

    private function jsonObject(mixed $value, string $field, array &$errors): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value) || array_is_list($value)) {
            $errors[] = $field . ' must be a JSON object';
            return [];
        }

        return $value;
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
            return '{}';
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function booleanValue(mixed $value, string $field, array &$errors): bool
    {
        if (in_array($value, [1, '1', true, 'true', 'TRUE', 'yes', 'YES'], true)) {
            return true;
        }
        if (in_array($value, [0, '0', false, 'false', 'FALSE', 'no', 'NO'], true)) {
            return false;
        }

        $errors[] = $field . ' must be true, false, 1, or 0';
        return false;
    }

    private function activeValue(mixed $value, array &$errors): int
    {
        if (in_array($value, [1, '1', true, 'true', 'TRUE'], true)) {
            return 1;
        }
        if (in_array($value, [0, '0', false, 'false', 'FALSE'], true)) {
            return 0;
        }

        $errors[] = 'active must be true, false, 1, or 0';
        return 0;
    }

    private function optionalBooleanValue(mixed $value, string $field, bool $default, array &$errors): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return $default;
        }

        return $this->booleanValue($value, $field, $errors);
    }

    private function optionalActiveValue(mixed $value, int $default, array &$errors): int
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return $default;
        }

        return $this->activeValue($value, $errors);
    }

    private function handlePdoException(PDOException $exception, string $resource): void
    {
        if ($exception->getCode() === '23505') {
            Response::json(409, false, $resource . ' already exists', []);
            return;
        }

        if ($exception->getCode() === '23503') {
            Response::json(409, false, 'foreign key reference does not exist', []);
            return;
        }

        throw $exception;
    }

    private function hashServerValue(mixed $value): ?string
    {
        $clean = $this->nullableText($value);
        return $clean === null ? null : hash('sha256', $clean);
    }

    private function exposeResetToken(): bool
    {
        return in_array(strtolower((string) getenv('AUTH_EXPOSE_RESET_TOKEN')), ['1', 'true', 'yes'], true);
    }

    private function appId(): string
    {
        return (string) ($this->appContext['app_id'] ?? $this->appContext['created_for_app_id'] ?? 'app_8301');
    }

    private function defaultDisplayName(string $email): string
    {
        $local = explode('@', $email)[0] ?? 'User';
        $clean = trim(str_replace(['.', '_', '-'], ' ', $local));
        return $clean === '' ? 'User' : ucwords($clean);
    }

    private function nullableText(mixed $value): ?string
    {
        $clean = trim((string) ($value ?? ''));
        return $clean === '' ? null : $clean;
    }
}
