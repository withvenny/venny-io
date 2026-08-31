<?php

declare(strict_types=1);

use VennyIO\BusinessManager\BusinessManagerAccess;
use VennyIO\BusinessManager\DatabaseInstaller;
use VennyIO\BusinessManager\ManifestRegistry;
use VennyIO\Kernel\BusinessManagerView;
use VennyIO\Kernel\Request;
use VennyIO\Kernel\Router;
use VennyIO\Support\Database;

/** @var Router $router */

$rootPath = dirname(__DIR__, 2);
$registry = new ManifestRegistry($rootPath);
$access = new BusinessManagerAccess();
$installer = new DatabaseInstaller($rootPath, $registry);

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$redirect = static function (string $location): void {
    header('Location: ' . $location, true, 302);
};

$render = static function (string $title, string $activeSection, string $bodyHtml, bool $showNavigation = true): void {
    BusinessManagerView::render($title, $bodyHtml, $activeSection, $showNavigation);
};

$safeNextPath = static function (mixed $value): string {
    $path = is_string($value) ? trim($value) : '';

    if ($path === '' || !str_starts_with($path, '/business-manager/')) {
        return '/business-manager/welcome.html';
    }

    $blocked = [
        '/business-manager/login.html',
        '/business-manager/logout.html',
    ];

    if (in_array($path, $blocked, true) || str_starts_with($path, '/business-manager/assets/')) {
        return '/business-manager/welcome.html';
    }

    return $path;
};

$renderLogin = static function (?string $error = null, string $next = '/business-manager/welcome.html') use ($render, $access, $escape): void {
    $configurationError = $access->configurationError();
    $configured = $configurationError === null;

    if ($configured) {
        $message = $error === null
            ? ''
            : '<div class="gate-alert" role="alert">' . $escape($error) . '</div>';

        $body = '
<section class="gate-shell">
  <div class="gate-copy">
    <p class="kicker">Restricted access</p>
    <h1>Business Manager</h1>
    <p class="lede">Enter the five-word Business Manager passphrase to continue.</p>
  </div>
  <section class="panel gate-panel">
    ' . $message . '
    <form method="post" action="/business-manager/login.html" class="gate-form" autocomplete="off">
      <input type="hidden" name="next" value="' . $escape($next) . '">
      <fieldset class="passphrase-fieldset">
        <legend>Passphrase</legend>
        <div class="passphrase-words" aria-label="Five-word Business Manager passphrase">
          <input name="passphrase_words[]" type="password" autocomplete="off" spellcheck="false" autocapitalize="none" maxlength="5" required autofocus aria-label="Passphrase word 1">
          <input name="passphrase_words[]" type="password" autocomplete="off" spellcheck="false" autocapitalize="none" maxlength="5" required aria-label="Passphrase word 2">
          <input name="passphrase_words[]" type="password" autocomplete="off" spellcheck="false" autocapitalize="none" maxlength="5" required aria-label="Passphrase word 3">
          <input name="passphrase_words[]" type="password" autocomplete="off" spellcheck="false" autocapitalize="none" maxlength="5" required aria-label="Passphrase word 4">
          <input name="passphrase_words[]" type="password" autocomplete="off" spellcheck="false" autocapitalize="none" maxlength="5" required aria-label="Passphrase word 5">
        </div>
      </fieldset>
      <p class="field-note">One word per field. Each word may contain no more than five characters.</p>
      <button type="submit">Enter Business Manager</button>
    </form>
  </section>
</section>';
    } else {
        $body = '
<section class="gate-shell">
  <div class="gate-copy">
    <p class="kicker">Configuration required</p>
    <h1>Business Manager is locked</h1>
    <p class="lede">The Business Manager passphrase is not configured correctly.</p>
  </div>
  <section class="panel gate-panel">
    <p><code>' . $escape(BusinessManagerAccess::ENV_KEY) . '</code> must contain exactly five whitespace-separated words. No word may exceed five characters.</p>
    <p class="muted">Set the variable in the hosting environment, then reload this page. The passphrase value is never rendered by Business Manager.</p>
  </section>
</section>';
    }

    $render('Access | Venny I/O Business Manager', 'welcome', $body, false);
};

$protect = static function (callable $handler) use ($access, $redirect): callable {
    return static function (Request $request) use ($access, $redirect, $handler): void {
        if (!$access->isAuthorized()) {
            $next = rawurlencode($request->path);
            $redirect('/business-manager/login.html?next=' . $next);
            return;
        }

        $handler($request);
    };
};

$renderWelcome = static function () use ($render, $registry, $escape): void {
    $manifests = $registry->all();
    $appCount = count(array_filter($manifests, static fn (array $manifest): bool => ($manifest['type'] ?? null) === 'app'));
    $integrationCount = count(array_filter($manifests, static fn (array $manifest): bool => ($manifest['type'] ?? null) === 'integration'));
    $bmCount = count(array_filter($manifests, static fn (array $manifest): bool => ($manifest['type'] ?? null) === 'bm'));

    $body = '
<section class="page-head">
  <p class="kicker">Welcome</p>
  <h1>Venny I/O Business Manager</h1>
  <p class="lede">Administrative visibility for the current Venny I/O environment and filesystem-discovered cartridge set.</p>
</section>
<section class="metric-grid" aria-label="Business Manager summary">
  <article class="metric-card"><span>Installed cartridges</span><strong>' . $escape(count($manifests)) . '</strong></article>
  <article class="metric-card"><span>Application cartridges</span><strong>' . $escape($appCount) . '</strong></article>
  <article class="metric-card"><span>Integration cartridges</span><strong>' . $escape($integrationCount) . '</strong></article>
  <article class="metric-card"><span>Business Manager cartridges</span><strong>' . $escape($bmCount) . '</strong></article>
</section>
<section class="panel">
  <div class="panel-head"><div><p class="kicker">Current contract</p><h2>Manifest-driven administration</h2></div></div>
  <p>Venny I/O 2.0 discovers every directory under <code>/cartridges</code> that contains a valid <code>cartridge.php</code>. No central cartridge registry file is used.</p>
  <p class="muted">The manifest is the cartridge contract for identity, dependencies, autoloading, routes, SQL declarations, configuration, capabilities, and Business Manager integration.</p>
</section>';

    $render('Welcome | Venny I/O Business Manager', 'welcome', $body);
};

