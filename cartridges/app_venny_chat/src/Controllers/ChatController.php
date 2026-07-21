<?php

declare(strict_types=1);

namespace VennyIO\Controllers;

use PDOException;
use VennyIO\Kernel\Request;
use VennyIO\Repositories\ChatRepository;
use VennyIO\Support\Ids;
use VennyIO\Support\Response;

final class ChatController
{
    public function __construct(
        private ChatRepository $repository,
        private array $context
    ) {
    }

    public function listThreads(Request $request): void
    {
        Response::json(200, true, 'threads retrieved successfully', [
            'items' => $this->repository->listThreads($this->appId(), $this->userId(), $request->query),
        ]);
    }

    public function showThread(string $threadId): void
    {
        if (!Ids::is('thread', $threadId)) {
            Response::json(422, false, 'validation failed', ['errors' => ['thread_id is invalid']]);
            return;
        }

        $thread = $this->repository->findThreadForParticipant($threadId, $this->appId(), $this->userId());
        if ($thread === []) {
            Response::json(404, false, 'thread not found', []);
            return;
        }

        Response::json(200, true, 'thread retrieved successfully', $thread);
    }

    public function createThread(Request $request): void
    {
        $input = $request->input();
        $errors = [];

        $threadId = $this->nullableText($input['thread_id'] ?? null) ?? Ids::generate('thread');
        if (!Ids::is('thread', $threadId)) {
            $errors[] = 'thread_id is invalid';
        }

        $subject = $this->nullableText($input['thread_subject'] ?? null);
        if ($subject === null) {
            $errors[] = 'thread_subject is required';
        }

        $participants = $this->jsonObject($input['thread_participants'] ?? [], 'thread_participants', $errors);
        $users = $participants['users'] ?? [];
        if (!is_array($users) || !array_is_list($users)) {
            $users = [];
        }
        $users[] = $this->userId();
        $participants['users'] = array_values(array_unique(array_filter(array_map('strval', $users))));

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        try {
            $thread = $this->repository->createThread([
                'thread_id' => $threadId,
                'thread_attributes' => $this->jsonObject($input['thread_attributes'] ?? [], 'thread_attributes', $errors),
                'thread_subject' => $subject,
                'thread_participants' => $participants,
                'thread_lastmessagepreview' => $this->nullableText($input['thread_lastmessagepreview'] ?? null) ?? '',
                'thread_lastmessageat' => $this->nullableText($input['thread_lastmessageat'] ?? null),
                'thread_author_id' => $this->userId(),
                'created_by_user_id' => $this->userId(),
                'created_for_app_id' => $this->appId(),
                'event_id' => $this->nullableText($input['event_id'] ?? null) ?? 'event_8301',
                'process_id' => $this->nullableText($input['process_id'] ?? null) ?? 'process_8301',
                'access' => strtolower($this->nullableText($input['access'] ?? null) ?? 'private'),
                'status' => 'active',
                'active' => 1,
            ]);
        } catch (PDOException $exception) {
            $this->handlePdoException($exception, 'thread');
            return;
        }

        Response::json(201, true, 'thread added successfully', $thread);
    }

    public function updateThread(string $threadId, Request $request): void
    {
        if (!Ids::is('thread', $threadId)) {
            Response::json(422, false, 'validation failed', ['errors' => ['thread_id is invalid']]);
            return;
        }

        $input = $request->input();
        $updates = [];
        $errors = [];

        if (array_key_exists('thread_subject', $input)) {
            $subject = $this->nullableText($input['thread_subject']);
            if ($subject === null) {
                $errors[] = 'thread_subject cannot be empty';
            } else {
                $updates['thread_subject'] = $subject;
            }
        }

        if (array_key_exists('thread_attributes', $input)) {
            $updates['thread_attributes'] = $this->jsonObject($input['thread_attributes'], 'thread_attributes', $errors);
        }

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        if ($updates === []) {
            Response::json(422, false, 'no valid update fields provided', []);
            return;
        }

        $thread = $this->repository->updateThreadForParticipant($threadId, $this->appId(), $this->userId(), $updates);
        if ($thread === []) {
            Response::json(404, false, 'thread not found', []);
            return;
        }

        Response::json(200, true, 'thread updated successfully', $thread);
    }

    public function destroyThread(string $threadId): void
    {
        if (!Ids::is('thread', $threadId)) {
            Response::json(422, false, 'validation failed', ['errors' => ['thread_id is invalid']]);
            return;
        }

        $thread = $this->repository->archiveThreadForParticipant($threadId, $this->appId(), $this->userId());
        if ($thread === []) {
            Response::json(404, false, 'thread not found', []);
            return;
        }

        Response::json(200, true, 'thread archived successfully', $thread);
    }

