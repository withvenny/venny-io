<?php

declare(strict_types=1);

use Venny\Cartridges\SendGridEmail\Config;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

$config = new Config(
    apiKey: 'SG.example.example',
    fromEmail: 'notifications@example.com',
    fromName: 'Example Notifications'
);

assert($config->fromEmail() === 'notifications@example.com');
assert($config->hasWebhookPublicKey() === false);

echo "int_sendgrid_email local configuration smoke tests passed.\n";
