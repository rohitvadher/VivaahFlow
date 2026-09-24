<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use PDOStatement;

class Connection
{
    private static ?PDO $instance = null;
    private static ?PDO $serverInstance = null;
    private static ?string $statusCache = null;

    private static function dbConfig(): array
    {
        $db = app_config('database');
        return [
            'host' => (string)($db['host'] ?? '127.0.0.1'),
            'port' => (int)($db['port'] ?? 3306),
            'name' => (string)($db['name'] ?? 'vivaahflow'),
            'user' => (string)($db['user'] ?? 'root'),
            'pass' => (string)($db['pass'] ?? ''),
            'charset' => (string)($db['charset'] ?? 'utf8mb4'),
        ];
    }

    private static function baseOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    /**
     * Connect to the MySQL/MariaDB server without selecting a database.
     * Used by the installer to detect/create the application database.
     */
    public static function connectToServer(): PDO
    {
        if (self::$serverInstance instanceof PDO) {
            return self::$serverInstance;
        }
        $db = self::dbConfig();
        $dsn = sprintf(
            'mysql:host=%s;port=%d;charset=%s',
            $db['host'],
            $db['port'],
            $db['charset']
        );
        self::$serverInstance = new PDO($dsn, $db['user'], $db['pass'], self::baseOptions());
        return self::$serverInstance;
    }

    /**
     * Connect to the application database (normal runtime path).
     */
    public static function connectToDatabase(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }
        $db = self::dbConfig();
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );
        self::$instance = new PDO($dsn, $db['user'], $db['pass'], self::baseOptions());
        return self::$instance;
    }

    public static function pdo(): PDO
    {
        return self::connectToDatabase();
    }

    public static function reset(): void
    {
        self::$instance = null;
        self::$serverInstance = null;
        self::$statusCache = null;
    }

    public static function databaseName(): string
    {
        return self::dbConfig()['name'];
    }

    public static function databaseExists(?string $name = null): bool
    {
        $target = $name ?? self::databaseName();
        try {
            $pdo = self::connectToServer();
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = ?'
            );
            $stmt->execute([$target]);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function createDatabase(?string $name = null): bool
    {
        $target = $name ?? self::databaseName();
        $safe = str_replace('`', '', $target);
        $pdo = self::connectToServer();
        $pdo->exec(
            'CREATE DATABASE IF NOT EXISTS `' . $safe . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        return self::databaseExists($target);
    }

    public static function tableExists(string $table): bool
    {
        try {
            $stmt = self::pdo()->prepare(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $stmt->execute([$table]);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Lightweight status probe used by the installer, health checks and
     * public bootstrap handling. Never throws.
     *
     * Returns one of:
     *  server_unavailable | database_missing | schema_incomplete | not_installed | installed
     */
    public static function getStatus(): string
    {
        // Memoized per request: frontend routers + PublicController call this
        // several times per page; the underlying state cannot change mid-request
        // except via the installer itself (which calls resetStatusCache()).
        if (self::$statusCache !== null) {
            return self::$statusCache;
        }
        try {
            self::connectToServer();
        } catch (\Throwable $e) {
            return self::$statusCache = 'server_unavailable';
        }
        if (!self::databaseExists()) {
            return self::$statusCache = 'database_missing';
        }
        try {
            $pdo = self::connectToDatabase();
        } catch (\Throwable $e) {
            return self::$statusCache = 'database_missing';
        }
        try {
            // Single information_schema round-trip instead of one per table.
            $stmt = $pdo->query(
                "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()"
            );
            $present = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $name) {
                $present[(string)$name] = true;
            }
            foreach ([
                'roles', 'users', 'customers', 'service_categories', 'services',
                'packages', 'enquiries', 'quotations', 'bookings', 'settings',
            ] as $table) {
                if (!isset($present[$table])) {
                    return self::$statusCache = 'schema_incomplete';
                }
            }
            // Installation marker: at least one role + settings row.
            $markers = $pdo->query('SELECT (SELECT COUNT(*) FROM `roles`) AS r, (SELECT COUNT(*) FROM `settings`) AS s')->fetch();
            if ((int)($markers['r'] ?? 0) === 0 || (int)($markers['s'] ?? 0) === 0) {
                return self::$statusCache = 'not_installed';
            }
            return self::$statusCache = 'installed';
        } catch (\Throwable $e) {
            return self::$statusCache = 'schema_incomplete';
        }
    }

    public static function resetStatusCache(): void
    {
        self::$statusCache = null;
    }

    public static function serverAvailable(): bool
    {
        try {
            self::connectToServer();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchColumn(string $sql, array $params = [])
    {
        return self::run($sql, $params)->fetchColumn();
    }

    public static function execute(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    public static function lastInsertId(): int
    {
        return (int)self::pdo()->lastInsertId();
    }

    public static function transaction(callable $callback)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
