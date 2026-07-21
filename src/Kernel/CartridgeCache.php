<?php

declare(strict_types=1);

namespace VennyIO\Kernel;

final class CartridgeCache
{
    public function __construct(private string $cachePath)
    {
    }

    /** @param array<int, CartridgeManifest> $manifests */
    public function write(array $manifests): void
    {
        $directory = dirname($this->cachePath);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return;
        }

        $payload = [
            'generated_at' => gmdate(DATE_ATOM),
            'cartridges' => array_map(
                static fn (CartridgeManifest $manifest): array => $manifest->toArray(),
                $manifests
            ),
        ];

        @file_put_contents(
            $this->cachePath,
            "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($payload, true) . ";\n",
            LOCK_EX
        );
    }
}
