<?php

declare(strict_types=1);

namespace VennyIO\Controllers;

use DateTimeImmutable;
use PDOException;
use VennyIO\Kernel\Request;
use VennyIO\Repositories\KeysRepository;
use VennyIO\Support\Ids;
use VennyIO\Support\Response;

final class KeysController
{
    public function __construct(private KeysRepository $keys)
    {
    }

    public function index(): void
    {
        $keys = $this->keys->all();

        Response::json(200, true, 'keys retrieved successfully', $keys);
    }

    public function show(string $keyId): void
    {
        $key = $this->keys->findByKeyId($keyId);

        if ($key === []) {
            Response::json(404, false, 'key not found', []);
            return;
        }

        Response::json(200, true, 'key retrieved successfully', $key);
    }

    public function store(Request $request): void
    {
        $input = $request->input();

        $keyName = $this->cleanText($input['key_name'] ?? '');
        $errors = [];

        if ($keyName === '') {
            $errors[] = 'key_name is required';
        }

        $keyId = $this->nullableText($input['key_id'] ?? null) ?? Ids::generate('key');
        if (!Ids::is('key', $keyId)) {
            $errors[] = 'key_id must be a valid Venny I/O key id';
        }

        $keyAttributes = $this->jsonObject($input['key_attributes'] ?? []);
        if ($keyAttributes === null) {
            $errors[] = 'key_attributes must be a JSON object';
        }

        $rateLimit = $this->positiveInt($input['key_ratelimit'] ?? 1000);
        if ($rateLimit === null) {
            $errors[] = 'key_ratelimit must be a positive integer';
        }

        $windowSize = $this->positiveInt($input['key_windowsize'] ?? 60);
        if ($windowSize === null) {
            $errors[] = 'key_windowsize must be a positive integer';
        }

        $keyExpires = $this->nullableTimestamp($input['key_expires'] ?? null, 'key_expires', $errors);

        $status = strtolower($this->cleanText($input['status'] ?? 'active'));
        if ($status === '') {
            $errors[] = 'status cannot be empty';
        }

        $access = strtolower($this->cleanText($input['access'] ?? 'private'));
        if ($access === '') {
            $errors[] = 'access cannot be empty';
        }

        $active = $this->activeValue($input['active'] ?? 1);
        if ($active === null) {
            $errors[] = 'active must be true, false, 1, or 0';
        }

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        $rawKey = $this->generateRawKey();
        $keyPrefix = substr($rawKey, 0, 20);
        $keyHash = password_hash($rawKey, PASSWORD_DEFAULT);

        $key = [
            'key_id' => $keyId,
            'key_attributes' => $keyAttributes,
            'key_name' => $keyName,
            'key_prefix' => $keyPrefix,
            'key_hash' => $keyHash,
            'key_ratelimit' => $rateLimit,
            'key_windowsize' => $windowSize,
            'key_lastused' => null,
            'key_expires' => $keyExpires,
            'created_by_user_id' => $this->nullableText($input['created_by_user_id'] ?? null) ?? 'user_8301',
            'created_for_app_id' => $this->nullableText($input['created_for_app_id'] ?? null) ?? 'app_8301',
            'event_id' => $this->nullableText($input['event_id'] ?? null) ?? 'event_8301',
            'process_id' => $this->nullableText($input['process_id'] ?? null) ?? 'process_8301',
            'access' => $access,
            'status' => $status,
            'active' => $active,
        ];

        try {
            $created = $this->keys->create($key);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23505') {
                Response::json(409, false, 'key already exists', []);
                return;
            }

            if ($exception->getCode() === '23503') {
                Response::json(409, false, 'created_for_app_id does not reference an existing app', []);
                return;
            }

            throw $exception;
        }