$renderEnvironment = static function () use ($render): void {
    $body = '
<section class="page-head">
  <p class="kicker">Environment</p>
  <h1>Environment</h1>
  <p class="lede">Inspect the runtime and understand how configuration is currently exposed to Venny I/O.</p>
</section>
<section class="choice-grid">
  <a class="choice-card" href="/business-manager/environment/infrastructure.html"><span class="choice-index">01</span><h2>Infrastructure</h2><p>Server, PHP, database and runtime settings.</p></a>
  <a class="choice-card" href="/business-manager/environment/variables.html"><span class="choice-index">02</span><h2>Variables</h2><p>Global and application variable visibility.</p></a>
</section>';

    $render('Environment | Venny I/O Business Manager', 'environment', $body);
};

$renderInfrastructure = static function () use ($render, $escape): void {
    $database = null;
    $databaseError = null;

    try {
        $database = Database::health();
    } catch (Throwable $throwable) {
        $databaseError = $throwable->getMessage();
    }

    $serverRows = [
        ['PHP version', PHP_VERSION],
        ['PHP SAPI', PHP_SAPI],
        ['Operating system', PHP_OS_FAMILY],
        ['Server software', $_SERVER['SERVER_SOFTWARE'] ?? 'Unavailable'],
        ['Hostname', gethostname() ?: 'Unavailable'],
        ['Timezone', date_default_timezone_get()],
        ['Dyno', getenv('DYNO') ?: 'Not reported'],
        ['Release', getenv('HEROKU_RELEASE_VERSION') ?: 'Not reported'],
    ];

    $rowsHtml = '';
    foreach ($serverRows as [$label, $value]) {
        $rowsHtml .= '<tr><th scope="row">' . $escape($label) . '</th><td>' . $escape($value) . '</td></tr>';
    }

    if (is_array($database)) {
        $databaseHtml = '<div class="status-line"><span class="status-dot good"></span><strong>Database connected</strong></div>'
            . '<dl class="definition-grid">'
            . '<div><dt>Database</dt><dd>' . $escape($database['database_name'] ?? 'Unavailable') . '</dd></div>'
            . '<div><dt>Host</dt><dd>' . $escape($database['host'] ?? 'Unavailable') . '</dd></div>'
            . '<div><dt>Port</dt><dd>' . $escape($database['port'] ?? 'Unavailable') . '</dd></div>'
            . '<div><dt>SSL mode</dt><dd>' . $escape($database['sslmode'] ?? 'Unavailable') . '</dd></div>'
            . '<div><dt>Connect time</dt><dd>' . $escape($database['timing_ms']['connect'] ?? 'Unavailable') . ' ms</dd></div>'
            . '<div><dt>Query time</dt><dd>' . $escape($database['timing_ms']['query'] ?? 'Unavailable') . ' ms</dd></div>'
            . '</dl>';
    } else {
        $databaseHtml = '<div class="status-line"><span class="status-dot bad"></span><strong>Database unavailable</strong></div>'
            . '<p class="muted">' . $escape($databaseError ?? 'Database health could not be read.') . '</p>';
    }

    $body = '
<section class="page-head">
  <p class="kicker">Environment / Infrastructure</p>
  <h1>Infrastructure</h1>
  <p class="lede">Read-only runtime information. Secret values are not displayed.</p>
</section>
<div class="content-grid two-col">
  <section class="panel">
    <div class="panel-head"><div><p class="kicker">Runtime</p><h2>Server settings</h2></div></div>
    <div class="table-wrap"><table><tbody>' . $rowsHtml . '</tbody></table></div>
  </section>
  <section class="panel">
    <div class="panel-head"><div><p class="kicker">PostgreSQL</p><h2>Database</h2></div></div>
    ' . $databaseHtml . '
  </section>
</div>';

    $render('Infrastructure | Venny I/O Business Manager', 'environment', $body);
};

