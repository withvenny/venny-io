<?php

declare(strict_types=1);

$state = static function (string $key): ?string {
    $value = getenv($key);
    return ($value !== false && trim((string) $value) !== '') ? 'configured' : null;
};

$missing = [];

foreach (['v_TWILIO_ACCOUNT_SID', 'v_TWILIO_AUTH_TOKEN'] as $key) {
    if ($state($key) === null) {
        $missing[] = $key;
    }
}

if (
    $state('v_TWILIO_MESSAGING_SERVICE_SID') === null &&
    $state('v_TWILIO_FROM_NUMBER') === null
) {
    $missing[] = 'v_TWILIO_MESSAGING_SERVICE_SID or v_TWILIO_FROM_NUMBER';
}

return [
    'cartridge' => 'int_twilio_sms',
    'configured' => $missing === [],
    'values' => [
        'v_TWILIO_ACCOUNT_SID' => getenv('v_TWILIO_ACCOUNT_SID') ?: null,
        'v_TWILIO_AUTH_TOKEN' => $state('v_TWILIO_AUTH_TOKEN'),
        'v_TWILIO_MESSAGING_SERVICE_SID' => getenv('v_TWILIO_MESSAGING_SERVICE_SID') ?: null,
        'v_TWILIO_FROM_NUMBER' => getenv('v_TWILIO_FROM_NUMBER') ?: null,
        'v_TWILIO_STATUS_CALLBACK_URL' => getenv('v_TWILIO_STATUS_CALLBACK_URL') ?: null,
    ],
    'missing_required' => $missing,
];
