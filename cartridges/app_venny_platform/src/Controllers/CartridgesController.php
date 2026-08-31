<?php

declare(strict_types=1);

namespace VennyIO\Controllers;

use VennyIO\Kernel\CartridgeRegistry;
use VennyIO\Support\Response;

final class CartridgesController
{
    public function __construct(private string $basePath)
    {
    }

    public function index(): void
    {
        Response::json(200, true, 'cartridges retrieved successfully', $this->allManifests());
    }

    public function show(string $name): void
    {
        foreach ($this->allManifests() as $manifest) {
            if (($manifest['name'] ?? '') === $name) {
                Response::json(200, true, 'cartridge retrieved successfully', $manifest);
                return;
            }
        }

        Response::json(404, false, 'cartridge not found', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allManifests(): array
    {
        $registry = new CartridgeRegistry($this->basePath);
        $rows = [];

        foreach ($registry->ordered() as $manifest) {
            $name = (string) ($manifest['name'] ?? '');
            $routesPath = is_string($manifest['routes'] ?? null) ? (string) $manifest['routes'] : '';

            $rows[] = [
                'manifest_version' => $manifest['manifest_version'] ?? null,
                'name' => $name,
                'type' => $manifest['type'] ?? null,
                'provider' => $manifest['provider'] ?? null,
                'domain' => $manifest['domain'] ?? null,
                'version' => $manifest['version'] ?? null,
                'description' => $manifest['description'] ?? null,
                'tool' => $manifest['tool'] ?? null,
                'tool_url' => $manifest['tool_url'] ?? null,
                'php' => $manifest['php'] ?? null,
                'requires' => $manifest['requires'] ?? [],
                'installed' => true,
                'enabled' => true,
                'routes' => $this->extractRouteSummary($routesPath),
                'sql' => $manifest['sql'] ?? [],
                'configuration' => $manifest['configuration'] ?? [],
                'capabilities' => $manifest['capabilities'] ?? [],
                'dependencies' => $manifest['dependencies'] ?? [],
            ];
        }

        return $rows;
    }

    private function extractRouteSummary(string $routesPath): array
    {
        if ($routesPath === '' || !is_file($routesPath)) {
            return [];
        }

        $contents = file_get_contents($routesPath) ?: '';
        preg_match_all('/\$router->(get|post|patch|delete)\(\'#[^#]*([^$#]+)\$#\'/i', $contents, $matches, PREG_SET_ORDER);

        $routes = [];
        foreach ($matches as $match) {
            $routes[] = [
                'method' => strtoupper($match[1]),
                'pattern' => $match[2],
            ];
        }

        return $routes;
    }
}
