<?php

declare(strict_types=1);

namespace VennyIO\Controllers;

use DateTimeImmutable;
use PDOException;
use VennyIO\Kernel\Request;
use VennyIO\Repositories\PlatformResourceRepository;
use VennyIO\Support\Ids;
use VennyIO\Support\Response;

final class PlatformResourceController
{
    public function __construct(
        private string $resourceName,
        private string $entityName,
        private string $primaryKey,
        private PlatformResourceRepository $repository,
        private array $config
    ) {
    }

    /*
    public function index(): void
    {
        Response::json(200, true, $this->resourceName . ' retrieved successfully', $this->repository->all());
    }
    */

    public function index(?Request $request = null): void {
        $filters = $request?->query ?? [];

        Response::json(
            200,
            true,
            $this->resourceName . ' retrieved successfully',
            $this->repository->all($filters)
        );
    }

    public function show(string $id): void
    {
        $row = $this->repository->find($id);
        if ($row === []) {
            Response::json(404, false, rtrim($this->resourceName, 's') . ' not found', []);
            return;
        }

        Response::json(200, true, rtrim($this->resourceName, 's') . ' retrieved successfully', $row);
    }

    public function store(Request $request): void
    {
        $input = $request->input();
        $payload = [];
        $errors = [];

        $id = $this->nullableText($input[$this->primaryKey] ?? null) ?? Ids::generate($this->entityName);
        if (!Ids::is($this->entityName, $id)) {
            $errors[] = $this->primaryKey . ' must be a valid Venny I/O ' . $this->entityName . ' id';
        }
        $payload[$this->primaryKey] = $id;

        foreach ($this->config['create'] as $field => $rules) {
            if ($field === $this->primaryKey) {
                continue;
            }

            $exists = array_key_exists($field, $input);
            $value = $exists ? $input[$field] : ($rules['default'] ?? null);

            if (($rules['required'] ?? false) && $this->isBlank($value)) {
                $errors[] = $field . ' is required';
                continue;
            }

            $payload[$field] = $this->cleanByType($field, $value, $rules, $errors);
        }

        $this->applyAuditDefaults($payload);

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        try {
            $created = $this->repository->create($payload);
        } catch (PDOException $exception) {
            $this->handlePdoException($exception);
            return;
        }

        Response::json(201, true, rtrim($this->resourceName, 's') . ' added successfully', $created);
    }

    public function update(string $id, Request $request): void
    {
        $input = $request->input();
        unset($input['_method'], $input['setup_token'], $input[$this->primaryKey]);

        $updates = [];
        $errors = [];
        $allowed = array_keys($this->config['update']);

        foreach ($this->config['update'] as $field => $rules) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $value = $input[$field];
            if (($rules['required'] ?? false) && $this->isBlank($value)) {
                $errors[] = $field . ' cannot be empty when provided';
                continue;
            }

            $updates[$field] = $this->cleanByType($field, $value, $rules, $errors);
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
            $updated = $this->repository->update($id, $updates, $allowed);
        } catch (PDOException $exception) {
            $this->handlePdoException($exception);
            return;
        }

        if ($updated === []) {
            Response::json(404, false, rtrim($this->resourceName, 's') . ' not found', []);
            return;
        }

        Response::json(200, true, rtrim($this->resourceName, 's') . ' updated successfully', $updated);
    }

    public function destroy(string $id): void
    {
        $archived = $this->repository->softDelete($id);
        if ($archived === []) {
            Response::json(404, false, rtrim($this->resourceName, 's') . ' not found', []);
            return;
        }

        Response::json(200, true, rtrim($this->resourceName, 's') . ' archived successfully', $archived);
    }

    private function cleanByType(string $field, mixed $value, array $rules, array &$errors): mixed
    {
        $type = $rules['type'] ?? 'text';

        if ($this->isBlank($value) && ($rules['nullable'] ?? false)) {
            return null;
        }

        return match ($type) {
            'json_object' => $this->jsonValue($field, $value, $errors, false),
            'json_array' => $this->jsonValue($field, $value, $errors, true),
            'int' => $this->intValue($field, $value, $errors, false),
            'nonnegative_int' => $this->intValue($field, $value, $errors, true),
            'active' => $this->activeValue($field, $value, $errors),
            'boolean' => $this->booleanValue($field, $value, $errors),
            'timestamp' => $this->timestampValue($field, $value, $errors),
            'text_lower' => strtolower($this->cleanText($value)),
            default => $this->cleanText($value),
        };
    }

    private function jsonValue(string $field, mixed $value, array &$errors, bool $mustBeList): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (!is_array($value)) {
            $errors[] = $field . ' must be valid JSON';
            return $mustBeList ? '[]' : '{}';
        }

        if ($mustBeList && !array_is_list($value)) {
            $errors[] = $field . ' must be a JSON array';
            return '[]';
        }

        if (!$mustBeList && $value === []) {
            return '{}';
        }

        if (!$mustBeList && array_is_list($value)) {
            $errors[] = $field . ' must be a JSON object';
            return '{}';
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES) ?: ($mustBeList ? '[]' : '{}');
    }

    private function intValue(string $field, mixed $value, array &$errors, bool $nonnegative): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            $errors[] = $field . ' must be an integer';
            return 0;
        }

        $int = (int) $value;
        if ($nonnegative && $int < 0) {
            $errors[] = $field . ' must be zero or greater';
            return 0;
        }

        return $int;
    }

    private function activeValue(string $field, mixed $value, array &$errors): int
    {
        if (in_array($value, [1, '1', true, 'true', 'TRUE'], true)) {
            return 1;
        }
        if (in_array($value, [0, '0', false, 'false', 'FALSE'], true)) {
            return 0;
        }

        $errors[] = $field . ' must be true, false, 1, or 0';
        return 0;
    }


    private function booleanValue(string $field, mixed $value, array &$errors): bool
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

    private function timestampValue(string $field, mixed $value, array &$errors): ?string
    {
        if ($this->isBlank($value)) {
            return null;
        }

        try {
            return (new DateTimeImmutable((string) $value))->format('c');
        } catch (\Throwable) {
            $errors[] = $field . ' must be a valid timestamp';
            return null;
        }
    }

    private function applyAuditDefaults(array &$payload): void
    {
        $payload['created_by_user_id'] = $this->nullableText($payload['created_by_user_id'] ?? null) ?? 'user_8301';
        if (in_array('created_for_app_id', $this->config['columns'], true)) {
            $payload['created_for_app_id'] = $this->nullableText($payload['created_for_app_id'] ?? null) ?? 'app_8301';
        }
        $payload['event_id'] = $this->nullableText($payload['event_id'] ?? null) ?? 'event_8301';
        $payload['process_id'] = $this->nullableText($payload['process_id'] ?? null) ?? 'process_8301';
        $payload['access'] = $this->nullableText($payload['access'] ?? null) ?? 'private';
        $payload['status'] = $this->nullableText($payload['status'] ?? null) ?? 'active';
        $payload['active'] = (int) ($payload['active'] ?? 1);
    }

    private function handlePdoException(PDOException $exception): void
    {
        if ($exception->getCode() === '23505') {
            Response::json(409, false, rtrim($this->resourceName, 's') . ' already exists', []);
            return;
        }

        if ($exception->getCode() === '23503') {
            Response::json(409, false, 'foreign key reference does not exist', []);
            return;
        }

        throw $exception;
    }

    private function cleanText(mixed $value): string
    {
        return trim((string) $value);
    }

    private function nullableText(mixed $value): ?string
    {
        $clean = $this->cleanText($value ?? '');
        return $clean === '' ? null : $clean;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
