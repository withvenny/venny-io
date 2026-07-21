<?php

declare(strict_types=1);

namespace VennyIO\Support;

use PDO;

final class Database
{
    public static function connection(): PDO
    {
        $config = self::configFromEnvironment();

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if (self::envBoolean('DB_PERSISTENT', false)) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }

        return new PDO($config['dsn'], $config['user'], $config['password'], $options);
    }

    /**
     * Returns simple timing diagnostics for separating API/runtime slowness
     * from Postgres connection and query slowness.
     */
    public static function health(): array
    {
        $started = microtime(true);

        $config = self::configFromEnvironment();
        $configLoadedMs = self::elapsedMs($started);

        $connectStarted = microtime(true);
        $pdo = self::connection();
        $connectMs = self::elapsedMs($connectStarted);

        $queryStarted = microtime(true);
        $stmt = $pdo->query('SELECT now() AS database_time, current_database() AS database_name, current_user AS database_user');
        $row = $stmt->fetch() ?: [];
        $queryMs = self::elapsedMs($queryStarted);

        return [
            'database' => 'postgres',
            'host' => $config['host'],
            'port' => $config['port'],
            'database_name' => $row['database_name'] ?? $config['database'],
            'database_user' => $row['database_user'] ?? null,
            'database_time' => $row['database_time'] ?? null,
            'sslmode' => 'require',
            'persistent_connections_enabled' => self::envBoolean('DB_PERSISTENT', false),
            'timing_ms' => [
                'config' => $configLoadedMs,
                'connect' => $connectMs,
                'query' => $queryMs,
                'total' => self::elapsedMs($started),
            ],
        ];
    }

    private static function configFromEnvironment(): array
    {
        $databaseUrl = getenv('DATABASE_URL');

        if (!$databaseUrl) {
            throw new \RuntimeException('DATABASE_URL is not configured.');
        }

        $parts = parse_url($databaseUrl);

        if ($parts === false || !isset($parts['host'], $parts['user'], $parts['pass'], $parts['path'])) {
            throw new \RuntimeException('DATABASE_URL is malformed.');
        }

        $host = (string) $parts['host'];
        $port = (string) ($parts['port'] ?? 5432);
        $database = ltrim((string) $parts['path'], '/');
        $user = (string) $parts['user'];
        $password = (string) $parts['pass'];
        $dsn = "pgsql:host={$host};port={$port};dbname={$database};sslmode=require";

        return [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'user' => $user,
            'password' => $password,
            'dsn' => $dsn,
        ];
    }

    private static function envBoolean(string $name, bool $default): bool
    {
        $value = getenv($name);

        if ($value === false || trim((string) $value) === '') {
            return $default;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private static function elapsedMs(float $started): float
    {
        return round((microtime(true) - $started) * 1000, 2);
    }
}