    public function listMessages(Request $request): void
    {
        $threadId = $this->nullableText($request->query['thread_id'] ?? null);
        if ($threadId === null || !Ids::is('thread', $threadId)) {
            Response::json(422, false, 'validation failed', ['errors' => ['thread_id is required']]);
            return;
        }

        $this->listMessagesForThread($threadId, $request);
    }

    public function listMessagesForThread(string $threadId, Request $request): void
    {
        if (!Ids::is('thread', $threadId)) {
            Response::json(422, false, 'validation failed', ['errors' => ['thread_id is invalid']]);
            return;
        }

        Response::json(200, true, 'messages retrieved successfully', [
            'items' => $this->repository->listMessagesForThread($threadId, $this->appId(), $this->userId(), $request->query),
        ]);
    }

    public function showMessage(string $messageId): void
    {
        if (!Ids::is('message', $messageId)) {
            Response::json(422, false, 'validation failed', ['errors' => ['message_id is invalid']]);
            return;
        }

        $message = $this->repository->findMessageForParticipant($messageId, $this->appId(), $this->userId());
        if ($message === []) {
            Response::json(404, false, 'message not found', []);
            return;
        }

        Response::json(200, true, 'message retrieved successfully', $message);
    }

    public function createMessage(Request $request): void
    {
        $input = $request->input();
        $threadId = $this->nullableText($input['thread_id'] ?? null);
        if ($threadId === null || !Ids::is('thread', $threadId)) {
            Response::json(422, false, 'validation failed', ['errors' => ['thread_id is required']]);
            return;
        }

        $this->createMessageInThread($threadId, $request);
    }

    public function createMessageInThread(string $threadId, Request $request): void
    {
        $input = $request->input();
        $errors = [];

        if (!Ids::is('thread', $threadId)) {
            $errors[] = 'thread_id is invalid';
        }

        $thread = $this->repository->findThreadForParticipant($threadId, $this->appId(), $this->userId());
        if ($thread === []) {
            Response::json(404, false, 'thread not found', []);
            return;
        }

        $messageId = $this->nullableText($input['message_id'] ?? null) ?? Ids::generate('message');
        if (!Ids::is('message', $messageId)) {
            $errors[] = 'message_id is invalid';
        }

        $body = $this->nullableText($input['message_body'] ?? $input['body'] ?? null);
        if ($body === null) {
            $errors[] = 'message_body is required';
        }

        $attachments = $this->jsonObject($input['message_attachments'] ?? [], 'message_attachments', $errors);
        $attributes = $this->jsonObject($input['message_attributes'] ?? [], 'message_attributes', $errors);
        $readBy = $this->jsonObject($input['message_readby'] ?? [], 'message_readby', $errors);
        $readBy[$this->userId()] = gmdate('c');

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        try {
            $message = $this->repository->createMessage([
                'message_id' => $messageId,
                'message_attributes' => $attributes,
                'thread_id' => $threadId,
                'message_sender_id' => $this->userId(),
                'message_body' => $body,
                'message_attachments' => $attachments,
                'message_readby' => $readBy,
                'created_by_user_id' => $this->userId(),
                'created_for_app_id' => $this->appId(),
                'event_id' => $this->nullableText($input['event_id'] ?? null) ?? 'event_8301',
                'process_id' => $this->nullableText($input['process_id'] ?? null) ?? 'process_8301',
                'access' => strtolower($this->nullableText($input['access'] ?? null) ?? 'private'),
                'status' => 'active',
                'active' => 1,
            ]);
        } catch (PDOException $exception) {
            $this->handlePdoException($exception, 'message');
            return;
        }

        Response::json(201, true, 'message added successfully', $message);
    }

    public function updateMessage(string $messageId, Request $request): void
    {
        if (!Ids::is('message', $messageId)) {
            Response::json(422, false, 'validation failed', ['errors' => ['message_id is invalid']]);
            return;
        }

        $input = $request->input();
        $updates = [];
        $errors = [];

        if (array_key_exists('message_body', $input)) {
            $body = $this->nullableText($input['message_body']);
            if ($body === null) {
                $errors[] = 'message_body cannot be empty';
            } else {
                $updates['message_body'] = $body;
            }
        }

        if (array_key_exists('message_attributes', $input)) {
            $updates['message_attributes'] = $this->jsonObject($input['message_attributes'], 'message_attributes', $errors);
        }

        if (array_key_exists('message_attachments', $input)) {
            $updates['message_attachments'] = $this->jsonObject($input['message_attachments'], 'message_attachments', $errors);
        }

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        if ($updates === []) {
            Response::json(422, false, 'no valid update fields provided', []);
            return;
        }

        $message = $this->repository->updateOwnMessage($messageId, $this->appId(), $this->userId(), $updates);
        if ($message === []) {
            Response::json(404, false, 'message not found or not owned by current user', []);
            return;
        }

        Response::json(200, true, 'message updated successfully', $message);
    }

