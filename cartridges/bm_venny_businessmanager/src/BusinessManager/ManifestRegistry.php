<?php

declare(strict_types=1);

namespace VennyIO\BusinessManager;

use VennyIO\Kernel\CartridgeRegistry;

final class ManifestRegistry
{
    private CartridgeRegistry $registry;

    public function __construct(private string $rootPath)
    {
        $this->registry = new CartridgeRegistry($rootPath);
    }

    /**
     * Return every filesystem-discovered cartridge with Business Manager
     * diagnostics layered onto the canonical runtime manifest.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $manifests = [];

        foreach ($this->registry->all() as $manifest) {
            $sql = [];
            foreach (($manifest['sql'] ?? []) as $role => $path) {
                if (!is_string($role) || $path === null) {
                    continue;
                }
                if (!is_string($path) || $path === '') {
                    continue;
                }

                $sql[$role] = [
                    'path' => $this->relativePath($path),
                    'exists' => is_file($path),
                    'is_sql' => strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'sql',
                ];
            }

            $routesPath = isset($manifest['routes']) && is_string($manifest['routes'])
                ? $manifest['routes']
                : null;

            $businessManager = [];
            foreach (($manifest['business_manager'] ?? []) as $role => $path) {
                if (!is_string($role)) {
                    continue;
                }
                $businessManager[$role] = $path === null
                    ? null
                    : [
                        'path' => $this->relativePath((string) $path),
                        'exists' => is_file((string) $path),
                    ];
            }

            $manifests[] = array_merge($manifest, [
                'installed' => true,
                'enabled' => true,
                'routes' => $routesPath !== null ? $this->relativePath($routesPath) : null,
                'routes_declared' => $routesPath !== null,
                'routes_exists' => $routesPath !== null && is_file($routesPath),
                'sql' => $sql,
                'business_manager' => $businessManager,
                'manifest_exists' => true,
                'manifest_path' => $this->relativePath((string) ($manifest['_manifest_path'] ?? '')),
            ]);
        }

        return $manifests;
    }

    /**
     * Return executable SQL files declared by application cartridges only.
     * Installation remains inside Business Manager. All schema files run before
     * constraints, then indexes, and dependency order is honored within a phase.
     *
     * @return array<int, array{cartridge:string,role:string,path:string,exists:bool,dependency_depth:int,dependency_count:int}>
     */
    public function schemaPlan(): array
    {
        $apps = array_values(array_filter(
            $this->all(),
            static fn (array $manifest): bool => ($manifest['type'] ?? null) === 'app'
        ));
        $depths = $this->dependencyDepths($apps);

        usort($apps, static function (array $left, array $right) use ($depths): int {
            $leftName = (string) ($left['name'] ?? '');
            $rightName = (string) ($right['name'] ?? '');
            $depth = ((int) ($depths[$leftName] ?? 0)) <=> ((int) ($depths[$rightName] ?? 0));
            if ($depth !== 0) {
                return $depth;
            }

            $leftCount = count(is_array($left['requires'] ?? null) ? $left['requires'] : []);
            $rightCount = count(is_array($right['requires'] ?? null) ? $right['requires'] : []);
            $count = $leftCount <=> $rightCount;
            if ($count !== 0) {
                return $count;
            }

            return strcmp($leftName, $rightName);
        });

        $standardRoles = ['schema', 'constraints', 'indexes'];
        $plan = [];

        foreach ($standardRoles as $role) {
            foreach ($apps as $manifest) {
                $metadata = $manifest['sql'][$role] ?? null;
                if (!is_array($metadata) || ($metadata['is_sql'] ?? false) !== true) {
                    continue;
                }

                $name = (string) ($manifest['name'] ?? '');
                $plan[] = [
                    'cartridge' => $name,
                    'role' => $role,
                    'path' => (string) ($metadata['path'] ?? ''),
                    'exists' => (bool) ($metadata['exists'] ?? false),
                    'dependency_depth' => (int) ($depths[$name] ?? 0),
                    'dependency_count' => count(is_array($manifest['requires'] ?? null) ? $manifest['requires'] : []),
                ];
            }
        }

        foreach ($apps as $manifest) {
            foreach (($manifest['sql'] ?? []) as $role => $metadata) {
                if (!is_string($role) || in_array($role, $standardRoles, true) || !is_array($metadata)) {
                    continue;
                }
                if (($metadata['is_sql'] ?? false) !== true) {
                    continue;
                }

                $name = (string) ($manifest['name'] ?? '');
                $plan[] = [
                    'cartridge' => $name,
                    'role' => $role,
                    'path' => (string) ($metadata['path'] ?? ''),
                    'exists' => (bool) ($metadata['exists'] ?? false),
                    'dependency_depth' => (int) ($depths[$name] ?? 0),
                    'dependency_count' => count(is_array($manifest['requires'] ?? null) ? $manifest['requires'] : []),
                ];
            }
        }

        return $plan;
    }

    /**
     * @return array<int, string>
     */
    public function dependencyErrors(): array
    {
        return $this->registry->dependencyErrors();
    }

    /**
     * @param array<int, array<string, mixed>> $ordered
     * @return array<string, int>
     */
    private function dependencyDepths(array $manifests): array
    {
        $byName = [];
        foreach ($manifests as $manifest) {
            $name = (string) ($manifest['name'] ?? '');
            if ($name !== '') {
                $byName[$name] = $manifest;
            }
        }

        $memo = [];
        $visiting = [];

        $depth = function (string $name) use (&$depth, &$memo, &$visiting, $byName): int {
            if (isset($memo[$name])) {
                return $memo[$name];
            }
            if (isset($visiting[$name])) {
                // dependencyErrors() reports cycles. Keep the plan renderable so
                // Business Manager can display the blocking dependency error.
                return 0;
            }

            $manifest = $byName[$name] ?? null;
            if (!is_array($manifest)) {
                return 0;
            }

            $visiting[$name] = true;
            $maxParent = -1;
            foreach (($manifest['requires'] ?? []) as $required) {
                if (!is_string($required) || !isset($byName[$required])) {
                    continue;
                }
                $maxParent = max($maxParent, $depth($required));
            }
            unset($visiting[$name]);

            $memo[$name] = $maxParent + 1;
            return $memo[$name];
        };

        foreach (array_keys($byName) as $name) {
            $depth($name);
        }

        return $memo;
    }

    private function relativePath(string $path): string
    {
        $normalizedRoot = rtrim(str_replace('\\', '/', $this->rootPath), '/');
        $normalizedPath = str_replace('\\', '/', $path);

        if (str_starts_with($normalizedPath, $normalizedRoot . '/')) {
            return substr($normalizedPath, strlen($normalizedRoot) + 1);
        }

        return $normalizedPath;
    }
}
