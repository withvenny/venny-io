<?php

declare(strict_types=1);

use Venny\Cartridges\ImgixImages\Config;
use Venny\Cartridges\ImgixImages\Provider;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

$config = new Config('demo.imgix.net');
$provider = new Provider($config);

$url = $provider->buildUrl('bridge.png', ['w' => 100, 'h' => 100]);

assert($url->success() === true);
assert(str_contains($url->data()['url'], 'demo.imgix.net/bridge.png'));
assert(str_contains($url->data()['url'], 'w=100'));
assert(str_contains($url->data()['url'], 'h=100'));

$thumbnail = $provider->thumbnail('bridge.png', 200, 150);
assert(str_contains($thumbnail->data()['url'], 'fit=crop'));

$health = $provider->healthCheck();
assert($health->success() === true);

echo "int_imgix_images smoke tests passed.\n";
