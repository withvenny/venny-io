<?php

declare(strict_types=1);

$secretState = static function (string $name): ?string {
    $value = getenv($name);

    return $value !== false && trim((string) $value) !== ''
        ? 'configured'
        : null;
};

$secretKey = getenv('v_STRIPE_SECRET_KEY');
$mode = null;

if ($secretKey !== false) {
    if (str_starts_with((string) $secretKey, 'sk_test_') || str_starts_with((string) $secretKey, 'rk_test_')) {
        $mode = 'test';
    } elseif (str_starts_with((string) $secretKey, 'sk_live_') || str_starts_with((string) $secretKey, 'rk_live_')) {
        $mode = 'live';
    }
}

$missing = [];

if ($secretKey === false || trim((string) $secretKey) === '') {
    $missing[] = 'v_STRIPE_SECRET_KEY';
}

return [
    'cartridge' => 'int_stripe_payments',
    'configured' => $missing === [],
    'mode' => $mode,
    'values' => [
        'v_STRIPE_SECRET_KEY' => $secretState('v_STRIPE_SECRET_KEY'),
        'v_STRIPE_PUBLISHABLE_KEY' => $secretState('v_STRIPE_PUBLISHABLE_KEY'),
        'v_STRIPE_WEBHOOK_SECRET' => $secretState('v_STRIPE_WEBHOOK_SECRET'),
        'v_STRIPE_API_VERSION' => getenv('v_STRIPE_API_VERSION') ?: null,
    ],
    'missing_required' => $missing,
];