    public function destroyMessage(string $messageId): void
    {
        if (!Ids::is('message', $messageId)) {
            Response::json(422, false, 'validation failed', ['errors' => ['message_id is invalid']]);
            return;
        }

        $message = $this->repository->archiveOwnMessage($messageId, $this->appId(), $this->userId());
        if ($message === []) {
            Response::json(404, false, 'message not found or not owned by current user', []);
            return;
        }

        Response::json(200, true, 'message archived successfully', $message);
    }

    public function markMessageRead(string $messageId): void
    {
        if (!Ids::is('message', $messageId)) {
            Response::json(422, false, 'validation failed', ['errors' => ['message_id is invalid']]);
            return;
        }

        $message = $this->repository->markMessageRead($messageId, $this->appId(), $this->userId());
        if ($message === []) {
            Response::json(404, false, 'message not found', []);
            return;
        }

        Response::json(200, true, 'message marked read successfully', $message);
    }

    public function findOrCreateDirectThread(Request $request): void
    {
        $input = $request->input();
        $errors = [];

        $recipientUserId = $this->nullableText($input['recipient_user_id'] ?? $input['user_id'] ?? null);
        if ($recipientUserId === null || !Ids::is('user', $recipientUserId)) {
            $errors[] = 'recipient_user_id is required';
        }

        if ($recipientUserId === $this->userId()) {
            $errors[] = 'recipient_user_id cannot be the current user';
        }

        if ($errors !== []) {
            Response::json(422, false, 'validation failed', ['errors' => $errors]);
            return;
        }

        if (!$this->repository->canStartDirectThread($this->appId(), $this->userId(), (string) $recipientUserId)) {
            Response::json(403, false, 'direct thread requires an accepted followship or a public open profile', []);
            return;
        }

        try {
            $thread = $this->repository->transaction(function () use ($input, $recipientUserId): array {
                $existing = $this->repository->findExistingDirectThread($this->appId(), $this->userId(), (string) $recipientUserId);
                if ($existing !== []) {
                    return $existing;
                }

                $attributeErrors = [];
                $attributes = $this->jsonObject($input['thread_attributes'] ?? [], 'thread_attributes', $attributeErrors);
                $attributes['source'] = $attributes['source'] ?? 'chatio-reference-app';
                $attributes['cartridge'] = 'app_venny_chat';
                $attributes['kind'] = 'direct_message';

                return $this->repository->createThread([
                    'thread_id' => Ids::generate('thread'),
                    'thread_attributes' => $attributes,
                    'thread_subject' => $this->nullableText($input['thread_subject'] ?? null) ?? 'Direct message',
                    'thread_participants' => [
                        'users' => [$this->userId(), (string) $recipientUserId],
                    ],
                    'thread_lastmessagepreview' => '',
                    'thread_lastmessageat' => null,
                    'thread_author_id' => $this->userId(),
                    'created_by_user_id' => $this->userId(),
                    'created_for_app_id' => $this->appId(),
                    'event_id' => $this->nullableText($input['event_id'] ?? null) ?? 'event_8301',
                    'process_id' => $this->nullableText($input['process_id'] ?? null) ?? 'process_8301',
                    'access' => 'private',
                    'status' => 'active',
                    'active' => 1,
                ]);
            });
        } catch (PDOException $exception) {
            $this->handlePdoException($exception, 'thread');
            return;
        }

        Response::json(200, true, 'direct thread ready', $thread);
    }

    private function appId(): string
    {
        return (string) $this->context['app_id'];
    }

    private function userId(): string
    {
        return (string) $this->context['user_id'];
    }

    private function nullableText(mixed $value): ?string
    {
        $clean = trim((string) ($value ?? ''));
        return $clean === '' ? null : $clean;
    }

    private function jsonObject(mixed $value, string $field, array &$errors): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (!is_array($value) || array_is_list($value)) {
            $errors[] = $field . ' must be a JSON object';
            return [];
        }

        return $value;
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
}