$renderVariables = static function () use ($render, $escape): void {
    $environment = getenv();
    $environment = is_array($environment) ? $environment : $_ENV;

    $criticalKeys = [
        'DATABASE_URL',
        'v_SITE_NAME',
        'v_SITE_DESCRIPTION',
        'v_SITE_LANGUAGE',
        'v_SITE_LOCALE',
        'v_SITE_CURRENCY',
        'v_SITE_SAFE_URLS',
    ];

    $vennyKeys = array_values(array_filter(
        array_map(static fn (mixed $key): string => (string) $key, array_keys($environment)),
        static fn (string $key): bool => str_starts_with($key, 'v_') && !in_array($key, $criticalKeys, true)
    ));
    natcasesort($vennyKeys);
    $vennyKeys = array_values(array_unique($vennyKeys));

    $keys = array_merge($criticalKeys, $vennyKeys);

    $rows = '';
    foreach ($keys as $key) {
        $safeKey = $escape($key);
        $isSet = array_key_exists($key, $environment);
        $status = $isSet
            ? '<span class="pill good variable-status"><span class="status-dot good"></span>Set</span>'
            : '<span class="pill bad variable-status"><span class="status-dot bad"></span>Not set</span>';
        $critical = in_array($key, $criticalKeys, true) ? ' data-critical-variable="true"' : '';

        $rows .= '<tr class="variable-row" data-variable-row data-key="' . $safeKey . '"' . $critical . '>'
            . '<td class="variable-key"><code>' . $safeKey . '</code></td>'
            . '<td class="variable-status-cell">' . $status . '</td>'
            . '<td class="variable-value-cell"><label class="sr-only" for="value-' . $escape(hash('sha256', $key)) . '">New value for ' . $safeKey . '</label>'
            . '<input id="value-' . $escape(hash('sha256', $key)) . '" class="variable-value" type="password" autocomplete="new-password" spellcheck="false" placeholder="Enter new value" data-variable-value>'
            . '</td>'
            . '<td class="variable-action"><button type="button" class="secondary" data-generate-command>Generate statement</button></td>'
            . '</tr>';
    }

    $body = '
<section class="page-head">
  <p class="kicker">Environment / Variables</p>
  <h1>Variables</h1>
  <p class="lede">Review project configuration without exposing current values. Business Manager never changes a server variable from this screen.</p>
</section>
<section class="panel">
  <div class="panel-head variable-panel-head">
    <div><p class="kicker">Project configuration</p><h2>Managed variables</h2></div>
    <span class="pill">' . $escape(count($keys)) . ' variables</span>
  </div>
  <p>The seven critical project variables always appear first. Status indicates whether each key exists in the running environment; current values are never rendered.</p>
  <p class="muted">Additional Venny-managed variables are discovered by the <code>v_</code> prefix and listed after the critical variables. Heroku/PHP runtime and buildpack variables are intentionally omitted.</p>
  <div class="variable-toolbar">
    <label for="variable-filter">Filter variables</label>
    <input id="variable-filter" type="search" placeholder="Search by key name" autocomplete="off" data-variable-filter>
  </div>
  <div class="table-wrap variable-table-wrap">
    <table class="variable-table">
      <thead><tr><th>Key</th><th>Status</th><th>New value</th><th><span class="sr-only">Action</span></th></tr></thead>
      <tbody>' . $rows . '</tbody>
    </table>
  </div>
</section>
<section class="panel command-panel" data-command-panel hidden>
  <div class="panel-head">
    <div><p class="kicker">Terminal</p><h2>Generated statement</h2></div>
    <button type="button" class="secondary" data-copy-command>Copy statement</button>
  </div>
  <p>Run this statement from the application repository. Business Manager does not execute it and does not submit the value to the server.</p>
  <pre class="command-output"><code data-command-output></code></pre>
  <p class="muted" data-copy-status aria-live="polite"></p>
</section>
<script>
(() => {
  const rows = Array.from(document.querySelectorAll("[data-variable-row]"));
  const filter = document.querySelector("[data-variable-filter]");
  const panel = document.querySelector("[data-command-panel]");
  const output = document.querySelector("[data-command-output]");
  const copyButton = document.querySelector("[data-copy-command]");
  const copyStatus = document.querySelector("[data-copy-status]");

  const shellQuote = (value) => "\'" + String(value).replaceAll("\'", "\'\"\'\"\'") + "\'";

  rows.forEach((row) => {
    const button = row.querySelector("[data-generate-command]");
    const input = row.querySelector("[data-variable-value]");

    button.addEventListener("click", () => {
      const key = row.dataset.key || "";
      const value = input.value;

      if (value.length === 0) {
        input.focus();
        input.setCustomValidity("Enter a value before generating a statement.");
        input.reportValidity();
        return;
      }

      input.setCustomValidity("");
      output.textContent = "heroku config:set " + shellQuote(key + "=" + value);
      panel.hidden = false;
      copyStatus.textContent = "";
      panel.scrollIntoView({ behavior: "smooth", block: "nearest" });
    });

    input.addEventListener("input", () => input.setCustomValidity(""));
  });

  if (filter) {
    filter.addEventListener("input", () => {
      const query = filter.value.trim().toLowerCase();
      rows.forEach((row) => {
        const key = (row.dataset.key || "").toLowerCase();
        row.hidden = query !== "" && !key.includes(query);
      });
    });
  }

  if (copyButton) {
    copyButton.addEventListener("click", async () => {
      const command = output.textContent || "";
      if (command === "") return;

      try {
        await navigator.clipboard.writeText(command);
        copyStatus.textContent = "Statement copied.";
      } catch (error) {
        copyStatus.textContent = "Copy failed. Select the statement and copy it manually.";
      }
    });
  }
})();
</script>';

    $render('Variables | Venny I/O Business Manager', 'environment', $body);
};

