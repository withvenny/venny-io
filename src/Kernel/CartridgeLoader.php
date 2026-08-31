<?php

declare(strict_types=1);

namespace VennyIO\Kernel;

final class CartridgeLoader
{
    private CartridgeRegistry $registry;

    public function __construct(private string $basePath)
    {
        $this->registry = new CartridgeRegistry($basePath);
    }

    /**
     * Discover and run every installed cartridge in dependency-safe order.
     *
     * @return array<int, array<string, mixed>>
     */
    public function load(Router $router): array
    {
        $loaded = [];

        foreach ($this->registry->ordered() as $manifest) {
            $routesPath = $manifest['routes'];
            if (is_string($routesPath) && $routesPath !== '') {
                /** @var Router $router */
                require $routesPath;
            }

            $loaded[] = $manifest;
        }

        return $loaded;
    }

    public function registry(): CartridgeRegistry
    {
        return $this->registry;
    }
}
