<?php

declare(strict_types=1);

/**
 * Venny I/O cartridge-aware bootstrap v2.
 *
 * The filesystem is the registry. A cartridge is installed/enabled when a
 * directory exists under /cartridges and contains a valid cartridge.php.
 * Every cartridge declares its autoload roots in that manifest.
 */
$root = dirname(__DIR__);

// Third-party SDKs are installed once at the application root. Cartridge
// classes remain cartridge-owned, while Composer owns provider dependencies.
$composerAutoload = $root . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

/** @var array<string, array<int, string>> $autoloadMap */
$autoloadMap = [
    'VennyIO\\' => [$root . '/src'],
];

$manifestPaths = glob($root . '/cartridges/*/cartridge.php') ?: [];
sort($manifestPaths, SORT_STRING);

foreach ($manifestPaths as $manifestPath) {
    $manifest = require $manifestPath;
    if (!is_array($manifest)) {
        throw new RuntimeException('Cartridge manifest must return an array: ' . $manifestPath);
    }

    $mappings = $manifest['autoload'] ?? [];
    if (!is_array($mappings)) {
        throw new RuntimeException('Cartridge autoload manifest key must be an array: ' . $manifestPath);
    }

    foreach ($mappings as $prefix => $path) {
        if (!is_string($prefix) || $prefix === '' || !is_string($path) || $path === '') {
            throw new RuntimeException('Invalid cartridge autoload mapping: ' . $manifestPath);
        }

        $autoloadMap[$prefix] ??= [];
        if (!in_array($path, $autoloadMap[$prefix], true)) {
            $autoloadMap[$prefix][] = $path;
        }
    }
}

// Specific namespaces win before broad shared namespaces such as VennyIO\.
uksort($autoloadMap, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

spl_autoload_register(static function (string $class) use ($autoloadMap): void {
    foreach ($autoloadMap as $prefix => $roots) {
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $relativePath = str_replace('\\', '/', $relativeClass) . '.php';

        foreach ($roots as $sourceRoot) {
            $file = rtrim($sourceRoot, '/\\') . '/' . $relativePath;
            if (is_file($file)) {
                require $file;
                return;
            }
        }
    }
});
