<?php

declare(strict_types=1);

namespace VennyIO\Kernel;

final class CartridgeRegistry
{
    private const MANIFEST_FILE = 'cartridge.php';

    /**
     * Every Venny cartridge publishes the same top-level manifest contract.
     * Values may be null/empty where a capability does not apply.
     */
    private const REQUIRED_KEYS = [
        'manifest_version',
        'name',
        'type',
        'provider',
        'domain',
        'version',
        'description',
        'tool',
        'tool_url',
        'php',
        'requires',
        'dependencies',
        'configuration',
        'capabilities',
        'documentation',
        'routes',
        'sql',
        'business_manager',
        'autoload',
    ];

    /** @var array<int, array<string, mixed>>|null */
    private ?array $manifests = null;

    public function __construct(private string $basePath)
    {
    }

    /**
     * Discover every cartridge directory from the filesystem.
     * Directory presence + a valid cartridge.php means installed and enabled.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->manifests !== null) {
            return $this->manifests;
        }

        $cartridgeRoot = rtrim($this->basePath, '/\\') . '/cartridges';
        if (!is_dir($cartridgeRoot)) {
            throw new \RuntimeException('Cartridge directory not found: ' . $cartridgeRoot);
        }

        $directories = glob($cartridgeRoot . '/*', GLOB_ONLYDIR) ?: [];
        sort($directories, SORT_STRING);

        $manifests = [];
        $names = [];

        foreach ($directories as $directory) {
            $directoryName = basename($directory);
            $manifestPath = $directory . '/' . self::MANIFEST_FILE;

            if (!is_file($manifestPath)) {
                throw new \RuntimeException(
                    'Cartridge directory is missing ' . self::MANIFEST_FILE . ': ' . $directoryName
                );
            }

            $manifest = require $manifestPath;
            if (!is_array($manifest)) {
                throw new \RuntimeException('Cartridge manifest must return an array: ' . $directoryName);
            }

            $this->validateManifest($manifest, $directoryName, $manifestPath);

            $name = (string) $manifest['name'];
            if (isset($names[$name])) {
                throw new \RuntimeException('Duplicate cartridge name discovered: ' . $name);
            }
            $names[$name] = true;

            $manifest['_directory'] = $directory;
            $manifest['_manifest_path'] = $manifestPath;
            $manifest['_installed'] = true;
            $manifest['_enabled'] = true;
            $manifests[] = $manifest;
        }

        $this->manifests = $manifests;
        return $this->manifests;
    }

    /**
     * Return all cartridges in dependency-safe boot order.
     *
     * @return array<int, array<string, mixed>>
     */
    public function ordered(): array
    {
        $manifests = $this->all();
        $errors = $this->dependencyErrors();
        if ($errors !== []) {
            throw new \RuntimeException('Cartridge dependency failure: ' . implode(' ', $errors));
        }

        $byName = [];
        $indegree = [];
        $dependents = [];

        foreach ($manifests as $manifest) {
            $name = (string) $manifest['name'];
            $byName[$name] = $manifest;
            $indegree[$name] = count($manifest['requires']);
            $dependents[$name] = [];
        }

        foreach ($manifests as $manifest) {
            $name = (string) $manifest['name'];
            foreach ($manifest['requires'] as $required) {
                $dependents[$required][] = $name;
            }
        }

        foreach ($dependents as &$children) {
            sort($children, SORT_STRING);
        }
        unset($children);

        $ready = [];
        foreach ($indegree as $name => $count) {
            if ($count === 0) {
                $ready[] = $name;
            }
        }
        sort($ready, SORT_STRING);

        $ordered = [];
        while ($ready !== []) {
            $name = array_shift($ready);
            $ordered[] = $byName[$name];

            foreach ($dependents[$name] as $child) {
                $indegree[$child]--;
                if ($indegree[$child] === 0) {
                    $ready[] = $child;
                    sort($ready, SORT_STRING);
                }
            }
        }

        if (count($ordered) !== count($manifests)) {
            throw new \RuntimeException('Cartridge dependency cycle detected.');
        }

        return $ordered;
    }