$renderApplication = static function () use ($render): void {
    $body = '
<section class="page-head">
  <p class="kicker">Application</p>
  <h1>Application</h1>
  <p class="lede">Inspect application data declarations and the cartridges installed in this Venny I/O application.</p>
</section>
<section class="choice-grid">
  <a class="choice-card" href="/business-manager/application/data.html"><span class="choice-index">01</span><h2>Data</h2><p>Schema files declared by installed application cartridges.</p></a>
  <a class="choice-card" href="/business-manager/application/cartridges.html"><span class="choice-index">02</span><h2>Cartridges</h2><p>Current manifest metadata, dependencies, routes and SQL declarations.</p></a>
</section>';

    $render('Application | Venny I/O Business Manager', 'application', $body);
};

$renderData = static function (Request $request, ?array $executionResult = null) use ($render, $registry, $installer, $access, $escape): void {
    $plan = $registry->schemaPlan();
    $missing = count(array_filter($plan, static fn (array $item): bool => ($item['exists'] ?? false) !== true));
    $dependencyErrors = $registry->dependencyErrors();
    $databaseStatus = $installer->status();

    $requestedInstallation = is_string($request->query['installation'] ?? null)
        ? trim((string) $request->query['installation'])
        : '';
    $installation = $executionResult;
    if ($installation === null && $requestedInstallation !== '') {
        $installation = $installer->installation($requestedInstallation);
    }

    $appliedByPath = [];
    if (is_array($installation)) {
        $installationSteps = $installation['steps'] ?? [];
        if (is_array($installationSteps)) {
            foreach ($installationSteps as $step) {
                if (!is_array($step)) {
                    continue;
                }
                $attributes = $step['step_attributes'] ?? [];
                $path = is_array($attributes) ? (string) ($attributes['path'] ?? '') : '';
                if ($path !== '') {
                    $appliedByPath[$path] = (string) ($step['step_status'] ?? '');
                }
            }
        } elseif (isset($installation['steps']) && is_array($installation['steps'])) {
            foreach ($installation['steps'] as $step) {
                if (!is_array($step)) {
                    continue;
                }
                $path = (string) ($step['path'] ?? '');
                if ($path !== '') {
                    $appliedByPath[$path] = (string) ($step['status'] ?? '');
                }
            }
        }
    }

    // Immediate installer results use a flatter step shape than persisted ledger rows.
    if (is_array($executionResult) && is_array($executionResult['steps'] ?? null)) {
        foreach ($executionResult['steps'] as $step) {
            if (!is_array($step)) {
                continue;
            }
            $path = (string) ($step['path'] ?? '');
            if ($path !== '') {
                $appliedByPath[$path] = (string) ($step['status'] ?? '');
            }
        }
    }

    $rows = '';
    foreach ($plan as $index => $item) {
        $path = (string) ($item['path'] ?? '');
        $fileState = ($item['exists'] ?? false)
            ? '<span class="pill good">Ready</span>'
            : '<span class="pill bad">Missing</span>';

        $appliedStatus = $appliedByPath[$path] ?? '';
        if ($appliedStatus === 'completed') {
            $fileState = '<span class="pill good">Applied</span>';
        } elseif ($appliedStatus === 'skipped') {
            $fileState = '<span class="pill">Skipped</span>';
        } elseif ($appliedStatus === 'failed') {
            $fileState = '<span class="pill bad">Failed</span>';
        }

        $rows .= '<tr>'
            . '<td>' . $escape($index + 1) . '</td>'
            . '<td><code>' . $escape($item['cartridge']) . '</code></td>'
            . '<td>' . $escape($item['role']) . '</td>'
            . '<td>' . $escape((int) ($item['dependency_depth'] ?? 0)) . '</td>'
            . '<td><code>' . $escape($path) . '</code></td>'
            . '<td>' . $fileState . '</td>'
            . '</tr>';
    }

    if ($missing > 0) {
        $planState = '<span class="pill bad">' . $escape($missing) . ' files missing</span>';
    } elseif ($dependencyErrors !== []) {
        $planState = '<span class="pill bad">Dependency issue</span>';
    } else {
        $planState = '<span class="pill good">Install plan ready</span>';
    }

    if (($databaseStatus['connected'] ?? false) === true) {
        $databaseState = '<div class="status-line"><span class="status-dot good"></span><strong>Database connected</strong></div>'
            . '<p class="muted">' . $escape((int) ($databaseStatus['table_count'] ?? 0)) . ' public tables currently detected.</p>';
    } else {
        $databaseState = '<div class="status-line"><span class="status-dot bad"></span><strong>Database unavailable</strong></div>'
            . '<p class="muted">' . $escape($databaseStatus['error'] ?? 'DATABASE_URL could not be used.') . '</p>';
    }

    $latest = $databaseStatus['latest'] ?? null;
    if (is_array($latest)) {
        $latestStatus = (string) ($latest['installation_status'] ?? 'unknown');
        $latestClass = $latestStatus === 'completed' ? 'good' : ($latestStatus === 'failed' ? 'bad' : '');
        $databaseState .= '<div class="latest-installation">'
            . '<span class="muted">Latest Business Manager install</span>'
            . '<a href="/business-manager/application/data.html?installation=' . rawurlencode((string) ($latest['installation_id'] ?? '')) . '"><code>' . $escape($latest['installation_id'] ?? '') . '</code></a>'
            . '<span class="pill ' . $latestClass . '">' . $escape($latestStatus) . '</span>'
            . '</div>';
    }

    $blockingMessages = '';
    foreach ($dependencyErrors as $error) {
        $blockingMessages .= '<li>' . $escape($error) . '</li>';
    }
    if ($missing > 0) {
        $blockingMessages .= '<li>' . $escape($missing) . ' declared SQL files are missing.</li>';
    }
    if (($databaseStatus['connected'] ?? false) !== true) {
        $blockingMessages .= '<li>DATABASE_URL must connect successfully before installation can run.</li>';
    }

    $canInstall = $missing === 0
        && $dependencyErrors === []
        && ($databaseStatus['connected'] ?? false) === true;

    if ($canInstall) {
        $csrf = $escape($access->csrfToken('database.install'));
        $installAction = '<form method="post" action="/business-manager/application/data/install.html" class="inline-form">'
            . '<input type="hidden" name="csrf_token" value="' . $csrf . '">'
            . '<button type="submit">Install schema</button>'
            . '</form>';
    } else {
        $installAction = '<button type="button" disabled>Install schema</button>';
    }

    $blockingHtml = $blockingMessages === ''
        ? ''
        : '<div class="gate-alert install-blockers"><strong>Installation blocked</strong><ul>' . $blockingMessages . '</ul></div>';

    $resultHtml = '';
    if (is_array($installation)) {
        $resultStatus = (string) ($installation['installation_status'] ?? $installation['status'] ?? 'unknown');
        $resultOk = ($installation['ok'] ?? null) === true || $resultStatus === 'completed';
        $resultClass = $resultOk ? 'good' : 'bad';
        $resultTitle = $resultOk ? 'Installation completed' : 'Installation failed';
        $resultId = (string) ($installation['installation_id'] ?? '');

        $stepRows = '';
        $rcaBlocks = '';
        $resultSteps = $installation['steps'] ?? [];
        $completedCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        if (is_array($resultSteps)) {
            foreach ($resultSteps as $step) {
                if (!is_array($step)) {
                    continue;
                }

                $attributes = $step['step_attributes'] ?? [];
                $stepSummary = $step['step_summary'] ?? [];
                $cartridge = is_array($attributes) && array_key_exists('cartridge', $attributes)
                    ? (string) ($attributes['cartridge'] ?? '')
                    : (string) ($step['cartridge'] ?? '');
                $role = is_array($attributes) && array_key_exists('role', $attributes)
                    ? (string) ($attributes['role'] ?? '')
                    : (string) ($step['role'] ?? '');
                $path = is_array($attributes) && array_key_exists('path', $attributes)
                    ? (string) ($attributes['path'] ?? '')
                    : (string) ($step['path'] ?? '');
                $dependencyDepth = is_array($attributes) && array_key_exists('dependency_depth', $attributes)
                    ? (int) ($attributes['dependency_depth'] ?? 0)
                    : (int) ($step['dependency_depth'] ?? 0);
                $stepOrder = (int) ($step['step_order'] ?? $step['order'] ?? 0);
                $stepStatus = (string) ($step['step_status'] ?? $step['status'] ?? 'unknown');
                $stepError = (string) ($step['step_error'] ?? $step['error'] ?? '');
                $sourceHash = (string) ($step['step_sql_hash'] ?? $step['checksum'] ?? '');
                $durationMs = is_array($stepSummary)
                    ? (float) ($stepSummary['duration_ms'] ?? $step['duration_ms'] ?? 0.0)
                    : (float) ($step['duration_ms'] ?? 0.0);
                $note = is_array($stepSummary)
                    ? (string) ($stepSummary['note'] ?? $step['note'] ?? '')
                    : (string) ($step['note'] ?? '');
                $diagnostics = is_array($stepSummary) && is_array($stepSummary['diagnostics'] ?? null)
                    ? $stepSummary['diagnostics']
                    : (is_array($step['diagnostics'] ?? null) ? $step['diagnostics'] : []);

                if ($stepStatus === 'completed') {
                    $completedCount++;
                } elseif ($stepStatus === 'skipped') {
                    $skippedCount++;
                } elseif ($stepStatus === 'failed') {
                    $failedCount++;
                }

                $stepClass = $stepStatus === 'completed' ? 'good' : ($stepStatus === 'failed' ? 'bad' : '');
                $statusDetails = '';
                if ($stepError !== '') {
                    $statusDetails .= '<div class="step-error">' . $escape($stepError) . '</div>';
                }
                if ($note !== '') {
                    $statusDetails .= '<div class="step-note">' . $escape($note) . '</div>';
                }

                $stepRows .= '<tr>'
                    . '<td>' . $escape($stepOrder) . '</td>'
                    . '<td><code>' . $escape($cartridge) . '</code></td>'
                    . '<td>' . $escape($role) . '</td>'
                    . '<td>' . $escape($dependencyDepth) . '</td>'
                    . '<td><code>' . $escape($path) . '</code></td>'
                    . '<td>' . $escape(number_format($durationMs, 2)) . ' ms</td>'
                    . '<td><span class="pill ' . $stepClass . '">' . $escape($stepStatus) . '</span>' . $statusDetails . '</td>'
                    . '</tr>';

                if ($stepStatus === 'failed') {
                    $diagRows = '';
                    $diagnosticLabels = [
                        'sqlstate' => 'SQLSTATE',
                        'driver_code' => 'Driver code',
                        'driver_message' => 'Driver message',
                        'exception_class' => 'Exception class',
                        'exception_code' => 'Exception code',
                        'source_bytes' => 'Source bytes',
                        'prepared_bytes' => 'Prepared bytes',
                        'source_lines' => 'Source lines',
                        'prepared_lines' => 'Prepared lines',
                        'first_executable_line' => 'First executable line',
                        'error_line' => 'Reported error line',
                        'normalization_changed_sql' => 'SQL normalized',
                        'transaction_rolled_back' => 'Transaction rolled back',
                    ];
                    foreach ($diagnosticLabels as $key => $label) {
                        if (!array_key_exists($key, $diagnostics) || $diagnostics[$key] === null || $diagnostics[$key] === '') {
                            continue;
                        }
                        $value = is_bool($diagnostics[$key]) ? ($diagnostics[$key] ? 'yes' : 'no') : (string) $diagnostics[$key];
                        $diagRows .= '<div><dt>' . $escape($label) . '</dt><dd><code>' . $escape($value) . '</code></dd></div>';
                    }

                    $excerpt = (string) ($diagnostics['sql_excerpt'] ?? '');
                    $rcaLines = [
                        'Installation: ' . $resultId,
                        'Step order: ' . $stepOrder,
                        'Cartridge: ' . $cartridge,
                        'Role: ' . $role,
                        'Dependency level: ' . $dependencyDepth,
                        'File: ' . $path,
                        'Source SHA-256: ' . $sourceHash,
                        'Duration: ' . number_format($durationMs, 2) . ' ms',
                        'SQLSTATE: ' . (string) ($diagnostics['sqlstate'] ?? ''),
                        'Driver code: ' . (string) ($diagnostics['driver_code'] ?? ''),
                        'Driver message: ' . (string) ($diagnostics['driver_message'] ?? ''),
                        'Exception: ' . (string) ($diagnostics['exception_class'] ?? ''),
                        'Error: ' . $stepError,
                    ];
                    $rcaPacket = implode("\n", $rcaLines);

                    $rcaBlocks .= '<section class="rca-card">'
                        . '<div class="rca-head"><div><p class="kicker">Root-cause analysis</p><h3>Failed step ' . $escape($stepOrder) . '</h3></div><span class="pill bad">failed</span></div>'
                        . '<dl class="definition-grid compact rca-grid">'
                        . '<div><dt>Cartridge</dt><dd><code>' . $escape($cartridge) . '</code></dd></div>'
                        . '<div><dt>Role</dt><dd>' . $escape($role) . '</dd></div>'
                        . '<div><dt>Dependency level</dt><dd>' . $escape($dependencyDepth) . '</dd></div>'
                        . '<div><dt>SQL file</dt><dd><code>' . $escape($path) . '</code></dd></div>'
                        . '<div><dt>Source SHA-256</dt><dd><code>' . $escape($sourceHash) . '</code></dd></div>'
                        . '<div><dt>Duration</dt><dd>' . $escape(number_format($durationMs, 2)) . ' ms</dd></div>'
                        . $diagRows
                        . '</dl>'
                        . ($excerpt !== '' ? '<div class="rca-section"><h4>SQL excerpt</h4><pre>' . $escape($excerpt) . '</pre></div>' : '')
                        . '<div class="rca-section"><h4>RCA packet</h4><p class="muted">Copy this block when researching or sending the failure back for analysis.</p><pre>' . $escape($rcaPacket) . '</pre></div>'
                        . '</section>';
                }
            }
        }

        $summary = $installation['installation_summary'] ?? [];
        if (is_array($summary)) {
            $completedCount = (int) ($summary['steps_completed'] ?? $completedCount);
            $skippedCount = (int) ($summary['steps_skipped'] ?? $skippedCount);
            $failedCount = (int) ($summary['steps_failed'] ?? $failedCount);
        }

        $message = (string) ($installation['message'] ?? '');
        $installationError = (string) ($installation['installation_error'] ?? '');
        $errors = $installation['errors'] ?? [];
        $errorHtml = '';
        if ($installationError !== '') {
            $errorHtml .= '<p class="step-error">' . $escape($installationError) . '</p>';
        }
        if (is_array($errors) && $errors !== []) {
            foreach ($errors as $error) {
                if ((string) $error !== '') {
                    $errorHtml .= '<p class="step-error">' . $escape($error) . '</p>';
                }
            }
        }

        $resultHtml = '<section class="panel install-result ' . $resultClass . '">'
            . '<div class="panel-head"><div><p class="kicker">Database setup</p><h2>' . $resultTitle . '</h2></div><span class="pill ' . $resultClass . '">' . $escape($resultStatus) . '</span></div>'
            . ($resultId !== '' ? '<p><code>' . $escape($resultId) . '</code></p>' : '')
            . ($message !== '' ? '<p>' . $escape($message) . '</p>' : '')
            . $errorHtml
            . '<details class="installation-details"><summary>Show installation steps (' . $escape($completedCount) . ' completed' . ($skippedCount > 0 ? ', ' . $escape($skippedCount) . ' skipped' : '') . ($failedCount > 0 ? ', ' . $escape($failedCount) . ' failed' : '') . ')</summary>'
            . '<div class="table-wrap"><table><thead><tr><th>#</th><th>Cartridge</th><th>Role</th><th>Dependency level</th><th>File</th><th>Duration</th><th>Status</th></tr></thead><tbody>' . $stepRows . '</tbody></table></div>'
            . '</details>'
            . $rcaBlocks
            . '</section>';
    }

    $body = '
<section class="page-head">
  <p class="kicker">Application / Data</p>
  <h1>Data</h1>
  <p class="lede">Business Manager can initialize the PostgreSQL database directly from SQL declared by installed application cartridge manifests.</p>
</section>
' . $resultHtml . '
<div class="content-grid two-col data-status-grid">
  <section class="panel">
    <div class="panel-head"><div><p class="kicker">Connection</p><h2>Database status</h2></div></div>
    ' . $databaseState . '
  </section>
  <section class="panel">
    <div class="panel-head"><div><p class="kicker">Execution</p><h2>Install behavior</h2></div>' . $planState . '</div>
    <p>Business Manager executes three database phases: all <code>schema</code> files, then <code>constraints</code>, then <code>indexes</code>. Within each phase, cartridges run from the least dependent to the most dependent according to each manifest\'s <code>requires</code> graph.</p>
    <p class="muted">Setup is idempotent. Existing tables, indexes and constraints are skipped and installation continues. A non-duplicate SQL failure still stops the install and is recorded in Venny\'s <code>installations</code> and <code>steps</code> tables.</p>
  </section>
</div>
<section class="panel">
  <div class="panel-head">
    <div><p class="kicker">Install schema</p><h2>Manifest SQL plan</h2></div>
    <div>' . $planState . '</div>
  </div>
  ' . $blockingHtml . '
  <div class="action-row">
    ' . $installAction . '
    <button type="button" class="secondary danger" disabled title="Reset remains disabled until backup and reset semantics are implemented.">Reset</button>
  </div>
  <div class="table-wrap"><table><thead><tr><th>#</th><th>Cartridge</th><th>Role</th><th>Dependency level</th><th>Declared file</th><th>Status</th></tr></thead><tbody>' . $rows . '</tbody></table></div>
</section>';

    $render('Data | Venny I/O Business Manager', 'application', $body);
};

