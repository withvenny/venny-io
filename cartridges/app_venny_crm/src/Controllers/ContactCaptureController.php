<?php

declare(strict_types=1);

namespace VennyIO\Controllers;

use PDOException;
use VennyIO\Kernel\Request;
use VennyIO\Repositories\ContactCaptureRepository;
use VennyIO\Support\Response;

final class ContactCaptureController
{
    public function __construct(
        private ContactCaptureRepository $repository,
        private array $appContext
    ) {
    }

    public function signUpForUpdates(Request $request): void
    {
        $input = $request->input();
        $errors = [];

        $email = strtolower(trim((string) ($input['email'] ?? $input['contact_email'] ?? $input['user_email'] ?? '')));
        if ($email === '') {
            $errors[] = 'email is required';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'email must be a valid email address';
        }

        $attributes = $this->jsonObject($input['contact_attributes'] ?? $input['attributes'] ?? [], 'contact_attributes', $errors);
        $source = $this->jsonObject($input['contact_source'] ?? $input['source'] ?? [], 'contact_source', $errors);
        $phones = $this->jsonObject($input['contact_phones'] ?? $input['phones'] ?? [], 'contact_phones', $errors);

        $view = trim((string) ($input['view'] ?? $input['source_view'] ?? $input['route'] ?? ($source['view'] ?? '')));
        if ($view === '') {
            $errors[] = 'source view is required. Provide view, source_view, route, or contact_source.view';
        }

        $phone = trim((string) ($input['phone'] ?? $input['contact_phone'] ?? ''));
        if ($phone !== '') {
            $phones['primary'] = $phone;
        }

        if ($view !== '') {
            $source['view'] = $view;
            $attributes['source_view'] = $view;
        }

        $source['capture_type'] = 'sign_up_for_updates';
        $source['captured_at'] = gmdate('c');
        $attributes['capture_type'] = 'sign_up_for_updates';

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        try {
            $contact = $this->repository->createUpdateSignup([
                'contact_attributes' => json_encode($attributes, JSON_UNESCAPED_SLASHES) ?: '{}',
                'contact_firstname' => $this->text($input['first_name'] ?? $input['contact_firstname'] ?? null),
                'contact_middlename' => $this->text($input['middle_name'] ?? $input['contact_middlename'] ?? null),
                'contact_lastname' => $this->text($input['last_name'] ?? $input['contact_lastname'] ?? null),
                'contact_emails' => json_encode(['primary' => $email], JSON_UNESCAPED_SLASHES) ?: '{}',
                'contact_phones' => json_encode($phones, JSON_UNESCAPED_SLASHES) ?: '{}',
                'contact_source' => json_encode($source, JSON_UNESCAPED_SLASHES) ?: '{}',
                'contact_title' => $this->text($input['contact_title'] ?? null),
                'created_by_user_id' => $this->text($input['created_by_user_id'] ?? null) ?? 'user_8301',
                'created_for_app_id' => $this->appId(),
                'event_id' => $this->text($input['event_id'] ?? null) ?? 'event_8301',
                'process_id' => $this->text($input['process_id'] ?? null) ?? 'process_8301',
                'access' => 'private',
                'status' => 'active',
                'active' => 1,
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23503') {
                Response::json(409, false, 'foreign key reference does not exist', []);
                return;
            }

            throw $exception;
        }

        Response::json(201, true, 'updates signup captured successfully', ['contact' => $contact]);
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

        // PHP decodes an empty JSON object (`{}`) as an empty array. Missing
        // optional object fields are also passed here as an empty array by the
        // controller defaults. Both cases are valid for these optional CRM
        // capture fields and should be treated as an empty JSON object.
        if (is_array($value) && $value === []) {
            return [];
        }

        if (!is_array($value) || array_is_list($value)) {
            $errors[] = $field . ' must be a JSON object';
            return [];
        }

        return $value;
    }

    private function text(mixed $value): ?string
    {
        $clean = trim((string) ($value ?? ''));
        return $clean === '' ? null : $clean;
    }

    private function appId(): string
    {
        return (string) ($this->appContext['app_id'] ?? $this->appContext['created_for_app_id'] ?? 'app_8301');
    }
}
