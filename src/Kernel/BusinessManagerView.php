<?php

declare(strict_types=1);

namespace VennyIO\Kernel;

final class BusinessManagerView
{
    public static function render(string $title, string $bodyHtml): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
        echo '<link rel="stylesheet" href="/bm_venny_setupwizard/style.css">';
        echo '</head><body><main class="shell">' . $bodyHtml . '</main></body></html>';
    }
}
