<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/config/bootstrap.php';

use VennyIO\Kernel\CartridgeLoader;
use VennyIO\Kernel\CartridgeRegistry;
use VennyIO\Kernel\Router;

$errors = [];
$warnings = [];

if (is_file($root . '/config/cartridges.php')) {
    $errors[] = 'config/cartridges.php still exists.';
}

$jsonManifests = glob($root . '/cartridges/*/cartridge.json') ?: [];
if ($jsonManifests !== []) {
    $errors[] = 'Legacy cartridge.json manifests remain: ' . implode(', ', $jsonManifests);
}

try {
    $registry = new CartridgeRegistry($root);
    $all = $registry->all();
    $ordered = $registry->ordered();

    if (count($all) !== count($ordered)) {
        $errors[] = 'Discovered and dependency-ordered cartridge counts do not match.';
    }

    $keySets = [];
    foreach (glob($root . '/cartridges/*/cartridge.php') ?: [] as $manifestPath) {
        $manifest = require $manifestPath;
        if (!is_array($manifest)) {
            $errors[] = 'Manifest did not return an array: ' . $manifestPath;
            continue;
        }
        $keys = array_keys($manifest);
        sort($keys, SORT_STRING);
        $keySets[$manifestPath] = $keys;
    }

    $reference = null;
    $referencePath = null;
    foreach ($keySets as $path => $keys) {
        if ($reference === null) {
            $reference = $keys;
            $referencePath = $path;
            continue;
        }
        if ($keys !== $reference) {
            $errors[] = 'Manifest key mismatch: ' . $path . ' differs from ' . $referencePath . '.';
        }
    }

    $router = new Router();
    $loaded = (new CartridgeLoader($root))->load($router);
    if (count($loaded) !== count($all)) {
        $errors[] = 'Runtime loader did not load every discovered cartridge.';
    }

    $types = ['app' => 0, 'integration' => 0, 'bm' => 0];
    foreach ($all as $manifest) {
        $type = (string) ($manifest['type'] ?? '');
        if (isset($types[$type])) {
            $types[$type]++;
        }

        foreach (($manifest['dependencies']['php_extensions'] ?? []) as $extension) {
            if (!is_string($extension) || !str_starts_with($extension, 'ext-')) {
                continue;
            }
            $extensionName = substr($extension, 4);
            if ($extensionName !== '' && !extension_loaded($extensionName)) {
                $warnings[] = (string) $manifest['name'] . ' declares unavailable PHP extension ' . $extension . '.';
            }
        }
    }

    echo 'Venny I/O Cartridge Runtime 2.0 validation' . PHP_EOL;
    echo 'Discovered: ' . count($all) . PHP_EOL;
    echo 'Application: ' . $types['app'] . PHP_EOL;
    echo 'Integration: ' . $types['integration'] . PHP_EOL;
    echo 'Business Manager: ' . $types['bm'] . PHP_EOL;
    echo 'Dependency errors: ' . count($registry->dependencyErrors()) . PHP_EOL;
    echo 'Runtime-loaded: ' . count($loaded) . PHP_EOL;
} catch (Throwable $throwable) {
    $errors[] = $throwable->getMessage();
}

foreach (array_values(array_unique($warnings)) as $warning) {
    fwrite(STDERR, 'WARNING: ' . $warning . PHP_EOL);
}

if ($errors !== []) {
    foreach (array_values(array_unique($errors)) as $error) {
        fwrite(STDERR, 'ERROR: ' . $error . PHP_EOL);
    }
    exit(1);
}

echo 'Status: PASS' . PHP_EOL;
