<?php

declare(strict_types=1);

namespace Venny\Cartridges\AnthropicClaude;

use Throwable;
use Venny\Cartridges\AnthropicClaude\Exceptions\AuthenticationException;
use Venny\Cartridges\AnthropicClaude\Exceptions\ProviderException;
use Venny\Cartridges\AnthropicClaude\Exceptions\RateLimitException;
use Venny\Cartridges\AnthropicClaude\Exceptions\ValidationException;

final class Provider
{
    private readonly Client $client;

    public function __construct(
        private readonly Config $config,
        ?Client $client = null
    ) {
        $this->client = $client ?? new Client($config);
    }

    public function createMessage(array $request): ProviderResult
    {
        $params = $this->messageParams($request);

        try {
            $message = $this->client->anthropic()->messages->create(...$params);

            $data = $this->normalize($message);
            $id = is_array($data) ? ($data['id'] ?? null) : null;

            return ProviderResult::ok(
                'messages_create',
                is_string($id) ? $id : null,
                ['message' => $data]
            );
        } catch (Throwable $e) {
            throw $this->mapException($e);
        }
    }

    public function streamMessage(array $request): iterable
    {
        $params = $this->messageParams($request);

        try {
            $stream = $this->client->anthropic()->messages->createStream(...$params);
            foreach ($stream as $event) {
                yield $this->normalize($event);
            }
        } catch (Throwable $e) {
            throw $this->mapException($e);
        }
    }

    public function countTokens(array $request): ProviderResult
    {
        if (!isset($request['messages']) || !is_array($request['messages'])) {
            throw new ValidationException('messages is required and must be an array.');
        }

        $params = [
            'messages' => $request['messages'],
            'model' => $request['model'] ?? $this->requireDefaultModel(),
        ];

        $optionalMap = [
            'cache_control' => 'cacheControl',
            'output_config' => 'outputConfig',
            'system' => 'system',
            'thinking' => 'thinking',
            'tool_choice' => 'toolChoice',
            'tools' => 'tools',
        ];

        foreach ($optionalMap as $input => $sdkName) {
            if (array_key_exists($input, $request)) {
                $params[$sdkName] = $request[$input];
            }
        }

        try {
            $result = $this->client->anthropic()->messages->countTokens(...$params);
            return ProviderResult::ok(
                'messages_count_tokens',
                null,
                ['count' => $this->normalize($result)]
            );
        } catch (Throwable $e) {
            throw $this->mapException($e);
        }
    }

    public function uploadFile(mixed $file): ProviderResult
    {
        try {
            $resource = $this->client->anthropic()->files->upload(file: $file);
            $data = $this->normalize($resource);
            return ProviderResult::ok(
                'files_upload',
                is_array($data) && isset($data['id']) ? (string) $data['id'] : null,
                ['file' => $data]
            );
        } catch (Throwable $e) {
            throw $this->mapException($e);
        }
    }

    public function retrieveFile(string $fileId): ProviderResult
    {
        $fileId = trim($fileId);
        if ($fileId === '') {
            throw new ValidationException('fileId is required.');
        }

        try {
            $resource = $this->client->anthropic()->files->retrieve($fileId);
            $data = $this->normalize($resource);
            return ProviderResult::ok('files_retrieve', $fileId, ['file' => $data]);
        } catch (Throwable $e) {
            throw $this->mapException($e);
        }
    }

    public function deleteFile(string $fileId): ProviderResult
    {
        $fileId = trim($fileId);
        if ($fileId === '') {
            throw new ValidationException('fileId is required.');
        }

        try {
            $resource = $this->client->anthropic()->files->delete($fileId);
            return ProviderResult::ok(
                'files_delete',
                $fileId,
                ['result' => $this->normalize($resource)]
            );
        } catch (Throwable $e) {
            throw $this->mapException($e);
        }
    }

    public function healthCheck(): ProviderResult
    {
        return ProviderResult::ok(
            'health_check',
            null,
            [
                'configured' => true,
                'client_constructed' => true,
                'remote_test_performed' => false,
                'default_model' => $this->config->defaultModel(),
                'default_max_tokens' => $this->config->maxTokens(),
            ],
            [
                'billing_note' => 'No Claude API generation was performed.',
            ]
        );
    }

    private function messageParams(array $request): array
    {
        if (!isset($request['messages']) || !is_array($request['messages'])) {
            throw new ValidationException('messages is required and must be an array.');
        }

        $params = [
            'maxTokens' => isset($request['max_tokens'])
                ? (int) $request['max_tokens']
                : $this->config->maxTokens(),
            'messages' => $request['messages'],
            'model' => $request['model'] ?? $this->requireDefaultModel(),
        ];

        if ($params['maxTokens'] < 1) {
            throw new ValidationException('max_tokens must be at least 1.');
        }

        $optionalMap = [
            'cache_control' => 'cacheControl',
            'container' => 'container',
            'inference_geo' => 'inferenceGeo',
            'metadata' => 'metadata',
            'output_config' => 'outputConfig',
            'service_tier' => 'serviceTier',
            'stop_sequences' => 'stopSequences',
            'system' => 'system',
            'thinking' => 'thinking',
            'tool_choice' => 'toolChoice',
            'tools' => 'tools',
            'top_k' => 'topK',
            'top_p' => 'topP',
            'user_profile_id' => 'userProfileID',
        ];

        foreach ($optionalMap as $input => $sdkName) {
            if (array_key_exists($input, $request)) {
                $params[$sdkName] = $request[$input];
            }
        }

        return $params;
    }

    private function requireDefaultModel(): string
    {
        $model = $this->config->defaultModel();
        if ($model === null || $model === '') {
            throw new ValidationException(
                'model is required when v_ANTHROPIC_DEFAULT_MODEL is not configured.'
            );
        }
        return $model;
    }

    private function normalize(mixed $value): mixed
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(fn($item) => $this->normalize($item), $value);
        }

        if ($value instanceof \JsonSerializable) {
            return $this->normalize($value->jsonSerialize());
        }

        $json = json_encode($value);
        if ($json !== false) {
            $decoded = json_decode($json, true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        return get_object_vars($value);
    }

    private function mapException(Throwable $e): ProviderException
    {
        $class = $e::class;
        $message = $e->getMessage();
        $code = (int) $e->getCode();

        $context = ['provider_exception' => $class];

        if (
            str_contains($class, 'Authentication') ||
            str_contains($class, 'Permission') ||
            $code === 401 ||
            $code === 403
        ) {
            return new AuthenticationException(
                'Anthropic authentication or authorization failed.',
                $code,
                $e,
                $context
            );
        }

        if (
            str_contains($class, 'RateLimit') ||
            $code === 429
        ) {
            return new RateLimitException(
                'Anthropic rate limit exceeded.',
                $code,
                $e,
                $context
            );
        }

        if (
            str_contains($class, 'BadRequest') ||
            str_contains($class, 'Unprocessable') ||
            $code === 400 ||
            $code === 422
        ) {
            return new ValidationException(
                $message !== '' ? $message : 'Anthropic rejected the request.',
                $code,
                $e,
                $context
            );
        }

        return new ProviderException(
            $message !== '' ? $message : 'Anthropic request failed.',
            $code,
            $e,
            $context
        );
    }
}
