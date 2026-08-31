<?php

declare(strict_types=1);

use Venny\Cartridges\GoogleLocations\Config;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

$config = new Config(
    serverKey: 'AIzaExampleOnlyNotARealGoogleApiKey',
    language: 'en',
    region: 'us'
);

assert($config->language() === 'en');
assert($config->region() === 'us');

echo "int_google_locations local configuration smoke tests passed.\n";
