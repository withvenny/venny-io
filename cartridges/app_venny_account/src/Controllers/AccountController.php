<?php

declare(strict_types=1);

namespace VennyIO\Controllers;

use PDOException;
use VennyIO\Kernel\Request;
use VennyIO\Repositories\AccountRepository;
use VennyIO\Support\Response;

final class AccountController
{
    public function __construct(
        private AccountRepository $repository,
        private array $appContext,
        private array $sessionContext
    ) {
    }

    public function show(Request $request): void
    {
        Response::json(200, true, 'account retrieved successfully', $this->repository->account($this->userId(), $this->appId()));
    }

    public function update(Request $request): void
    {
        $input = $request->input();
        $errors = [];

        $email = $input['email'] ?? $input['user_email'] ?? null;
        if ($email !== null && trim((string) $email) !== '' && filter_var(strtolower(trim((string) $email)), FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'email must be a valid email address';
        }

        foreach (['user_addresses', 'person_addresses', 'address', 'user_phones', 'person_phones', 'user_optins', 'communication_preferences', 'profile_attributes', 'person_attributes', 'user_attributes', 'social', 'handles'] as $field) {
            if (array_key_exists($field, $input) && !$this->isJsonObject($input[$field])) {
                $errors[] = $field . ' must be a JSON object';
            }
        }

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        unset(
            $input['user_id'],
            $input['person_id'],
            $input['profile_id'],
            $input['created_by_user_id'],
            $input['created_for_app_id'],
            $input['user_passwordhash'],
            $input['password'],
            $input['current_password'],
            $input['password_confirmation']
        );

        try {
            $account = $this->repository->updateAccount($this->userId(), $this->appId(), $input);
        } catch (PDOException $exception) {
            $this->handlePdoException($exception);
            return;
        }

        if ($account === []) {
            Response::json(404, false, 'account not found', []);
            return;
        }

        Response::json(200, true, 'account updated successfully', $account);
    }

    public function updatePassword(Request $request): void
    {
        $input = $request->input();
        $errors = [];

        $currentPassword = (string) ($input['current_password'] ?? '');
        $password = (string) ($input['password'] ?? $input['new_password'] ?? '');
        $confirmation = (string) ($input['password_confirmation'] ?? $input['new_password_confirmation'] ?? '');

        if ($currentPassword === '') {
            $errors[] = 'current_password is required';
        }

        if ($password === '') {
            $errors[] = 'password is required';
        } elseif (strlen($password) < 8) {
            $errors[] = 'password must be at least 8 characters';
        }

        if ($confirmation !== '' && !hash_equals($password, $confirmation)) {
            $errors[] = 'password_confirmation must match password';
        }

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        $user = $this->repository->findUser($this->userId(), $this->appId(), true);
        $hash = (string) ($user['user_passwordhash'] ?? '');

        if ($user === [] || $hash === '' || !password_verify($currentPassword, $hash)) {
            Response::json(401, false, 'current password is invalid', []);
            return;
        }

        $updated = $this->repository->updatePassword($this->userId(), $this->appId(), password_hash($password, PASSWORD_DEFAULT));
        if ($updated === []) {
            Response::json(404, false, 'account not found', []);
            return;
        }

        Response::json(200, true, 'password updated successfully', ['user' => $updated]);
    }

    public function signOut(Request $request): void
    {
        $session = $this->repository->revokeSession($this->sessionId(), $this->userId(), $this->appId());
        if ($session === []) {
            Response::json(404, false, 'session not found', []);
            return;
        }

        Response::json(200, true, 'signed out successfully', ['session' => $session]);
    }

    private function isJsonObject(mixed $value): bool
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        return is_array($value) && !array_is_list($value);
    }

    private function handlePdoException(PDOException $exception): void
    {
        if ($exception->getCode() === '23505') {
            Response::json(409, false, 'account field already exists', []);
            return;
        }

        if ($exception->getCode() === '23503') {
            Response::json(409, false, 'foreign key reference does not exist', []);
            return;
        }

        throw $exception;
    }

    private function appId(): string
    {
        return (string) ($this->appContext['app_id'] ?? $this->appContext['created_for_app_id'] ?? '');
    }

    private function userId(): string
    {
        return (string) ($this->sessionContext['session_user_id'] ?? $this->sessionContext['user_id'] ?? '');
    }

    private function sessionId(): string
    {
        return (string) ($this->sessionContext['session_id'] ?? '');
    }
}
