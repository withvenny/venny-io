<?php

declare(strict_types=1);

namespace VennyIO\Controllers;

use DateTimeImmutable;
use PDOException;
use VennyIO\Kernel\Request;
use VennyIO\Repositories\PlatformResourceRepository;
use VennyIO\Support\Ids;
use VennyIO\Support\Response;

final class ContentController
{
    private const CREATE_FIELDS = [
        'content_attributes', 'content_startdate', 'content_enddate', 'content_slug', 'content_title',
        'content_description', 'content_body', 'content_tags', 'content_template', 'content_visible',
        'created_by_user_id', 'created_for_app_id', 'event_id', 'process_id', 'access', 'status', 'active',
    ];

    public function __construct(private PlatformResourceRepository $repository)
    {
    }

    public function index(): void
    {
        Response::json(200, true, 'content retrieved successfully', $this->repository->all());
    }

    public function show(string $id): void
    {
        $row = $this->repository->find($id);
        if ($row === []) {
            Response::json(404, false, 'content not found', []);
            return;
        }

        Response::json(200, true, 'content retrieved successfully', $row);
    }

    public function showBySlug(string $slug): void
    {
        $normalizedSlug = $this->normalizeSlug($slug);

        if ($normalizedSlug === '') {
            Response::json(422, false, 'validation failed', ['errors' => ['content_slug must include at least one letter or number']]);
            return;
        }

        $row = $this->repository->findOneBy([
            'content_slug' => $normalizedSlug,
            'content_visible' => 'true',
            'status' => 'active',
            'active' => 1,
        ]);

        if ($row === []) {
            Response::json(404, false, 'content not found', []);
            return;
        }

        Response::json(200, true, 'content retrieved successfully', $row);
    }

    public function store(Request $request): void
    {
        $input = $request->input();
        $errors = [];

        $contentId = $this->nullableText($input['content_id'] ?? null) ?? Ids::generate('content');
        if (!Ids::is('content', $contentId)) {
            $errors[] = 'content_id must be a valid Venny I/O content id';
        }

        $payload = [
            'content_id' => $contentId,
            'content_attributes' => $this->jsonObject('content_attributes', $input['content_attributes'] ?? [], $errors),
            'content_startdate' => $this->timestampValue('content_startdate', $input['content_startdate'] ?? null, $errors),
            'content_enddate' => $this->timestampValue('content_enddate', $input['content_enddate'] ?? null, $errors),
            'content_slug' => $this->slugValue($input['content_slug'] ?? null, $errors),
            'content_title' => $this->requiredText('content_title', $input['content_title'] ?? null, $errors),
            'content_description' => $this->nullableLimitedText('content_description', $input['content_description'] ?? null, 280, $errors),
            'content_body' => $this->requiredText('content_body', $input['content_body'] ?? null, $errors),
            'content_tags' => $this->jsonAnyNullable('content_tags', $input['content_tags'] ?? null, $errors),
            'content_template' => $this->nullableText($input['content_template'] ?? null),
            'content_visible' => $this->booleanValue('content_visible', $input['content_visible'] ?? true, $errors),
            'created_by_user_id' => $this->nullableText($input['created_by_user_id'] ?? null) ?? 'user_8301',
            'created_for_app_id' => $this->nullableText($input['created_for_app_id'] ?? null) ?? 'app_8301',
            'event_id' => $this->nullableText($input['event_id'] ?? null) ?? 'event_8301',
            'process_id' => $this->nullableText($input['process_id'] ?? null) ?? 'process_8301',
            'access' => strtolower($this->nullableText($input['access'] ?? null) ?? 'public'),
            'status' => strtolower($this->nullableText($input['status'] ?? null) ?? 'active'),
            'active' => $this->activeValue('active', $input['active'] ?? 1, $errors),
        ];

        $this->validateDateRange($payload['content_startdate'], $payload['content_enddate'], $errors);

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

        Response::json(201, true, 'content added successfully', $created);
    }

