<?php

declare(strict_types=1);

/**
 * Venny I/O cartridge-aware bootstrap.
 *
 * We intentionally keep this autoloader simple while the codebase is still
 * vanilla PHP. The namespace stays VennyIO\*, but classes can now live in:
 * - src/                         shared kernel/runtime files
 * - cartridges/[cartridge]/src/ cartridge-owned files
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'VennyIO\\';
    $root = dirname(__DIR__);

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relativeClass) . '.php';

    $candidates = [
        $root . '/src/' . $relativePath,
    ];

    foreach (glob($root . '/cartridges/*/src', GLOB_ONLYDIR) ?: [] as $cartridgeSrc) {
        $candidates[] = $cartridgeSrc . '/' . $relativePath;
    }

    foreach ($candidates as $file) {
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});
