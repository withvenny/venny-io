<?php

declare(strict_types=1);

namespace VennyIO\Controllers;

use DateTimeImmutable;
use PDOException;
use VennyIO\Kernel\Request;
use VennyIO\Repositories\SessionsRepository;
use VennyIO\Support\Ids;
use VennyIO\Support\Response;

final class SessionsController
{
    public function __construct(private SessionsRepository $sessions)
    {
    }

    public function index(): void
    {
        Response::json(200, true, 'sessions retrieved successfully', $this->sessions->all());
    }

    public function show(string $sessionId): void
    {
        $session = $this->sessions->find($sessionId);
        if ($session === []) {
            Response::json(404, false, 'session not found', []);
            return;
        }
        Response::json(200, true, 'session retrieved successfully', $session);
    }

    public function store(Request $request): void
    {
        $input = $request->input();
        $errors = [];

        $sessionId = $this->nullableText($input['session_id'] ?? null) ?? Ids::generate('session');
        if (!Ids::is('session', $sessionId)) {
            $errors[] = 'session_id must be a valid Venny I/O session id';
        }

        $attributes = $this->jsonObject($input['session_attributes'] ?? [], $errors);
        $expiresAt = $this->timestamp($input['session_expiresat'] ?? '+30 days', 'session_expiresat', $errors);
        $userAgent = $this->nullableText($input['session_useragent'] ?? ($request->server['HTTP_USER_AGENT'] ?? null)) ?? 'unknown';
        $active = $this->activeValue($input['active'] ?? 1, $errors);

        $rawRefreshToken = $this->nullableText($input['session_refreshtoken'] ?? null) ?? $this->generateRefreshToken();
        $refreshTokenHash = password_hash($rawRefreshToken, PASSWORD_DEFAULT);

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        $session = [
            'session_id' => $sessionId,
            'session_attributes' => $attributes,
            'session_refreshtokenhash' => $refreshTokenHash,
            'session_ipaddresshash' => $this->nullableText($input['session_ipaddresshash'] ?? null),
            'session_ipcountryhash' => $this->nullableText($input['session_ipcountryhash'] ?? null),
            'session_user_id' => $this->nullableText($input['session_user_id'] ?? null),
            'session_useragent' => $userAgent,
            'session_expiresat' => $expiresAt,
            'session_revokedat' => $this->timestamp($input['session_revokedat'] ?? null, 'session_revokedat', $errors),
            'session_lastseenat' => $this->timestamp($input['session_lastseenat'] ?? null, 'session_lastseenat', $errors),
            'created_by_user_id' => $this->nullableText($input['created_by_user_id'] ?? null) ?? 'user_8301',
            'created_for_app_id' => $this->nullableText($input['created_for_app_id'] ?? null) ?? 'app_8301',
            'event_id' => $this->nullableText($input['event_id'] ?? null) ?? 'event_8301',
            'process_id' => $this->nullableText($input['process_id'] ?? null) ?? 'process_8301',
            'access' => $this->nullableText($input['access'] ?? null) ?? 'private',
            'status' => $this->nullableText($input['status'] ?? null) ?? 'active',
            'active' => $active,
        ];

        try {
            $created = $this->sessions->create($session);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23505') {
                Response::json(409, false, 'session already exists', []);
                return;
            }
            if ($exception->getCode() === '23503') {
                Response::json(409, false, 'foreign key reference does not exist', []);
                return;
            }
            throw $exception;
        }

        Response::json(201, true, 'session added successfully', [
            'raw_refresh_token' => $rawRefreshToken,
            'warning' => 'Store this raw refresh token now. It is returned only once and cannot be retrieved later.',
            'session' => $created,
        ]);
    }

    public function update(string $sessionId, Request $request): void
    {
        $input = $request->input();
        unset($input['_method'], $input['setup_token'], $input['session_id'], $input['session_refreshtoken'], $input['session_refreshtokenhash']);

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

        $errors = [];
        $updates = [];
        foreach ($allowed as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }
            $value = $input[$field];
            if ($field === 'session_attributes') {
                $updates[$field] = $this->jsonObject($value, $errors);
            } elseif (in_array($field, ['session_expiresat', 'session_revokedat', 'session_lastseenat'], true)) {
                $updates[$field] = $this->timestamp($value, $field, $errors);
            } elseif ($field === 'active') {
                $updates[$field] = $this->activeValue($value, $errors);
            } else {
                $updates[$field] = $this->nullableText($value);
            }
        }

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }
        if ($updates === []) {
            Response::json(422, false, 'no valid update fields provided', []);
            return;
        }

        $updated = $this->sessions->update($sessionId, $updates);
        if ($updated === []) {
            Response::json(404, false, 'session not found', []);
            return;
        }
        Response::json(200, true, 'session updated successfully', $updated);
    }

    public function destroy(string $sessionId): void
    {
        $archived = $this->sessions->softDelete($sessionId);
        if ($archived === []) {
            Response::json(404, false, 'session not found', []);
            return;
        }
        Response::json(200, true, 'session archived successfully', $archived);
    }

    private function jsonObject(mixed $value, array &$errors): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }
        if (!is_array($value) || array_is_list($value)) {
            $errors[] = 'session_attributes must be a JSON object';
            return '{}';
        }
        return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function timestamp(mixed $value, string $field, array &$errors): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }
        try {
            return (new DateTimeImmutable((string) $value))->format('c');
        } catch (\Throwable) {
            $errors[] = $field . ' must be a valid timestamp';
            return null;
        }
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

    private function nullableText(mixed $value): ?string
    {
        $clean = trim((string) ($value ?? ''));
        return $clean === '' ? null : $clean;
    }

    private function generateRefreshToken(): string
    {
        return 'venny_refresh_' . bin2hex(random_bytes(32));
    }
}
