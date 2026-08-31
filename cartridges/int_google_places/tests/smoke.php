<?php

declare(strict_types=1);

use Venny\Cartridges\GooglePlaces\Config;
use Venny\Cartridges\GooglePlaces\Provider;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

$config = new Config(
    serverKey: 'AIzaExampleOnlyNotARealGoogleApiKey',
    language: 'en',
    regionCode: 'US'
);

$provider = new Provider($config);

assert($config->language() === 'en');
assert($config->regionCode() === 'US');
assert(strlen($provider->createSessionToken()) >= 20);

echo "int_google_places local configuration smoke tests passed.\n";