    /**
     * @return array<int, string>
     */
    public function dependencyErrors(): array
    {
        $manifests = $this->all();
        $available = [];
        $graph = [];

        foreach ($manifests as $manifest) {
            $name = (string) $manifest['name'];
            $available[$name] = true;
            $graph[$name] = $manifest['requires'];
        }

        $errors = [];
        foreach ($graph as $name => $requires) {
            foreach ($requires as $required) {
                if (!isset($available[$required])) {
                    $errors[] = $name . ' requires missing cartridge ' . $required . '.';
                }
            }
        }

        foreach ($this->dependencyCycles($graph) as $cycle) {
            $errors[] = 'Cartridge dependency cycle detected: ' . implode(' -> ', $cycle) . '.';
        }

        return array_values(array_unique($errors));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $name): ?array
    {
        foreach ($this->all() as $manifest) {
            if (($manifest['name'] ?? null) === $name) {
                return $manifest;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function validateManifest(array $manifest, string $directoryName, string $manifestPath): void
    {
        foreach (self::REQUIRED_KEYS as $key) {
            if (!array_key_exists($key, $manifest)) {
                throw new \RuntimeException(
                    'Cartridge manifest missing required key "' . $key . '": ' . $directoryName
                );
            }
        }

        $name = $manifest['name'];
        if (!is_string($name) || $name === '') {
            throw new \RuntimeException('Cartridge manifest name must be a non-empty string: ' . $manifestPath);
        }

        if ($name !== $directoryName) {
            throw new \RuntimeException(
                'Cartridge manifest name must match its directory. Directory=' . $directoryName . ', name=' . $name
            );
        }

        $type = $manifest['type'];
        if (!is_string($type) || !in_array($type, ['app', 'integration', 'bm'], true)) {
            throw new \RuntimeException('Invalid cartridge type for ' . $name . '.');
        }

        $expectedPrefix = match ($type) {
            'app' => 'app_',
            'integration' => 'int_',
            'bm' => 'bm_',
        };

        if (!str_starts_with($name, $expectedPrefix)) {
            throw new \RuntimeException(
                'Cartridge type/name mismatch for ' . $name . '; expected prefix ' . $expectedPrefix
            );
        }

        foreach (['requires', 'configuration', 'capabilities', 'documentation'] as $arrayKey) {
            if (!is_array($manifest[$arrayKey])) {
                throw new \RuntimeException('Cartridge manifest key ' . $arrayKey . ' must be an array: ' . $name);
            }
        }

        foreach ($manifest['requires'] as $required) {
            if (!is_string($required) || $required === '') {
                throw new \RuntimeException('Cartridge requires[] must contain cartridge names: ' . $name);
            }
            if ($required === $name) {
                throw new \RuntimeException('Cartridge cannot require itself: ' . $name);
            }
        }

        foreach (['dependencies', 'sql', 'business_manager', 'autoload'] as $mapKey) {
            if (!is_array($manifest[$mapKey])) {
                throw new \RuntimeException('Cartridge manifest key ' . $mapKey . ' must be an array: ' . $name);
            }
        }

        $routes = $manifest['routes'];
        if ($routes !== null && (!is_string($routes) || $routes === '')) {
            throw new \RuntimeException('Cartridge routes must be null or a non-empty path: ' . $name);
        }

        if ($routes !== null && !is_file($routes)) {
            throw new \RuntimeException('Declared cartridge routes file is missing: ' . $name . ' -> ' . $routes);
        }

        foreach ($manifest['sql'] as $role => $path) {
            if (!is_string($role)) {
                throw new \RuntimeException('Cartridge sql roles must be string keys: ' . $name);
            }
            if ($path !== null && (!is_string($path) || $path === '')) {
                throw new \RuntimeException('Cartridge sql path must be null or a non-empty string: ' . $name);
            }
        }

        foreach ($manifest['autoload'] as $prefix => $path) {
            if (!is_string($prefix) || $prefix === '' || !is_string($path) || $path === '') {
                throw new \RuntimeException('Cartridge autoload mappings must be namespace => path: ' . $name);
            }
            if (!is_dir($path)) {
                throw new \RuntimeException('Cartridge autoload path is missing: ' . $name . ' -> ' . $path);
            }

            $phpFiles = glob(rtrim($path, '/\\') . '/*.php') ?: [];
            if ($phpFiles === []) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                        $phpFiles[] = $file->getPathname();
                        break;
                    }
                }
            }

            if ($phpFiles === []) {
                throw new \RuntimeException(
                    'Cartridge autoload path contains no PHP source files; remove the mapping until source exists: '
                    . $name . ' -> ' . $path
                );
            }
        }
    }

    /**
     * @param array<string, array<int, string>> $graph
     * @return array<int, array<int, string>>
     */
    private function dependencyCycles(array $graph): array
    {
        $state = [];
        $stack = [];
        $cycles = [];

        $visit = function (string $name) use (&$visit, &$state, &$stack, &$cycles, $graph): void {
            $current = $state[$name] ?? 0;
            if ($current === 2) {
                return;
            }
            if ($current === 1) {
                $position = array_search($name, $stack, true);
                if ($position !== false) {
                    $cycle = array_slice($stack, $position);
                    $cycle[] = $name;
                    $cycles[] = $cycle;
                }
                return;
            }

            $state[$name] = 1;
            $stack[] = $name;
            foreach ($graph[$name] ?? [] as $required) {
                if (array_key_exists($required, $graph)) {
                    $visit($required);
                }
            }
            array_pop($stack);
            $state[$name] = 2;
        };

        foreach (array_keys($graph) as $name) {
            $visit($name);
        }

        return $cycles;
    }
}
