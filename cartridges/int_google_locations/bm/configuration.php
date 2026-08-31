<?php

declare(strict_types=1);

$state = static function (string $key): ?string {
    $value = getenv($key);
    return ($value !== false && trim((string) $value) !== '') ? 'configured' : null;
};

$missing = [];

if ($state('v_GOOGLE_MAPS_SERVER_KEY') === null) {
    $missing[] = 'v_GOOGLE_MAPS_SERVER_KEY';
}

return [
    'cartridge' => 'int_google_locations',
    'configured' => $missing === [],
    'values' => [
        'v_GOOGLE_MAPS_SERVER_KEY' => $state('v_GOOGLE_MAPS_SERVER_KEY'),
        'v_GOOGLE_GEOCODING_LANGUAGE' => getenv('v_GOOGLE_GEOCODING_LANGUAGE') ?: null,
        'v_GOOGLE_GEOCODING_REGION' => getenv('v_GOOGLE_GEOCODING_REGION') ?: null,
    ],
    'missing_required' => $missing,
];
