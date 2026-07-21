<?php

declare(strict_types=1);

namespace VennyIO\Kernel;

final class CartridgeManifest
{
    /** @param array<string, mixed> $data */
    public function __construct(
        private array $data,
        private string $basePath
    ) {
        $this->validate();
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $basePath): self
    {
        return new self($data, $basePath);
    }

    public function name(): string
    {
        return (string) $this->data['name'];
    }

    /** @return array<int, string> */
    public function requires(): array
    {
        return array_values(array_map('strval', $this->data['requires'] ?? []));
    }

    public function priority(): int
    {
        return (int) ($this->data['priority'] ?? 500);
    }

    public function routesPath(): ?string
    {
        $routes = $this->data['routes'] ?? null;
        if (!is_string($routes) || trim($routes) === '') {
            return null;
        }

        return $this->resolvePath($routes);
    }

    public function bootstrapPath(): ?string
    {
        $bootstrap = $this->data['bootstrap'] ?? null;
        if (!is_string($bootstrap) || trim($bootstrap) === '') {
            return null;
        }

        return $this->resolvePath($bootstrap);
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data + ['_path' => $this->basePath];
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return $this->basePath . '/' . ltrim($path, '/');
    }

    private function validate(): void
    {
        $name = $this->data['name'] ?? null;
        if (!is_string($name) || preg_match('/^(app|bm|int)_[a-z0-9_]+$/', $name) !== 1) {
            throw new \RuntimeException('Invalid cartridge name in ' . $this->basePath);
        }

        if (($this->data['requires'] ?? []) !== [] && !is_array($this->data['requires'])) {
            throw new \RuntimeException('Cartridge requires must be an array: ' . $name);
        }
    }
}
