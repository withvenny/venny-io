<?php

declare(strict_types=1);

use VennyIO\Kernel\BusinessManagerView;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;

/** @var Router $router */

$renderBusinessManager = static function (): void {
    BusinessManagerView::render('Venny I/O Business Manager', <<<HTML
<header class="hero">
  <div>
    <p class="eyebrow">Business Manager</p>
    <h1>Venny I/O Command Center</h1>
    <p class="muted">The setup wizard now lives in the <code>bm_venny_setupwizard</code> cartridge.</p>
  </div>
</header>
<section class="card">
  <h2>Cartridge refactor started</h2>
  <p>This route proves the Business Manager cartridge is loading independently from the platform cartridge.</p>
  <div class="grid two">
    <div>
      <h3>Loaded Business Manager cartridge</h3>
      <code>bm_venny_setupwizard</code>
    </div>
    <div>
      <h3>Required platform cartridge</h3>
      <code>app_venny_platform</code>
    </div>
  </div>
</section>
HTML);
};

$router->get('#^/$#', $renderBusinessManager);
$router->get('#^/setup$#', $renderBusinessManager);
$router->get('#^/business-manager$#', $renderBusinessManager);
