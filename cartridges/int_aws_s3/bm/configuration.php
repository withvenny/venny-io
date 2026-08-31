<?php

declare(strict_types=1);

$state = static function (string $key): ?string {
    $value = getenv($key);
    return ($value !== false && trim((string) $value) !== '') ? 'configured' : null;
};

$required = ['v_AWS_REGION', 'v_AWS_S3_BUCKET'];
$missing = [];

foreach ($required as $key) {
    if ($state($key) === null) {
        $missing[] = $key;
    }
}

return [
    'cartridge' => 'int_aws_s3',
    'configured' => $missing === [],
    'values' => [
        'v_AWS_ACCESS_KEY_ID' => $state('v_AWS_ACCESS_KEY_ID'),
        'v_AWS_SECRET_ACCESS_KEY' => $state('v_AWS_SECRET_ACCESS_KEY'),
        'v_AWS_SESSION_TOKEN' => $state('v_AWS_SESSION_TOKEN'),
        'v_AWS_REGION' => getenv('v_AWS_REGION') ?: null,
        'v_AWS_S3_BUCKET' => getenv('v_AWS_S3_BUCKET') ?: null,
        'v_AWS_S3_PREFIX' => getenv('v_AWS_S3_PREFIX') ?: null,
        'v_AWS_S3_ENDPOINT' => getenv('v_AWS_S3_ENDPOINT') ?: null,
        'v_AWS_S3_PATH_STYLE' => getenv('v_AWS_S3_PATH_STYLE') ?: 'false',
    ],
    'missing_required' => $missing,
];