$renderCartridges = static function () use ($render, $registry, $escape): void {
    $manifests = $registry->all();
    $accordions = '';

    foreach ($manifests as $manifest) {
        $requires = $manifest['requires'] ?? [];
        $requiresHtml = $requires === []
            ? '<span class="muted">None</span>'
            : implode('', array_map(static fn (string $name): string => '<code class="tag">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</code>', $requires));

        $dependencies = $manifest['dependencies'] ?? [];
        $dependencyTags = [];
        if (is_array($dependencies)) {
            foreach ($dependencies as $dependencyType => $declared) {
                if (!is_array($declared)) {
                    continue;
                }
                foreach ($declared as $key => $value) {
                    if (is_int($key) && is_string($value)) {
                        $dependencyTags[] = '<code class="tag">' . $escape((string) $dependencyType . ': ' . $value) . '</code>';
                    } elseif (is_string($key)) {
                        $dependencyTags[] = '<code class="tag">' . $escape((string) $dependencyType . ': ' . $key . ' ' . (string) $value) . '</code>';
                    }
                }
            }
        }
        $dependenciesHtml = $dependencyTags === []
            ? '<span class="muted">None</span>'
            : implode('', $dependencyTags);

        $configuration = $manifest['configuration'] ?? [];
        $configurationHtml = is_array($configuration) && $configuration !== []
            ? implode('', array_map(static fn (string $name): string => '<code class="tag">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</code>', array_values(array_filter($configuration, 'is_string'))))
            : '<span class="muted">None</span>';

        $sql = $manifest['sql'] ?? [];
        $sqlHtml = '';
        if (is_array($sql) && $sql !== []) {
            foreach ($sql as $role => $metadata) {
                if (!is_array($metadata)) {
                    continue;
                }
                $status = ($metadata['exists'] ?? false) ? 'Ready' : 'Missing';
                $class = ($metadata['exists'] ?? false) ? 'good' : 'bad';
                $sqlHtml .= '<div class="file-row"><span><strong>' . $escape($role) . '</strong><code>' . $escape($metadata['path'] ?? '') . '</code></span><span class="pill ' . $class . '">' . $status . '</span></div>';
            }
        } else {
            $sqlHtml = '<p class="muted">No SQL files declared.</p>';
        }

        $manifestExists = ($manifest['manifest_exists'] ?? false) === true;
        $manifestStatus = $manifestExists ? '<span class="pill good">Installed</span>' : '<span class="pill bad">Manifest missing</span>';
        $version = $escape($manifest['version'] ?? 'Unavailable');
        $type = $escape($manifest['type'] ?? 'unknown');
        $domain = $escape($manifest['domain'] ?? 'unknown');
        $name = $escape($manifest['name'] ?? '');

        $accordions .= '<details class="cartridge-accordion" name="business-manager-cartridges">'
            . '<summary class="cartridge-summary">'
            . '<div class="cartridge-summary-copy"><p class="kicker">' . $type . ' / ' . $domain . '</p><h2><code>' . $name . '</code></h2></div>'
            . '<div class="cartridge-summary-meta"><span class="cartridge-version">v' . $version . '</span>' . $manifestStatus . '<span class="accordion-marker" aria-hidden="true"></span></div>'
            . '</summary>'
            . '<div class="cartridge-details">'
            . '<dl class="definition-grid compact">'
            . '<div><dt>Provider</dt><dd>' . $escape($manifest['provider'] ?? 'Unavailable') . '</dd></div>'
            . '<div><dt>Version</dt><dd>' . $version . '</dd></div>'
            . '<div><dt>Routes</dt><dd>' . (($manifest['routes_declared'] ?? false) ? (($manifest['routes_exists'] ?? false) ? 'Ready' : 'Missing') : 'Not declared') . '</dd></div>'
            . '<div><dt>Manifest</dt><dd><code>' . $escape($manifest['manifest_path'] ?? '') . '</code></dd></div>'
            . '</dl>'
            . '<div class="subsection"><h3>Description</h3><p>' . $escape($manifest['description'] ?? 'No description declared.') . '</p></div>'
            . '<div class="subsection"><h3>Cartridge dependencies</h3><div class="tag-row">' . $requiresHtml . '</div></div>'
            . '<div class="subsection"><h3>Runtime dependencies</h3><div class="tag-row">' . $dependenciesHtml . '</div></div>'
            . '<div class="subsection"><h3>Configuration keys</h3><div class="tag-row">' . $configurationHtml . '</div></div>'
            . '<div class="subsection"><h3>SQL declarations</h3>' . $sqlHtml . '</div>'
            . '</div>'
            . '</details>';
    }

    if ($accordions === '') {
        $accordions = '<section class="panel"><p class="muted">No installed cartridges were found.</p></section>';
    }

    $body = '
<section class="page-head">
  <p class="kicker">Application / Cartridges</p>
  <h1>Cartridges</h1>
  <p class="lede">Installed cartridges are collapsed by default. Expand a cartridge to inspect its current <code>cartridge.php</code> manifest metadata.</p>
</section>
<section class="cartridge-list">' . $accordions . '</section>';

    $render('Cartridges | Venny I/O Business Manager', 'application', $body);
};

