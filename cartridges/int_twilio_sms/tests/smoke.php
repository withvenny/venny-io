<?php

declare(strict_types=1);

use Venny\Cartridges\TwilioSms\Config;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

$config = new Config(
    accountSid: 'AC00000000000000000000000000000000',
    authToken: 'example-only-token',
    messagingServiceSid: 'MG00000000000000000000000000000000'
);

assert($config->hasMessagingService() === true);
assert($config->fromNumber() === null);

echo "int_twilio_sms local configuration smoke tests passed.\n";
