<?php

declare(strict_types=1);

namespace VennyIO\Controllers;

use PDOException;
use VennyIO\Kernel\Request;
use VennyIO\Repositories\AppsRepository;
use VennyIO\Support\Ids;
use VennyIO\Support\Response;

final class AppsController
{
    public function __construct(private AppsRepository $apps)
    {
    }


    public function index(): void
    {        $apps = $this->apps->all();

        Response::json(200, true, 'apps retrieved successfully', $apps);
    }


    public function show(string $appId): void
    {        $app = $this->apps->findByAppId($appId);

        if ($app === []) {
            Response::json(404, false, 'app not found', []);
            return;
        }

        Response::json(200, true, 'app retrieved successfully', $app);
    }

    public function store(?Request $request = null): void
    {
        $input = $request instanceof Request ? $request->input() : $this->requestInput();

        $appName = $this->cleanText($input['app_name'] ?? '');
        $appSlug = $this->cleanSlug($input['app_slug'] ?? '');
        $appDescription = $this->cleanText($input['app_description'] ?? '');

        $errors = [];

        if ($appName === '') {
            $errors[] = 'app_name is required';
        }

        if ($appSlug === '') {
            $errors[] = 'app_slug is required';
        }

        if ($appDescription === '') {
            $errors[] = 'app_description is required';
        }

        $appId = $this->nullableText($input['app_id'] ?? null) ?? Ids::generate('app');
        if (!preg_match('/^app_[0-9A-Za-z]{4,64}$/', $appId)) {
            $errors[] = 'app_id must start with app_ and contain 4 to 64 letters or numbers after the prefix';
        }

        $appAttributes = $this->jsonObject($input['app_attributes'] ?? []);
        if ($appAttributes === null) {
            $errors[] = 'app_attributes must be a JSON object';
        }

        $appContactEmail = $this->cleanText($input['app_contactemail'] ?? '');
        if ($appContactEmail !== '' && !filter_var($appContactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'app_contactemail must be a valid email address';
        }

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

        $app = [
            'app_id' => $appId,
            'app_attributes' => $appAttributes,
            'app_name' => $appName,
            'app_slug' => $appSlug,
            'app_description' => $appDescription,
            'app_domain' => $this->nullableText($input['app_domain'] ?? null),
            'app_website' => $this->nullableText($input['app_website'] ?? null),
            'app_contactname' => $this->nullableText($input['app_contactname'] ?? null),
            'app_contactemail' => $appContactEmail !== '' ? $appContactEmail : null,
            'app_contactphone' => $this->nullableText($input['app_contactphone'] ?? null),
            'app_environment' => $this->nullableText($input['app_environment'] ?? null) ?? 'production',
            'app_type' => $this->nullableText($input['app_type'] ?? null) ?? 'internal',
            'created_by_user_id' => $this->nullableText($input['created_by_user_id'] ?? null) ?? 'user_8301',
            'event_id' => $this->nullableText($input['event_id'] ?? null) ?? 'event_8301',
            'process_id' => $this->nullableText($input['process_id'] ?? null) ?? 'process_8301',
            'access' => $access,
            'status' => $status,
            'active' => $active,
        ];

        try {
            $created = $this->apps->create($app);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23505') {
                Response::json(409, false, 'app already exists', []);
                return;
            }

            throw $exception;
        }

        Response::json(201, true, 'app added successfully', $created);
    }

    public function update(string $appId, ?Request $request = null): void
    {        $input = $request instanceof Request ? $request->input() : $this->requestInput();
        unset($input['_method'], $input['setup_token'], $input['app_id']);

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

        $updates = [];
        $errors = [];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $value = $input[$field];

            if ($field === 'app_attributes') {
                $json = $this->jsonObject($value);
                if ($json === null) {
                    $errors[] = 'app_attributes must be a JSON object';
                    continue;
                }
                $updates[$field] = $json;
                continue;
            }

            if ($field === 'app_slug') {
                $clean = $this->cleanSlug((string) $value);
                if ($clean === '') {
                    $errors[] = 'app_slug cannot be empty when provided';
                    continue;
                }
                $updates[$field] = $clean;
                continue;
            }

            if ($field === 'active') {
                $active = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($active === null && !in_array((string) $value, ['0', '1'], true)) {
                    $errors[] = 'active must be true, false, 1, or 0';
                    continue;
                }
                $updates[$field] = ($active === true || (string) $value === '1') ? 1 : 0;
                continue;
            }

            if ($field === 'status') {
                $clean = strtolower($this->cleanText((string) $value));
                if (!in_array($clean, ['active', 'inactive', 'archived'], true)) {
                    $errors[] = 'status must be active, inactive, or archived';
                    continue;
                }
                $updates[$field] = $clean;
                continue;
            }

            if ($field === 'app_contactemail') {
                $clean = $this->cleanText((string) $value);
                if ($clean !== '' && !filter_var($clean, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'app_contactemail must be a valid email address';
                    continue;
                }
                $updates[$field] = $clean === '' ? null : $clean;
                continue;
            }

            $clean = $this->cleanText((string) $value);

            if (in_array($field, ['app_name', 'app_description'], true) && $clean === '') {
                $errors[] = $field . ' cannot be empty when provided';
                continue;
            }

            $nullableFields = [
                'app_domain',
                'app_website',
                'app_contactname',
                'app_contactphone',
                'app_environment',
                'app_type',
            ];

            $updates[$field] = in_array($field, $nullableFields, true) && $clean === '' ? null : $clean;
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
            $updated = $this->apps->update($appId, $updates);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23505') {
                Response::json(409, false, 'app already exists', []);
                return;
            }

            throw $exception;
        }

        if ($updated === []) {
            Response::json(404, false, 'app not found', []);
            return;
        }

        Response::json(200, true, 'app updated successfully', $updated);
    }

    public function destroy(string $appId): void
    {        $deleted = $this->apps->softDelete($appId);

        if ($deleted === []) {
            Response::json(404, false, 'app not found', []);
            return;
        }

        Response::json(200, true, 'app deleted successfully', $deleted);
    }

    private function requestInput(): array
    {
        $body = [];

        if ($_POST !== []) {
            $body = $_POST;
        } else {
            $raw = file_get_contents('php://input') ?: '';
            $trimmed = trim($raw);
            $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? $_SERVER['REDIRECT_HTTP_CONTENT_TYPE'] ?? ''));

            if ($trimmed !== '' && (str_contains($contentType, 'application/json') || str_starts_with($trimmed, '{') || str_starts_with($trimmed, '['))) {
                $decoded = json_decode($trimmed, true);
                $body = is_array($decoded) ? $decoded : [];
            } elseif ($trimmed !== '') {
                $parsed = [];
                parse_str($trimmed, $parsed);
                $body = is_array($parsed) ? $parsed : [];
            }
        }

        return array_merge($_GET, $body);
    }

    /**
     * Matches apps.app_attributes JSONB NOT NULL DEFAULT '{}'::jsonb and the
     * ck_apps_attributes_object constraint in the Venny I/O schema.
     */
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

    private function cleanText(mixed $value): string
    {
        return trim((string) $value);
    }

    private function nullableText(mixed $value): ?string
    {
        $clean = $this->cleanText($value);
        return $clean === '' ? null : $clean;
    }

    private function cleanSlug(mixed $value): string
    {
        $slug = strtolower(trim((string) $value));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug;
    }
}