$assetRoot = __DIR__ . '/public';

$serveAsset = static function (string $path, string $contentType): void {
    if (!is_file($path)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo 'asset not found';
        return;
    }

    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
};

$router->get('#^/business-manager/assets/style\.css$#', static function () use ($serveAsset, $assetRoot): void {
    $serveAsset($assetRoot . '/style.css', 'text/css; charset=utf-8');
});

$router->get('#^/business-manager/assets/logo\.png$#', static function () use ($serveAsset, $assetRoot): void {
    $serveAsset($assetRoot . '/logo.png', 'image/png');
});

$router->get('#^/$#', static function () use ($redirect): void { $redirect('/business-manager/welcome.html'); });
$router->get('#^/business-manager$#', static function () use ($redirect): void { $redirect('/business-manager/welcome.html'); });
$router->get('#^/business-manager\.html$#', static function () use ($redirect): void { $redirect('/business-manager/welcome.html'); });

$router->get('#^/business-manager/login\.html$#', static function (Request $request) use ($access, $redirect, $renderLogin, $safeNextPath): void {
    if ($access->isAuthorized()) {
        $redirect($safeNextPath($request->query['next'] ?? null));
        return;
    }

    $renderLogin(null, $safeNextPath($request->query['next'] ?? null));
});

$router->post('#^/business-manager/login\.html$#', static function (Request $request) use ($access, $redirect, $renderLogin, $safeNextPath): void {
    $input = $request->input();
    $next = $safeNextPath($input['next'] ?? null);

    $provided = '';
    $wordInput = $input['passphrase_words'] ?? null;

    if (is_array($wordInput)) {
        $words = array_map(
            static fn (mixed $word): string => is_string($word) ? trim($word) : '',
            array_slice($wordInput, 0, 5)
        );
        $provided = implode(' ', $words);
    } elseif (is_string($input['passphrase'] ?? null)) {
        // Backward-compatible fallback for existing clients posting one field.
        $provided = (string) $input['passphrase'];
    }

    if ($access->verify($provided)) {
        $access->grant();
        $redirect($next);
        return;
    }

    if ($access->configured()) {
        usleep(500000);
        $renderLogin('Passphrase not accepted.', $next);
        return;
    }

    $renderLogin(null, $next);
});