        Response::json(201, true, 'key added successfully', [
            'raw_key' => $rawKey,
            'warning' => 'Store this raw key now. It is returned only once and cannot be retrieved later.',
            'key' => $created,
        ]);
    }

    public function update(string $keyId, Request $request): void
    {
        $input = $request->input();
        unset($input['_method'], $input['setup_token'], $input['key_id'], $input['key_hash'], $input['key_prefix']);

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

        $updates = [];
        $errors = [];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $value = $input[$field];

            if ($field === 'key_attributes') {
                $json = $this->jsonObject($value);
                if ($json === null) {
                    $errors[] = 'key_attributes must be a JSON object';
                    continue;
                }
                $updates[$field] = $json;
                continue;
            }

            if (in_array($field, ['key_ratelimit', 'key_windowsize'], true)) {
                $int = $this->positiveInt($value);
                if ($int === null) {
                    $errors[] = $field . ' must be a positive integer';
                    continue;
                }
                $updates[$field] = $int;
                continue;
            }

            if ($field === 'key_expires') {
                $updates[$field] = $this->nullableTimestamp($value, 'key_expires', $errors);
                continue;
            }

            if ($field === 'active') {
                $active = $this->activeValue($value);
                if ($active === null) {
                    $errors[] = 'active must be true, false, 1, or 0';
                    continue;
                }
                $updates[$field] = $active;
                continue;
            }

            $clean = $this->cleanText($value);

            if ($field === 'key_name' && $clean === '') {
                $errors[] = 'key_name cannot be empty when provided';
                continue;
            }

            if (in_array($field, ['status', 'access'], true) && $clean === '') {
                $errors[] = $field . ' cannot be empty when provided';
                continue;
            }

            $updates[$field] = $clean === '' ? null : strtolower($clean);

            if (in_array($field, ['key_name', 'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id'], true)) {
                $updates[$field] = $clean === '' ? null : $clean;
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

        try {
            $updated = $this->keys->update($keyId, $updates);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23505') {
                Response::json(409, false, 'key already exists', []);
                return;
            }

            if ($exception->getCode() === '23503') {
                Response::json(409, false, 'created_for_app_id does not reference an existing app', []);
                return;
            }

            throw $exception;
        }

        if ($updated === []) {
            Response::json(404, false, 'key not found', []);
            return;
        }

        Response::json(200, true, 'key updated successfully', $updated);
    }

    public function destroy(string $keyId): void
    {
        $archived = $this->keys->softDelete($keyId);

        if ($archived === []) {
            Response::json(404, false, 'key not found', []);
            return;
        }

        Response::json(200, true, 'key archived successfully', $archived);
    }

    private function generateRawKey(): string
    {
        return 'venny_live_' . bin2hex(random_bytes(32));
    }

    private function jsonObject(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return '{}';
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return null;
            }

            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded) || array_is_list($decoded)) {
                return null;
            }

            return json_encode($decoded, JSON_UNESCAPED_SLASHES);
        }

        return null;
    }

    private function activeValue(mixed $value): ?int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_int($value) && in_array($value, [0, 1], true)) {
            return $value;
        }

        if (is_string($value)) {
            $clean = strtolower(trim($value));
            if (in_array($clean, ['1', 'true', 'yes'], true)) {
                return 1;
            }
            if (in_array($clean, ['0', 'false', 'no'], true)) {
                return 0;
            }
        }

        return null;
    }

    private function positiveInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && preg_match('/^[1-9][0-9]*$/', trim($value))) {
            return (int) trim($value);
        }

        return null;
    }

    /**
     * @param list<string> $errors
     */
    private function nullableTimestamp(mixed $value, string $field, array &$errors): ?string
    {
        $clean = $this->cleanText($value);
        if ($clean === '') {
            return null;
        }

        try {
            new DateTimeImmutable($clean);
        } catch (\Throwable) {
            $errors[] = $field . ' must be a valid date/time string';
            return null;
        }

        return $clean;
    }

    private function cleanText(mixed $value): string
    {
        return trim((string) $value);
    }

    private function nullableText(mixed $value): ?string
    {
        $clean = $this->cleanText($value);
        return $clean === '' ? null : $clean;
    }
}
