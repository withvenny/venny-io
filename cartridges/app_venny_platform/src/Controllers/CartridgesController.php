<?php

declare(strict_types=1);

namespace VennyIO\Controllers;

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

    private function allManifests(): array
    {
        $enabled = require $this->basePath . '/config/cartridges.php';
        $enabled = is_array($enabled) ? $enabled : [];
        $rows = [];

        foreach (glob($this->basePath . '/cartridges/*/cartridge.php') ?: [] as $manifestPath) {
            $manifest = require $manifestPath;
            if (!is_array($manifest)) {
                continue;
            }

            $name = (string) ($manifest['name'] ?? basename(dirname($manifestPath)));
            $routesPath = (string) ($manifest['routes'] ?? '');
            $routes = $this->extractRouteSummary($routesPath);

            $rows[] = [
                'name' => $name,
                'type' => $manifest['type'] ?? null,
                'provider' => $manifest['provider'] ?? null,
                'domain' => $manifest['domain'] ?? null,
                'version' => $manifest['version'] ?? null,
                'requires' => $manifest['requires'] ?? [],
                'enabled' => in_array($name, $enabled, true),
                'routes' => $routes,
                'sql' => $manifest['sql'] ?? [],
            ];
        }

        usort($rows, static fn (array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));
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
