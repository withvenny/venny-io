<?php

declare(strict_types=1);

$mask = static function (string|false $value): ?string {
    if ($value === false || trim($value) === '') {
        return null;
    }

    return 'configured';
};

return [
    'cartridge' => 'int_imgix_images',
    'configured' => getenv('v_IMGIX_DOMAIN') !== false && trim((string) getenv('v_IMGIX_DOMAIN')) !== '',
    'values' => [
        'v_IMGIX_DOMAIN' => getenv('v_IMGIX_DOMAIN') ?: null,
        'v_IMGIX_SECURE_URL_TOKEN' => $mask(getenv('v_IMGIX_SECURE_URL_TOKEN')),
        'v_IMGIX_USE_HTTPS' => getenv('v_IMGIX_USE_HTTPS') ?: 'true',
        'v_IMGIX_INCLUDE_LIBRARY_PARAM' => getenv('v_IMGIX_INCLUDE_LIBRARY_PARAM') ?: 'true',
    ],
    'missing_required' => (
        getenv('v_IMGIX_DOMAIN') === false ||
        trim((string) getenv('v_IMGIX_DOMAIN')) === ''
    ) ? ['v_IMGIX_DOMAIN'] : [],
];
