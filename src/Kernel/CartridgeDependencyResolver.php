<?php

declare(strict_types=1);

namespace VennyIO\Kernel;

final class CartridgeDependencyResolver
{
    /**
     * @param array<string, CartridgeManifest> $manifests
     * @return array<int, CartridgeManifest>
     */
    public function resolve(array $manifests): array
    {
        $resolved = [];
        $permanent = [];
        $temporary = [];

        $names = array_keys($manifests);
        usort($names, static function (string $left, string $right) use ($manifests): int {
            return [$manifests[$left]->priority(), $left] <=> [$manifests[$right]->priority(), $right];
        });

        $visit = function (string $name) use (&$visit, &$resolved, &$permanent, &$temporary, $manifests): void {
            if (isset($permanent[$name])) {
                return;
            }
            if (isset($temporary[$name])) {
                throw new \RuntimeException('Circular cartridge dependency detected at: ' . $name);
            }
            if (!isset($manifests[$name])) {
                throw new \RuntimeException('Missing cartridge dependency: ' . $name);
            }

            $temporary[$name] = true;
            foreach ($manifests[$name]->requires() as $dependency) {
                if (!isset($manifests[$dependency])) {
                    throw new \RuntimeException($name . ' requires missing cartridge ' . $dependency);
                }
                $visit($dependency);
            }

            unset($temporary[$name]);
            $permanent[$name] = true;
            $resolved[] = $manifests[$name];
        };

        foreach ($names as $name) {
            $visit($name);
        }

        return $resolved;
    }
}
