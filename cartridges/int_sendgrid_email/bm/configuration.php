<?php

declare(strict_types=1);

$state = static function (string $key): ?string {
    $value = getenv($key);
    return ($value !== false && trim((string) $value) !== '') ? 'configured' : null;
};

$missing = [];

foreach (['v_SENDGRID_API_KEY', 'v_SENDGRID_FROM_EMAIL'] as $key) {
    if ($state($key) === null) {
        $missing[] = $key;
    }
}

return [
    'cartridge' => 'int_sendgrid_email',
    'configured' => $missing === [],
    'values' => [
        'v_SENDGRID_API_KEY' => $state('v_SENDGRID_API_KEY'),
        'v_SENDGRID_FROM_EMAIL' => getenv('v_SENDGRID_FROM_EMAIL') ?: null,
        'v_SENDGRID_FROM_NAME' => getenv('v_SENDGRID_FROM_NAME') ?: null,
        'v_SENDGRID_REPLY_TO_EMAIL' => getenv('v_SENDGRID_REPLY_TO_EMAIL') ?: null,
        'v_SENDGRID_REPLY_TO_NAME' => getenv('v_SENDGRID_REPLY_TO_NAME') ?: null,
        'v_SENDGRID_WEBHOOK_PUBLIC_KEY' => $state('v_SENDGRID_WEBHOOK_PUBLIC_KEY'),
    ],
    'missing_required' => $missing,
];
