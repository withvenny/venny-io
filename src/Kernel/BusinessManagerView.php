<?php

declare(strict_types=1);

namespace VennyIO\Kernel;

final class BusinessManagerView
{
    public static function render(
        string $title,
        string $bodyHtml,
        string $activeSection = 'welcome',
        bool $showNavigation = true
    ): void {
        $activeSection = in_array($activeSection, ['welcome', 'environment', 'application'], true)
            ? $activeSection
            : 'welcome';

        $nav = [
            'welcome' => ['/business-manager/welcome.html', 'Welcome'],
            'environment' => ['/business-manager/environment.html', 'Environment'],
            'application' => ['/business-manager/application.html', 'Application'],
        ];

        $navHtml = '';
        if ($showNavigation) {
            foreach ($nav as $key => [$href, $label]) {
                $class = $key === $activeSection ? ' class="active" aria-current="page"' : '';
                $navHtml .= '<a href="' . $href . '"' . $class . '>' . $label . '</a>';
            }
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, private');
        header('Pragma: no-cache');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');

        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<meta name="color-scheme" content="light">';
        echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
        echo '<link rel="stylesheet" href="/business-manager/assets/style.css?v=2.0.2">';
        echo '</head><body><div class="bm-frame">';
        echo '<header class="bm-topbar"><div class="bm-topbar-inner"><div class="brand-row"><div class="brand-lockup">';
        echo '<img src="/business-manager/assets/logo.png?v=2.0.2" alt="do more with venny">';
        echo '<div class="brand-title"><strong>Business Manager</strong><span>Venny I/O administrative control surface</span></div>';
        echo '</div>';

        if ($showNavigation) {
            echo '<form class="bm-logout" method="post" action="/business-manager/logout.html">';
            echo '<button type="submit" class="quiet-button">Lock</button>';
            echo '</form>';
        }

        echo '</div>';

        if ($showNavigation) {
            echo '<nav class="bm-nav" aria-label="Business Manager">' . $navHtml . '</nav>';
        }

        echo '</div></header>';
        echo '<main class="bm-main' . ($showNavigation ? '' : ' bm-main-gate') . '">' . $bodyHtml . '</main>';
        echo '</div></body></html>';
    }
}
