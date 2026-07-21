<?php

declare(strict_types=1);

namespace VennyIO\Kernel;

final class CartridgeDiscovery
{
    public function __construct(private string $cartridgesPath)
    {
    }

    /** @return array<string, CartridgeManifest> */
    public function discover(): array
    {
        if (!is_dir($this->cartridgesPath)) {
            throw new \RuntimeException('Cartridge directory not found: ' . $this->cartridgesPath);
        }

        $discovered = [];
        foreach (glob($this->cartridgesPath . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $manifest = $this->readManifest($directory);
            if ($manifest === null) {
                continue;
            }

            if (isset($discovered[$manifest->name()])) {
                throw new \RuntimeException('Duplicate cartridge name: ' . $manifest->name());
            }

            $discovered[$manifest->name()] = $manifest;
        }

        return $discovered;
    }

    private function readManifest(string $directory): ?CartridgeManifest
    {
        $jsonPath = $directory . '/manifest.json';
        if (is_file($jsonPath)) {
            try {
                $data = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new \RuntimeException('Invalid cartridge manifest: ' . $jsonPath, 0, $exception);
            }

            if (!is_array($data)) {
                throw new \RuntimeException('Cartridge manifest must be an object: ' . $jsonPath);
            }

            return CartridgeManifest::fromArray($data, $directory);
        }

        // Backward compatibility during the manifest.json migration.
        $phpPath = $directory . '/cartridge.php';
        if (!is_file($phpPath)) {
            return null;
        }

        $data = require $phpPath;
        if (!is_array($data)) {
            throw new \RuntimeException('Cartridge manifest must return an array: ' . $phpPath);
        }

        return CartridgeManifest::fromArray($data, $directory);
    }
}
