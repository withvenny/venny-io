<?php

declare(strict_types=1);

return [
    'name' => 'int_aws_s3',
    'display_name' => 'AWS S3 Storage',
    'version' => '1.0.0',
    'type' => 'integration',
    'provider' => 'AWS',
    'tool' => 'Amazon S3',
    'description' => 'Provides reusable Amazon S3 object-storage capabilities to Venny I/O.',
    'purpose' => 'Provide Venny I/O application cartridges with a reusable object-storage interface for Amazon S3 while keeping AWS authentication, bucket configuration, object-key prefixing, presigned access, provider exceptions, and S3 SDK calls isolated from application-domain code.',
    'tool_url' => 'https://aws.amazon.com/s3/',
    'documentation' => [
        'https://docs.aws.amazon.com/sdk-for-php/',
        'https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/php_s3_code_examples.html',
        'https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-presigned-url.html',
    ],
    'configuration' => [
        ['key' => 'v_AWS_ACCESS_KEY_ID', 'required' => false, 'secret' => true],
        ['key' => 'v_AWS_SECRET_ACCESS_KEY', 'required' => false, 'secret' => true],
        ['key' => 'v_AWS_SESSION_TOKEN', 'required' => false, 'secret' => true],
        ['key' => 'v_AWS_REGION', 'required' => true, 'secret' => false],
        ['key' => 'v_AWS_S3_BUCKET', 'required' => true, 'secret' => false],
        ['key' => 'v_AWS_S3_PREFIX', 'required' => false, 'secret' => false],
        ['key' => 'v_AWS_S3_ENDPOINT', 'required' => false, 'secret' => false],
        ['key' => 'v_AWS_S3_PATH_STYLE', 'required' => false, 'secret' => false],
    ],
    'capabilities' => [
        'put_object','put_file','get_object','delete_object','object_exists',
        'head_object','copy_object','move_object','presigned_get_url',
        'presigned_put_url','list_objects','health_check',
    ],
    'dependencies' => [
        'php' => '>=8.1',
        'composer' => ['aws/aws-sdk-php' => '^3.0'],
    ],
    'health_check_available' => true,
    'configuration_screen_available' => true,
    'owns_sql' => false,
];
