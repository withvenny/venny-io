<?php

declare(strict_types=1);

namespace Venny\Cartridges\AwsS3;

use Aws\Exception\AwsException;
use Psr\Http\Message\StreamInterface;
use Throwable;
use Venny\Cartridges\AwsS3\Exceptions\AuthenticationException;
use Venny\Cartridges\AwsS3\Exceptions\NotFoundException;
use Venny\Cartridges\AwsS3\Exceptions\ProviderException;
use Venny\Cartridges\AwsS3\Exceptions\ValidationException;

final class Provider
{
    private readonly Client $client;

    public function __construct(
        private readonly Config $config,
        ?Client $client = null
    ) {
        $this->client = $client ?? new Client($config);
    }

    public function put(string $key, mixed $body, array $options = []): ProviderResult
    {
        $logicalKey = $this->requireKey($key);
        $params = $options + [
            'Bucket' => $this->config->bucket(),
            'Key' => $this->config->key($logicalKey),
            'Body' => $body,
        ];

        return $this->execute('put', $logicalKey, fn() => $this->client->s3()->putObject($params));
    }

    public function putFile(string $key, string $filePath, array $options = []): ProviderResult
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new ValidationException('File does not exist or is not readable: ' . $filePath);
        }

        $logicalKey = $this->requireKey($key);
        $params = $options + [
            'Bucket' => $this->config->bucket(),
            'Key' => $this->config->key($logicalKey),
            'SourceFile' => $filePath,
        ];

        return $this->execute('put_file', $logicalKey, fn() => $this->client->s3()->putObject($params));
    }

    public function get(string $key, array $options = []): ProviderResult
    {
        $logicalKey = $this->requireKey($key);
        $params = $options + [
            'Bucket' => $this->config->bucket(),
            'Key' => $this->config->key($logicalKey),
        ];

        return $this->execute('get', $logicalKey, fn() => $this->client->s3()->getObject($params), includeBody: true);
    }

    public function head(string $key): ProviderResult
    {
        $logicalKey = $this->requireKey($key);

        return $this->execute('head', $logicalKey, fn() => $this->client->s3()->headObject([
            'Bucket' => $this->config->bucket(),
            'Key' => $this->config->key($logicalKey),
        ]));
    }

    public function exists(string $key): ProviderResult
    {
        $logicalKey = $this->requireKey($key);

        try {
            $exists = $this->client->s3()->doesObjectExistV2(
                $this->config->bucket(),
                $this->config->key($logicalKey)
            );

            return ProviderResult::ok(
                'exists',
                $logicalKey,
                ['key' => $logicalKey, 'exists' => $exists],
                $this->baseMetadata()
            );
        } catch (AwsException $e) {
            throw $this->mapAwsException($e);
        }
    }

    public function delete(string $key): ProviderResult
    {
        $logicalKey = $this->requireKey($key);

        return $this->execute('delete', $logicalKey, fn() => $this->client->s3()->deleteObject([
            'Bucket' => $this->config->bucket(),
            'Key' => $this->config->key($logicalKey),
        ]));
    }

    public function copy(string $sourceKey, string $destinationKey, array $options = []): ProviderResult
    {
        $source = $this->requireKey($sourceKey);
        $destination = $this->requireKey($destinationKey);

        $copySource = rawurlencode($this->config->bucket() . '/' . $this->config->key($source));
        $copySource = str_replace('%2F', '/', $copySource);

        $params = $options + [
            'Bucket' => $this->config->bucket(),
            'Key' => $this->config->key($destination),
            'CopySource' => $copySource,
        ];

        return $this->execute('copy', $destination, fn() => $this->client->s3()->copyObject($params), [
            'source_key' => $source,
        ]);
    }

    public function move(string $sourceKey, string $destinationKey, array $options = []): ProviderResult
    {
        $copy = $this->copy($sourceKey, $destinationKey, $options);
        $this->delete($sourceKey);

        return ProviderResult::ok(
            'move',
            $copy->providerId(),
            [
                'source_key' => $sourceKey,
                'destination_key' => $destinationKey,
                'copy' => $copy->data(),
            ],
            $copy->metadata()
        );
    }

    public function list(
        string $prefix = '',
        int $maxKeys = 1000,
        ?string $continuationToken = null
    ): ProviderResult {
        if ($maxKeys < 1 || $maxKeys > 1000) {
            throw new ValidationException('maxKeys must be between 1 and 1000.');
        }

        $logicalPrefix = trim($prefix, '/');
        $params = [
            'Bucket' => $this->config->bucket(),
            'Prefix' => $this->config->prefix() . $logicalPrefix,
            'MaxKeys' => $maxKeys,
        ];

        if ($continuationToken !== null && trim($continuationToken) !== '') {
            $params['ContinuationToken'] = trim($continuationToken);
        }

        return $this->execute('list', $logicalPrefix ?: null, fn() => $this->client->s3()->listObjectsV2($params));
    }

    public function createPresignedGetUrl(
        string $key,
        string $expires = '+15 minutes',
        array $options = []
    ): ProviderResult {
        $logicalKey = $this->requireKey($key);

        try {
            $command = $this->client->s3()->getCommand('GetObject', $options + [
                'Bucket' => $this->config->bucket(),
                'Key' => $this->config->key($logicalKey),
            ]);

            $request = $this->client->s3()->createPresignedRequest($command, $expires);

            return ProviderResult::ok(
                'presigned_get_url',
                $logicalKey,
                [
                    'key' => $logicalKey,
                    'url' => (string) $request->getUri(),
                    'method' => 'GET',
                    'expires' => $expires,
                ],
                $this->baseMetadata()
            );
        } catch (AwsException $e) {
            throw $this->mapAwsException($e);
        } catch (Throwable $e) {
            throw new ValidationException('Unable to create presigned GET URL: ' . $e->getMessage(), 0, $e);
        }
    }

    public function createPresignedPutUrl(
        string $key,
        string $expires = '+15 minutes',
        array $options = []
    ): ProviderResult {
        $logicalKey = $this->requireKey($key);

        try {
            $command = $this->client->s3()->getCommand('PutObject', $options + [
                'Bucket' => $this->config->bucket(),
                'Key' => $this->config->key($logicalKey),
            ]);

            $request = $this->client->s3()->createPresignedRequest($command, $expires);

            return ProviderResult::ok(
                'presigned_put_url',
                $logicalKey,
                [
                    'key' => $logicalKey,
                    'url' => (string) $request->getUri(),
                    'method' => 'PUT',
                    'expires' => $expires,
                    'headers' => $request->getHeaders(),
                ],
                $this->baseMetadata()
            );
        } catch (AwsException $e) {
            throw $this->mapAwsException($e);
        } catch (Throwable $e) {
            throw new ValidationException('Unable to create presigned PUT URL: ' . $e->getMessage(), 0, $e);
        }
    }

    public function healthCheck(): ProviderResult
    {
        return $this->execute(
            'health_check',
            null,
            fn() => $this->client->s3()->headBucket([
                'Bucket' => $this->config->bucket(),
            ]),
            [
                'configured_prefix' => $this->config->prefix(),
                'static_credentials_configured' => $this->config->hasStaticCredentials(),
                'custom_endpoint_configured' => $this->config->endpoint() !== null,
            ]
        );
    }

    private function execute(
        string $operation,
        ?string $providerId,
        callable $callback,
        array $metadata = [],
        bool $includeBody = false
    ): ProviderResult {
        try {
            $result = $callback();
            $data = $result->toArray();

            if (!$includeBody && isset($data['Body'])) {
                unset($data['Body']);
            } elseif ($includeBody && isset($data['Body']) && $data['Body'] instanceof StreamInterface) {
                $data['Body']->rewind();
                $data['body'] = $data['Body']->getContents();
                unset($data['Body']);
            }

            return ProviderResult::ok(
                $operation,
                $providerId,
                [
                    'key' => $providerId,
                    'object' => $data,
                ],
                $metadata + $this->baseMetadata()
            );
        } catch (AwsException $e) {
            throw $this->mapAwsException($e);
        } catch (ProviderException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ProviderException(
                'Unexpected AWS S3 provider failure.',
                (int) $e->getCode(),
                $e
            );
        }
    }

    private function requireKey(string $key): string
    {
        $key = ltrim(trim($key), '/');
        if ($key === '') {
            throw new ValidationException('S3 object key is required.');
        }
        return $key;
    }

    private function baseMetadata(): array
    {
        return [
            'bucket' => $this->config->bucket(),
            'region' => $this->config->region(),
        ];
    }

    private function mapAwsException(AwsException $e): ProviderException
    {
        $context = [
            'aws_error_code' => $e->getAwsErrorCode(),
            'aws_error_type' => $e->getAwsErrorType(),
            'aws_request_id' => $e->getAwsRequestId(),
            'status_code' => $e->getStatusCode(),
        ];

        $status = $e->getStatusCode();
        $code = (string) $e->getAwsErrorCode();

        if ($status === 404 || in_array($code, ['NoSuchKey', 'NotFound', 'NoSuchBucket'], true)) {
            return new NotFoundException('AWS S3 resource was not found.', (int) $e->getCode(), $e, $context);
        }

        if ($status === 401 || $status === 403 || in_array($code, ['AccessDenied', 'InvalidAccessKeyId', 'SignatureDoesNotMatch'], true)) {
            return new AuthenticationException('AWS S3 authentication or authorization failed.', (int) $e->getCode(), $e, $context);
        }

        return new ProviderException('AWS S3 request failed.', (int) $e->getCode(), $e, $context);
    }
}
