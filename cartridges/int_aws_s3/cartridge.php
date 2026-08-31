<?php

declare(strict_types=1);

return [
    'manifest_version' => '2.0.0',
    'name' => 'int_aws_s3',
    'type' => 'integration',
    'provider' => 'AWS',
    'domain' => 'aws_s3',
    'version' => '2.0.0',
    'description' => 'Venny I/O Amazon S3 integration cartridge for object storage, retrieval, metadata, copy/move, deletion, existence checks, and presigned GET/PUT URLs.',
    'tool' => 'Amazon S3',
    'tool_url' => 'https://aws.amazon.com/s3/',
    'php' => '>=8.2',
    'requires' => [
        'app_venny_platform',
    ],
    'dependencies' => [
        'php_extensions' => [],
        'composer' => [
            'aws/aws-sdk-php' => '^3.0',
        ],
        'npm' => [],
    ],
    'configuration' => [
        'v_AWS_ACCESS_KEY_ID',
        'v_AWS_SECRET_ACCESS_KEY',
        'v_AWS_SESSION_TOKEN',
        'v_AWS_REGION',
        'v_AWS_S3_BUCKET',
        'v_AWS_S3_PREFIX',
        'v_AWS_S3_ENDPOINT',
        'v_AWS_S3_PATH_STYLE',
    ],
    'capabilities' => [
        'put_object',
        'put_file',
        'get_object',
        'delete_object',
        'object_exists',
        'head_object',
        'copy_object',
        'move_object',
        'presigned_get_url',
        'presigned_put_url',
        'list_objects',
        'health_check',
    ],
    'documentation' => [
        'https://docs.aws.amazon.com/sdk-for-php/',
        'https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/php_s3_code_examples.html',
        'https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-presigned-url.html',
    ],
    'routes' => null,
    'sql' => [
        'schema' => null,
        'constraints' => null,
        'indexes' => null,
    ],
    'business_manager' => [
        'metadata' => __DIR__ . '/bm/metadata.php',
        'configuration' => __DIR__ . '/bm/configuration.php',
        'health' => __DIR__ . '/bm/health.php',
    ],
    'autoload' => [
        'Venny\\Cartridges\\AwsS3\\' => __DIR__ . '/src',
    ],
];
