<?php

declare(strict_types=1);

use Venny\Cartridges\StripePayments\Config;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

$config = new Config(
    secretKey: 'sk_test_example_only_not_a_real_key',
    publishableKey: 'pk_test_example_only_not_a_real_key',
    webhookSecret: 'whsec_example_only_not_a_real_secret'
);

assert($config->mode() === 'test');
assert($config->hasWebhookSecret() === true);
assert($config->apiVersion() === null);

echo "int_stripe_payments local configuration smoke tests passed.\n";
