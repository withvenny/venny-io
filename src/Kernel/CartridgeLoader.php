<?php

declare(strict_types=1);

namespace VennyIO\Kernel;

final class CartridgeLoader
{
    public function __construct(private string $basePath)
    {
    }

    /**
     * Discovers every standards-compliant cartridge, resolves dependencies,
     * loads optional bootstrap files, and registers routes automatically.
     *
     * @return array<int, array<string, mixed>>
     */
    public function loadAvailable(Router $router): array
    {
        $discovered = (new CartridgeDiscovery($this->basePath . '/cartridges'))->discover();
        $resolved = (new CartridgeDependencyResolver())->resolve($discovered);

        foreach ($resolved as $manifest) {
            $bootstrapPath = $manifest->bootstrapPath();
            if ($bootstrapPath !== null) {
                if (!is_file($bootstrapPath)) {
                    throw new \RuntimeException('Cartridge bootstrap file not found: ' . $bootstrapPath);
                }
                $bootstrap = require $bootstrapPath;
                if (is_callable($bootstrap)) {
                    $bootstrap($router, $manifest->toArray());
                }
            }

            $routesPath = $manifest->routesPath();
            if ($routesPath !== null) {
                if (!is_file($routesPath)) {
                    throw new \RuntimeException('Cartridge routes file not found: ' . $routesPath);
                }

                /** @var Router $router Available to legacy route files. */
                $routeRegistrar = require $routesPath;
                if (is_callable($routeRegistrar)) {
                    $routeRegistrar($router, $manifest->toArray());
                }
            }
        }

        (new CartridgeCache($this->basePath . '/storage/cache/cartridges.php'))->write($resolved);

        return array_map(
            static fn (CartridgeManifest $manifest): array => $manifest->toArray(),
            $resolved
        );
    }

}