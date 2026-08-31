<?php

declare(strict_types=1);

namespace VennyIO\BusinessManager;

use PDO;
use PDOException;
use Throwable;
use VennyIO\Support\Database;

final class DatabaseInstaller
{
    private const LOCK_NAME = 'venny_business_manager_database_setup';

    private const CONSTRAINT_HELPER = <<<'SQL'
CREATE OR REPLACE FUNCTION venny_add_constraint(
    p_table_name TEXT,
    p_constraint_name TEXT,
    p_constraint_definition TEXT
) RETURNS void AS $$
BEGIN
    IF to_regclass(p_table_name) IS NULL THEN
        RAISE NOTICE 'Skipping constraint %. Table % does not exist.', p_constraint_name, p_table_name;
        RETURN;
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint c
        JOIN pg_class t ON t.oid = c.conrelid
        JOIN pg_namespace n ON n.oid = t.relnamespace
        WHERE c.conname = p_constraint_name
          AND t.relname = p_table_name
          AND n.nspname = current_schema()
    ) THEN
        EXECUTE format('ALTER TABLE %I ADD CONSTRAINT %I %s',
            p_table_name,
            p_constraint_name,
            p_constraint_definition
        );
    END IF;
END;
$$ LANGUAGE plpgsql;
SQL;

    public function __construct(
        private string $rootPath,
        private ManifestRegistry $registry
    ) {
    }

    /**
     * @return array{connected:bool,table_count:int,ledger_available:bool,latest:?array,error:?string}
     */
    public function status(): array
    {
        if (trim((string) getenv('DATABASE_URL')) === '') {
            return [
                'connected' => false,
                'table_count' => 0,
                'ledger_available' => false,
                'latest' => null,
                'error' => 'DATABASE_URL is not configured.',
            ];
        }

        try {
            $pdo = Database::connection();
            $tableCount = (int) $pdo->query(
                "SELECT count(*) FROM information_schema.tables WHERE table_schema = current_schema() AND table_type = 'BASE TABLE'"
            )->fetchColumn();

            $ledgerAvailable = $this->ledgerAvailable($pdo);
            $latest = $ledgerAvailable ? $this->latestInstallation($pdo) : null;

            return [
                'connected' => true,
                'table_count' => $tableCount,
                'ledger_available' => $ledgerAvailable,
                'latest' => $latest,
                'error' => null,
            ];
        } catch (Throwable $throwable) {
            return [
                'connected' => false,
                'table_count' => 0,
                'ledger_available' => false,
                'latest' => null,
                'error' => $throwable->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function installation(string $installationId): ?array
    {
        if ($installationId === '' || strlen($installationId) > 64) {
            return null;
        }

        try {
            $pdo = Database::connection();
            if (!$this->ledgerAvailable($pdo)) {
                return null;
            }

            $statement = $pdo->prepare('SELECT * FROM installations WHERE installation_id = :installation_id LIMIT 1');
            $statement->execute(['installation_id' => $installationId]);
            $installation = $statement->fetch();

            if (!is_array($installation)) {
                return null;
            }

            $stepsStatement = $pdo->prepare(<<<'SQL'
SELECT
    step_id,
    step_name,
    step_order,
    step_status,
    step_sql_hash,
    step_started_at,
    step_finished_at,
    step_error,
    step_attributes,
    step_summary
FROM steps
WHERE step_attributes ->> 'installation_id' = :installation_id
ORDER BY step_order ASC, time_started ASC
SQL);
            $stepsStatement->execute(['installation_id' => $installationId]);
            $steps = $stepsStatement->fetchAll();

            foreach (['installation_attributes', 'installation_modules', 'installation_summary'] as $jsonField) {
                $installation[$jsonField] = $this->decodeJson($installation[$jsonField] ?? null);
            }

            foreach ($steps as &$step) {
                $step['step_attributes'] = $this->decodeJson($step['step_attributes'] ?? null);
                $step['step_summary'] = $this->decodeJson($step['step_summary'] ?? null);
            }
            unset($step);

            $installation['steps'] = $steps;
            return $installation;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Execute every declared application-cartridge SQL file against DATABASE_URL.
     * Each file is atomic. Installation and step records are written to the existing
     * Venny platform installation ledger as soon as those tables are available.
     *
     * @return array<string, mixed>
     */
    public function install(): array
    {
        $plan = $this->registry->schemaPlan();
        $dependencyErrors = $this->registry->dependencyErrors();
        $missing = array_values(array_filter($plan, static fn (array $item): bool => ($item['exists'] ?? false) !== true));

        if ($dependencyErrors !== []) {
            return $this->failedResult('Cartridge dependencies are not satisfied.', $dependencyErrors);
        }

        if ($missing !== []) {
            $paths = array_map(static fn (array $item): string => (string) ($item['path'] ?? ''), $missing);
            return $this->failedResult('One or more declared SQL files are missing.', $paths);
        }

        if ($plan === []) {
            return $this->failedResult('No application-cartridge SQL files are declared.', []);
        }

        $pdo = null;
        $lockAcquired = false;
        $installationId = $this->makeId('install_bm');
        $startedAt = microtime(true);
        $recorded = false;
        $results = [];
        $modules = array_values(array_unique(array_map(
            static fn (array $item): string => (string) ($item['cartridge'] ?? ''),
            $plan
        )));
        $modules = array_values(array_filter($modules, static fn (string $value): bool => $value !== ''));

        try {
            $pdo = Database::connection();
            $lockAcquired = $this->acquireLock($pdo);

            if (!$lockAcquired) {
                return $this->failedResult('Another Business Manager database installation is already running.', []);
            }

            if ($this->ledgerAvailable($pdo)) {
                $this->createInstallation($pdo, $installationId, $modules);
                $recorded = true;
            }

            foreach ($plan as $index => $item) {
                $absolutePath = $this->absoluteSqlPath((string) ($item['path'] ?? ''));
                if ($absolutePath === null) {
                    throw new \RuntimeException('SQL path is outside the Venny cartridge directory: ' . (string) ($item['path'] ?? ''));
                }

                $rawSql = file_get_contents($absolutePath);
                if (!is_string($rawSql)) {
                    throw new \RuntimeException('Unable to read SQL file: ' . (string) ($item['path'] ?? ''));
                }

                $role = (string) ($item['role'] ?? 'sql');
                $sql = $this->prepareSql($rawSql, $role);
                $checksum = hash('sha256', $rawSql);
                $preparedChecksum = hash('sha256', $sql);
                $stepStartedAt = microtime(true);
                $sqlDiagnostics = $this->sqlDiagnostics($rawSql, $sql, $item);

                if (($sqlDiagnostics['has_executable_sql'] ?? false) !== true) {
                    $results[] = [
                        'order' => $index + 1,
                        'cartridge' => (string) ($item['cartridge'] ?? ''),
                        'role' => $role,
                        'dependency_depth' => (int) ($item['dependency_depth'] ?? 0),
                        'path' => (string) ($item['path'] ?? ''),
                        'checksum' => $checksum,
                        'prepared_checksum' => $preparedChecksum,
                        'status' => 'skipped',
                        'error' => null,
                        'note' => 'No executable SQL found. The file contains comments and/or whitespace only.',
                        'duration_ms' => $this->elapsedMs($stepStartedAt),
                        'diagnostics' => $sqlDiagnostics,
                        'persisted' => false,
                    ];

                    if (!$recorded && $this->ledgerAvailable($pdo)) {
                        $this->createInstallation($pdo, $installationId, $modules);
                        $recorded = true;
                    }

                    if ($recorded) {
                        $this->persistUnrecordedSteps($pdo, $installationId, $results);
                    }

                    continue;
                }

                try {
                    $pdo->beginTransaction();
                    $pdo->exec($sql);
                    $pdo->commit();

                    $results[] = [
                        'order' => $index + 1,
                        'cartridge' => (string) ($item['cartridge'] ?? ''),
                        'role' => $role,
                        'dependency_depth' => (int) ($item['dependency_depth'] ?? 0),
                        'path' => (string) ($item['path'] ?? ''),
                        'checksum' => $checksum,
                        'prepared_checksum' => $preparedChecksum,
                        'status' => 'completed',
                        'error' => null,
                        'duration_ms' => $this->elapsedMs($stepStartedAt),
                        'diagnostics' => $sqlDiagnostics,
                        'persisted' => false,
                    ];

                    if (!$recorded && $this->ledgerAvailable($pdo)) {
                        $this->createInstallation($pdo, $installationId, $modules);
                        $recorded = true;
                    }

                    if ($recorded) {
                        $this->persistUnrecordedSteps($pdo, $installationId, $results);
                    }
                } catch (Throwable $throwable) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    if ($this->isExistingObjectError($throwable)) {
                        $results[] = [
                            'order' => $index + 1,
                            'cartridge' => (string) ($item['cartridge'] ?? ''),
                            'role' => $role,
                            'dependency_depth' => (int) ($item['dependency_depth'] ?? 0),
                            'path' => (string) ($item['path'] ?? ''),
                            'checksum' => $checksum,
                            'prepared_checksum' => $preparedChecksum,
                            'status' => 'skipped',
                            'error' => null,
                            'note' => 'Existing database object detected; installation continued.',
                            'duration_ms' => $this->elapsedMs($stepStartedAt),
                            'diagnostics' => $sqlDiagnostics,
                            'persisted' => false,
                        ];

                        if (!$recorded && $this->ledgerAvailable($pdo)) {
                            $this->createInstallation($pdo, $installationId, $modules);
                            $recorded = true;
                        }

                        if ($recorded) {
                            $this->persistUnrecordedSteps($pdo, $installationId, $results);
                        }

                        continue;
                    }

                    $failureDiagnostics = array_merge(
                        $sqlDiagnostics,
                        $this->exceptionDiagnostics($throwable, $sql)
                    );
                    $failureDiagnostics['transaction_rolled_back'] = true;

                    $results[] = [
                        'order' => $index + 1,
                        'cartridge' => (string) ($item['cartridge'] ?? ''),
                        'role' => $role,
                        'dependency_depth' => (int) ($item['dependency_depth'] ?? 0),
                        'path' => (string) ($item['path'] ?? ''),
                        'checksum' => $checksum,
                        'prepared_checksum' => $preparedChecksum,
                        'status' => 'failed',
                        'error' => $throwable->getMessage(),
                        'duration_ms' => $this->elapsedMs($stepStartedAt),
                        'diagnostics' => $failureDiagnostics,
                        'persisted' => false,
                    ];

                    if (!$recorded && $this->ledgerAvailable($pdo)) {
                        $this->createInstallation($pdo, $installationId, $modules);
                        $recorded = true;
                    }

                    if ($recorded) {
                        $this->persistUnrecordedSteps($pdo, $installationId, $results);
                        $this->finishInstallation($pdo, $installationId, 'failed', $results, $throwable->getMessage());
                    }

                    return [
                        'ok' => false,
                        'installation_id' => $installationId,
                        'recorded' => $recorded,
                        'status' => 'failed',
                        'message' => 'Database installation stopped when a non-idempotent SQL step failed.',
                        'errors' => [$throwable->getMessage()],
                        'steps' => $results,
                        'duration_ms' => $this->elapsedMs($startedAt),
                    ];
                }
            }

            try {
                $pdo->exec('DROP FUNCTION IF EXISTS venny_add_constraint(TEXT, TEXT, TEXT)');
            } catch (Throwable) {
                // Helper cleanup is non-critical and must not turn a completed installation into a failure.
            }

            if ($recorded) {
                $this->finishInstallation($pdo, $installationId, 'completed', $results, null);
            }

            return [
                'ok' => true,
                'installation_id' => $installationId,
                'recorded' => $recorded,
                'status' => 'completed',
                'message' => 'Database installation completed.',
                'errors' => [],
                'steps' => $results,
                'duration_ms' => $this->elapsedMs($startedAt),
            ];
        } catch (Throwable $throwable) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($pdo instanceof PDO) {
                try {
                    if (!$recorded && $this->ledgerAvailableSafely($pdo)) {
                        $this->createInstallation($pdo, $installationId, $modules);
                        $recorded = true;
                    }

                    if ($recorded) {
                        $this->persistUnrecordedSteps($pdo, $installationId, $results);
                        $this->finishInstallation($pdo, $installationId, 'failed', $results, $throwable->getMessage());
                    }
                } catch (Throwable) {
                    // Preserve the original installer failure if the ledger cannot be written.
                }
            }

            return [
                'ok' => false,
                'installation_id' => $installationId,
                'recorded' => $recorded,
                'status' => 'failed',
                'message' => 'Database installation could not start or complete.',
                'errors' => [$throwable->getMessage()],
                'steps' => $results,
                'duration_ms' => $this->elapsedMs($startedAt),
            ];
        } finally {
            if ($pdo instanceof PDO && $lockAcquired) {
                $this->releaseLock($pdo);
            }
        }
    }

    /**
     * @param array<int, string> $details
     * @return array<string, mixed>
     */
    private function failedResult(string $message, array $details): array
    {
        return [
            'ok' => false,
            'installation_id' => null,
            'recorded' => false,
            'status' => 'failed',
            'message' => $message,
            'errors' => $details,
            'steps' => [],
            'duration_ms' => 0.0,
        ];
    }

    private function absoluteSqlPath(string $relativePath): ?string
    {
        if ($relativePath === '') {
            return null;
        }

        $root = realpath($this->rootPath . '/cartridges');
        $path = realpath($this->rootPath . '/' . ltrim($relativePath, '/'));

        if ($root === false || $path === false || !is_file($path)) {
            return null;
        }

        $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
        $normalizedPath = str_replace('\\', '/', $path);

        if (!str_starts_with($normalizedPath, $normalizedRoot . '/')) {
            return null;
        }

        return $path;
    }

    private function prepareSql(string $sql, string $role): string
    {
        // Spreadsheet/export workflows can occasionally wrap a multi-line SQL
        // comment banner in a double-quoted string. PostgreSQL treats that as an
        // invalid quoted identifier. Normalize only comment-only blocks so harmless
        // formatting artifacts cannot block an otherwise valid cartridge install.
        $sql = $this->normalizeQuotedCommentBlocks($sql);

        if ($role === 'schema') {
            // Venny schema installs are idempotent. Cartridge SQL already follows
            // this convention, but normalize ordinary CREATE TABLE statements so
            // a future cartridge cannot turn an existing table into an install stop.
            return preg_replace(
                '/\bCREATE\s+TABLE\s+(?!IF\s+NOT\s+EXISTS\b)/i',
                'CREATE TABLE IF NOT EXISTS ',
                $sql
            ) ?? $sql;
        }

        if ($role === 'indexes') {
            // PostgreSQL indexes share the relation namespace with tables. Apply
            // IF NOT EXISTS to both normal and UNIQUE indexes.
            $sql = preg_replace(
                '/\bCREATE\s+UNIQUE\s+INDEX\s+(?!IF\s+NOT\s+EXISTS\b)/i',
                'CREATE UNIQUE INDEX IF NOT EXISTS ',
                $sql
            ) ?? $sql;

            return preg_replace(
                '/\bCREATE\s+INDEX\s+(?!IF\s+NOT\s+EXISTS\b)/i',
                'CREATE INDEX IF NOT EXISTS ',
                $sql
            ) ?? $sql;
        }

        if ($role === 'constraints') {
            if (!$this->hasExecutableSql($sql)) {
                return $sql;
            }

            return $this->prepareConstraintSql($sql);
        }

        return $sql;
    }


    private function normalizeQuotedCommentBlocks(string $sql): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $sql);
        if (!is_array($lines)) {
            return $sql;
        }

        $insideQuotedComment = false;

        foreach ($lines as $index => $line) {
            if (!$insideQuotedComment) {
                if (preg_match('/^(\s*)"(--.*)$/', $line, $matches) === 1) {
                    $line = $matches[1] . $matches[2];
                    $insideQuotedComment = true;
                }
            }

            if ($insideQuotedComment) {
                // Only close on a comment line whose final non-whitespace character
                // is the wrapping double quote. SQL inside the block is never changed.
                if (preg_match('/^(\s*--.*)"\s*$/', $line, $matches) === 1) {
                    $line = $matches[1];
                    $insideQuotedComment = false;
                } elseif (preg_match('/^\s*--/', $line) !== 1) {
                    // This was not a pure quoted comment block. Stop normalization
                    // rather than guessing about executable SQL.
                    $insideQuotedComment = false;
                }
            }

            $lines[$index] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function sqlDiagnostics(string $rawSql, string $preparedSql, array $item): array
    {
        return [
            'dependency_depth' => (int) ($item['dependency_depth'] ?? 0),
            'source_bytes' => strlen($rawSql),
            'prepared_bytes' => strlen($preparedSql),
            'source_lines' => $this->lineCount($rawSql),
            'prepared_lines' => $this->lineCount($preparedSql),
            'source_sha256' => hash('sha256', $rawSql),
            'prepared_sha256' => hash('sha256', $preparedSql),
            'normalization_changed_sql' => $rawSql !== $preparedSql,
            'has_executable_sql' => $this->hasExecutableSql($preparedSql),
            'first_executable_line' => $this->firstExecutableLine($preparedSql),
        ];
    }

    private function hasExecutableSql(string $sql): bool
    {
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql) ?? $sql;
        $sql = preg_replace('/^[\t ]*--.*(?:\R|$)/m', '', $sql) ?? $sql;

        return trim($sql, " \t\n\r\0\x0B;") !== '';
    }

    private function firstExecutableLine(string $sql): ?int
    {
        $lines = preg_split('/\r\n|\r|\n/', $sql);
        if (!is_array($lines)) {
            return null;
        }

        $insideBlockComment = false;
        foreach ($lines as $index => $line) {
            $candidate = trim((string) $line);
            if ($candidate === '') {
                continue;
            }

            if ($insideBlockComment) {
                if (str_contains($candidate, '*/')) {
                    $candidate = trim((string) substr($candidate, (int) strpos($candidate, '*/') + 2));
                    $insideBlockComment = false;
                    if ($candidate === '') {
                        continue;
                    }
                } else {
                    continue;
                }
            }

            if (str_starts_with($candidate, '/*')) {
                if (!str_contains($candidate, '*/')) {
                    $insideBlockComment = true;
                    continue;
                }
                $candidate = trim((string) preg_replace('/^\/\*.*?\*\//', '', $candidate));
                if ($candidate === '') {
                    continue;
                }
            }

            if (str_starts_with($candidate, '--') || trim($candidate, ';') === '') {
                continue;
            }

            return $index + 1;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function exceptionDiagnostics(Throwable $throwable, string $preparedSql): array
    {
        $diagnostics = [
            'exception_class' => get_class($throwable),
            'exception_code' => (string) $throwable->getCode(),
            'exception_message' => $throwable->getMessage(),
            'sqlstate' => null,
            'driver_code' => null,
            'driver_message' => null,
            'error_line' => null,
            'sql_excerpt' => null,
        ];

        if ($throwable instanceof PDOException && is_array($throwable->errorInfo ?? null)) {
            $errorInfo = $throwable->errorInfo;
            $diagnostics['sqlstate'] = isset($errorInfo[0]) ? (string) $errorInfo[0] : null;
            $diagnostics['driver_code'] = isset($errorInfo[1]) ? (string) $errorInfo[1] : null;
            $diagnostics['driver_message'] = isset($errorInfo[2]) ? (string) $errorInfo[2] : null;
        }

        if ($diagnostics['sqlstate'] === null && preg_match('/^[0-9A-Z]{5}$/i', (string) $throwable->getCode()) === 1) {
            $diagnostics['sqlstate'] = strtoupper((string) $throwable->getCode());
        }

        $messageForLine = trim((string) ($diagnostics['driver_message'] ?? ''));
        if ($messageForLine === '') {
            $messageForLine = $throwable->getMessage();
        }

        if (preg_match('/\bLINE\s+(\d+)\s*:/i', $messageForLine, $matches) === 1) {
            $line = max(1, (int) $matches[1]);
            $diagnostics['error_line'] = $line;
            $diagnostics['sql_excerpt'] = $this->sqlExcerpt($preparedSql, $line, 4);
        }

        return $diagnostics;
    }

    private function sqlExcerpt(string $sql, int $centerLine, int $radius): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $sql);
        if (!is_array($lines) || $lines === []) {
            return '';
        }

        $start = max(1, $centerLine - $radius);
        $end = min(count($lines), $centerLine + $radius);
        $output = [];

        for ($lineNumber = $start; $lineNumber <= $end; $lineNumber++) {
            $marker = $lineNumber === $centerLine ? '>>' : '  ';
            $output[] = sprintf('%s %5d | %s', $marker, $lineNumber, (string) ($lines[$lineNumber - 1] ?? ''));
        }

        return implode("\n", $output);
    }

    private function lineCount(string $sql): int
    {
        if ($sql === '') {
            return 0;
        }

        return substr_count(str_replace(["\r\n", "\r"], "\n", $sql), "\n") + 1;
    }

    private function isExistingObjectError(Throwable $throwable): bool
    {
        $sqlState = strtoupper((string) $throwable->getCode());

        // 42P07 = duplicate_table / duplicate relation (also covers indexes)
        // 42710 = duplicate_object (including duplicate constraints)
        return in_array($sqlState, ['42P07', '42710'], true);
    }

    private function prepareConstraintSql(string $sql): string
    {
        $sql = preg_replace(
            '/^\s*DROP\s+FUNCTION\s+IF\s+EXISTS\s+venny_add_constraint\s*\(\s*TEXT\s*,\s*TEXT\s*,\s*TEXT\s*\)\s*;\s*$/mi',
            '',
            $sql
        ) ?? $sql;

        // Two legacy aggregate constraint files include a terminal COMMIT without
        // owning the transaction. The installer supplies the per-file transaction.
        $sql = preg_replace('/^\s*COMMIT\s*;\s*$/mi', '', $sql) ?? $sql;

        return self::CONSTRAINT_HELPER . "\n\n" . $sql;
    }

    private function acquireLock(PDO $pdo): bool
    {
        $statement = $pdo->prepare('SELECT pg_try_advisory_lock(hashtext(:lock_name))');
        $statement->execute(['lock_name' => self::LOCK_NAME]);
        return (bool) $statement->fetchColumn();
    }

    private function releaseLock(PDO $pdo): void
    {
        try {
            $statement = $pdo->prepare('SELECT pg_advisory_unlock(hashtext(:lock_name))');
            $statement->execute(['lock_name' => self::LOCK_NAME]);
        } catch (Throwable) {
            // Connection cleanup will also release a session advisory lock.
        }
    }

    private function ledgerAvailable(PDO $pdo): bool
    {
        $statement = $pdo->query("SELECT to_regclass('installations') IS NOT NULL AND to_regclass('steps') IS NOT NULL");
        return (bool) $statement->fetchColumn();
    }

    private function ledgerAvailableSafely(PDO $pdo): bool
    {
        try {
            return $this->ledgerAvailable($pdo);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<int, string> $modules
     */
    private function createInstallation(PDO $pdo, string $installationId, array $modules): void
    {
        $statement = $pdo->prepare(<<<'SQL'
INSERT INTO installations (
    installation_id,
    installation_attributes,
    installation_experience,
    installation_modules,
    installation_status,
    installation_summary
) VALUES (
    :installation_id,
    CAST(:installation_attributes AS jsonb),
    :installation_experience,
    CAST(:installation_modules AS jsonb),
    'running',
    '{}'::jsonb
)
SQL);

        $statement->execute([
            'installation_id' => $installationId,
            'installation_attributes' => json_encode(['source' => 'business_manager'], JSON_UNESCAPED_SLASHES),
            'installation_experience' => 'business_manager_database_setup',
            'installation_modules' => json_encode($modules, JSON_UNESCAPED_SLASHES),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $results
     */
    private function persistUnrecordedSteps(PDO $pdo, string $installationId, array &$results): void
    {
        foreach ($results as &$result) {
            if (($result['persisted'] ?? false) === true) {
                continue;
            }

            $this->createStep($pdo, $installationId, $result);
            $result['persisted'] = true;
        }
        unset($result);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function createStep(PDO $pdo, string $installationId, array $result): void
    {
        $stepId = $this->makeId('step_bm');
        $attributes = [
            'source' => 'business_manager',
            'installation_id' => $installationId,
            'cartridge' => (string) ($result['cartridge'] ?? ''),
            'role' => (string) ($result['role'] ?? ''),
            'path' => (string) ($result['path'] ?? ''),
            'dependency_depth' => (int) ($result['dependency_depth'] ?? 0),
        ];
        $summary = [
            'duration_ms' => (float) ($result['duration_ms'] ?? 0.0),
            'prepared_sql_hash' => (string) ($result['prepared_checksum'] ?? ''),
        ];
        if (isset($result['diagnostics']) && is_array($result['diagnostics'])) {
            $summary['diagnostics'] = $result['diagnostics'];
        }
        if (isset($result['note']) && is_string($result['note']) && $result['note'] !== '') {
            $summary['note'] = $result['note'];
        }

        $statement = $pdo->prepare(<<<'SQL'
INSERT INTO steps (
    step_id,
    step_attributes,
    step_name,
    step_order,
    step_status,
    step_sql_hash,
    step_started_at,
    step_finished_at,
    step_error,
    step_summary
) VALUES (
    :step_id,
    CAST(:step_attributes AS jsonb),
    :step_name,
    :step_order,
    :step_status,
    :step_sql_hash,
    now(),
    now(),
    :step_error,
    CAST(:step_summary AS jsonb)
)
SQL);

        $statement->execute([
            'step_id' => $stepId,
            'step_attributes' => json_encode($attributes, JSON_UNESCAPED_SLASHES),
            'step_name' => trim((string) ($result['cartridge'] ?? '') . ' ' . (string) ($result['role'] ?? '')),
            'step_order' => (int) ($result['order'] ?? 0),
            'step_status' => (string) ($result['status'] ?? 'failed'),
            'step_sql_hash' => (string) ($result['checksum'] ?? ''),
            'step_error' => $result['error'] ?? null,
            'step_summary' => json_encode($summary, JSON_UNESCAPED_SLASHES),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $results
     */
    private function finishInstallation(PDO $pdo, string $installationId, string $status, array $results, ?string $error): void
    {
        $completed = count(array_filter($results, static fn (array $result): bool => ($result['status'] ?? '') === 'completed'));
        $failed = count(array_filter($results, static fn (array $result): bool => ($result['status'] ?? '') === 'failed'));
        $skipped = count(array_filter($results, static fn (array $result): bool => ($result['status'] ?? '') === 'skipped'));
        $summary = [
            'steps_total' => count($results),
            'steps_completed' => $completed,
            'steps_skipped' => $skipped,
            'steps_failed' => $failed,
        ];

        $statement = $pdo->prepare(<<<'SQL'
UPDATE installations
SET installation_status = :installation_status,
    installation_finished_at = now(),
    installation_error = :installation_error,
    installation_summary = CAST(:installation_summary AS jsonb),
    time_updated = now()
WHERE installation_id = :installation_id
SQL);
        $statement->execute([
            'installation_status' => $status,
            'installation_error' => $error,
            'installation_summary' => json_encode($summary, JSON_UNESCAPED_SLASHES),
            'installation_id' => $installationId,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function latestInstallation(PDO $pdo): ?array
    {
        $statement = $pdo->query(<<<'SQL'
SELECT
    installation_id,
    installation_status,
    installation_started_at,
    installation_finished_at,
    installation_error,
    installation_summary
FROM installations
WHERE installation_experience = 'business_manager_database_setup'
ORDER BY installation_started_at DESC
LIMIT 1
SQL);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        $row['installation_summary'] = $this->decodeJson($row['installation_summary'] ?? null);
        return $row;
    }

    private function makeId(string $prefix): string
    {
        return $prefix . '_' . gmdate('YmdHis') . '_' . bin2hex(random_bytes(4));
    }

    private function elapsedMs(float $startedAt): float
    {
        return round((microtime(true) - $startedAt) * 1000, 2);
    }

    private function decodeJson(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
