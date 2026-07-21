<?php

declare(strict_types=1);

namespace VennyIO\Kernel;

use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use VennyIO\Support\Response;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function patch(string $pattern, callable $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        $effectiveMethod = $request->effectiveMethod();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $effectiveMethod) {
                continue;
            }

            $pattern = $this->compilePattern($route['pattern']);

            if (!preg_match($pattern, $request->path, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            $this->invokeHandler($route['handler'], $request, $params);
            return;
        }

        Response::json(404, false, 'route not found', []);
    }

    private function compilePattern(string $pattern): string
    {
        // Newer cartridges register explicit regex patterns like #^/posts$#.
        // Keep those untouched. Older/generated cartridges register template
        // paths like /contacts/{id}; those must be compiled before preg_match().
        if ($pattern !== '' && $pattern[0] === '#') {
            return $pattern;
        }

        $compiled = preg_quote($pattern, '#');
        $compiled = preg_replace(
            '#\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}#',
            '(?P<$1>[^/]+)',
            $compiled
        ) ?? $compiled;

        return '#^' . $compiled . '$#';
    }

    /**
     * Support both route handler styles currently present in cartridges:
     *
     * - static function (Request $request, array $params): void {}
     * - function (string $id, Request $request): void {}
     * - function (string $id): void {}
     * - function (): void {}
     */
    private function invokeHandler(callable $handler, Request $request, array $params): void
    {
        $reflection = is_array($handler)
            ? new ReflectionMethod($handler[0], (string) $handler[1])
            : new ReflectionFunction($handler);

        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;

            if ($typeName === Request::class || $typeName === 'VennyIO\\Kernel\\Request') {
                $arguments[] = $request;
                continue;
            }

            if ($typeName === 'array') {
                $arguments[] = $params;
                continue;
            }

            if (array_key_exists($name, $params)) {
                $arguments[] = $params[$name];
                continue;
            }

            if ($name === 'request') {
                $arguments[] = $request;
                continue;
            }

            if ($name === 'params') {
                $arguments[] = $params;
                continue;
            }

            if (array_key_exists('id', $params)) {
                $arguments[] = $params['id'];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            // Last-resort fallback for legacy untyped handlers.
            $arguments[] = $request;
        }

        $handler(...$arguments);
    }
}