$router->post('#^/business-manager/logout\.html$#', static function () use ($access, $redirect): void {
    $access->revoke();
    $redirect('/business-manager/login.html');
});

$router->get('#^/business-manager/welcome\.html$#', $protect($renderWelcome));
$router->get('#^/business-manager/environment\.html$#', $protect($renderEnvironment));
$router->get('#^/business-manager/environment/infrastructure\.html$#', $protect($renderInfrastructure));
$router->get('#^/business-manager/environment/variables\.html$#', $protect($renderVariables));
$router->get('#^/business-manager/application\.html$#', $protect($renderApplication));
$router->post('#^/business-manager/application/data/install\.html$#', static function (Request $request) use ($access, $redirect, $renderData, $installer): void {
    if (!$access->isAuthorized()) {
        $redirect('/business-manager/login.html?next=' . rawurlencode('/business-manager/application/data.html'));
        return;
    }

    $input = $request->input();
    $csrfToken = is_string($input['csrf_token'] ?? null) ? (string) $input['csrf_token'] : '';

    if (!$access->verifyCsrfToken('database.install', $csrfToken)) {
        http_response_code(403);
        $renderData($request, [
            'ok' => false,
            'installation_id' => null,
            'recorded' => false,
            'status' => 'failed',
            'message' => 'Database installation request was rejected.',
            'errors' => ['The Business Manager security token was invalid or expired. Reload the Data page and try again.'],
            'steps' => [],
            'duration_ms' => 0.0,
        ]);
        return;
    }

    $result = $installer->install();
    $installationId = is_string($result['installation_id'] ?? null) ? (string) $result['installation_id'] : '';

    if (($result['recorded'] ?? false) === true && $installationId !== '') {
        $redirect('/business-manager/application/data.html?installation=' . rawurlencode($installationId));
        return;
    }

    $renderData($request, $result);
});

$router->get('#^/business-manager/application/data\.html$#', $protect($renderData));
$router->get('#^/business-manager/application/cartridges\.html$#', $protect($renderCartridges));