    public function update(string $id, Request $request): void
    {
        $input = $request->input();
        unset($input['_method'], $input['setup_token'], $input['content_id']);

        $updates = [];
        $errors = [];

        foreach (self::CREATE_FIELDS as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $updates[$field] = match ($field) {
                'content_attributes' => $this->jsonObject($field, $input[$field], $errors),
                'content_startdate', 'content_enddate' => $this->timestampValue($field, $input[$field], $errors),
                'content_slug' => $this->slugValue($input[$field], $errors),
                'content_title', 'content_body' => $this->requiredText($field, $input[$field], $errors),
                'content_description' => $this->nullableLimitedText($field, $input[$field], 280, $errors),
                'content_tags' => $this->jsonAnyNullable($field, $input[$field], $errors),
                'content_template' => $this->nullableText($input[$field]),
                'content_visible' => $this->booleanValue($field, $input[$field], $errors),
                'access', 'status' => strtolower($this->requiredText($field, $input[$field], $errors)),
                'active' => $this->activeValue($field, $input[$field], $errors),
                default => $this->requiredText($field, $input[$field], $errors),
            };
        }

        $start = $updates['content_startdate'] ?? null;
        $end = $updates['content_enddate'] ?? null;
        if ($start !== null && $end !== null) {
            $this->validateDateRange($start, $end, $errors);
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
            $updated = $this->repository->update($id, $updates, self::CREATE_FIELDS);
        } catch (PDOException $exception) {
            $this->handlePdoException($exception);
            return;
        }

        if ($updated === []) {
            Response::json(404, false, 'content not found', []);
            return;
        }

        Response::json(200, true, 'content updated successfully', $updated);
    }

    public function destroy(string $id): void
    {
        $archived = $this->repository->softDelete($id);
        if ($archived === []) {
            Response::json(404, false, 'content not found', []);
            return;
        }

        Response::json(200, true, 'content archived successfully', $archived);
    }

    private function requiredText(string $field, mixed $value, array &$errors): string
    {
        $clean = $this->nullableText($value);
        if ($clean === null) {
            $errors[] = $field . ' is required';
            return '';
        }
        return $clean;
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value) || is_object($value)) {
            return null;
        }
        $clean = trim((string) $value);
        return $clean === '' ? null : $clean;
    }

    private function nullableLimitedText(string $field, mixed $value, int $max, array &$errors): ?string
    {
        $clean = $this->nullableText($value);
        if ($clean !== null && $this->stringLength($clean) > $max) {
            $errors[] = $field . ' must be ' . $max . ' characters or fewer';
        }
        return $clean;
    }

    private function stringLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return \mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }

    private function slugValue(mixed $value, array &$errors): string
    {
        $clean = $this->normalizeSlug($this->requiredText('content_slug', $value, $errors));

        if ($clean === '') {
            $errors[] = 'content_slug must include at least one letter or number';
        }

        return $clean;
    }

    private function normalizeSlug(mixed $value): string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return '';
        }

        $clean = strtolower(trim((string) $value));
        $clean = preg_replace('/[^a-z0-9]+/', '-', $clean) ?: '';

        return trim($clean, '-');
    }

    private function jsonObject(string $field, mixed $value, array &$errors): string
    {
        $decoded = $this->decodeJsonIfNeeded($value);
        if (!is_array($decoded) || array_is_list($decoded)) {
            $errors[] = $field . ' must be a JSON object';
            return '{}';
        }

        return json_encode($decoded, JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function jsonAnyNullable(string $field, mixed $value, array &$errors): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = $this->decodeJsonIfNeeded($value);
        if (!is_array($decoded)) {
            $errors[] = $field . ' must be a JSON array or object';
            return null;
        }

        return json_encode($decoded, JSON_UNESCAPED_SLASHES) ?: null;
    }

    private function decodeJsonIfNeeded(mixed $value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return $value;
    }

    private function timestampValue(string $field, mixed $value, array &$errors): ?string
    {
        $clean = $this->nullableText($value);
        if ($clean === null) {
            return null;
        }

        try {
            return (new DateTimeImmutable($clean))->format(DateTimeImmutable::ATOM);
        } catch (\Throwable) {
            $errors[] = $field . ' must be a valid timestamp';
            return null;
        }
    }

    private function validateDateRange(?string $start, ?string $end, array &$errors): void
    {
        if ($start === null || $end === null) {
            return;
        }

        if (new DateTimeImmutable($end) < new DateTimeImmutable($start)) {
            $errors[] = 'content_enddate must be greater than or equal to content_startdate';
        }
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

    private function handlePdoException(PDOException $exception): void
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        if ($sqlState === '23505') {
            Response::json(409, false, 'content already exists', []);
            return;
        }
        if ($sqlState === '23503') {
            Response::json(422, false, 'referenced app or user does not exist', []);
            return;
        }

        Response::json(500, false, 'content could not be saved', [
            'error' => $exception->getMessage(),
        ]);
    }
}
