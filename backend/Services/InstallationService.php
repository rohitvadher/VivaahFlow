<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use PDO;

/**
 * Central installation / database-bootstrap service.
 *
 * Single source of truth for:
 *   database missing | schema incomplete | not installed | installed
 *
 * All operations are idempotent: re-running never duplicates roles,
 * settings, users, catalogue or demo records.
 */
class InstallationService
{
    public const SCHEMA_VERSION = 1;
    public const APP_VERSION = '1.0.0';

    /** @return array{status:string,installed:bool,database:string,details:array} */
    public function status(): array
    {
        $dbName = Connection::databaseName();
        if (!Connection::serverAvailable()) {
            $cfg = app_config('database');
            return [
                'status' => 'server_unavailable',
                'installed' => false,
                'database' => $dbName,
                'details' => [
                    'host' => (string)($cfg['host'] ?? '127.0.0.1'),
                    'port' => (int)($cfg['port'] ?? 3306),
                    'user' => (string)($cfg['user'] ?? 'root'),
                    'message' => 'Database server is unavailable. Start MySQL/MariaDB and check host, port and credentials.',
                ],
            ];
        }
        if (!Connection::databaseExists()) {
            return [
                'status' => 'database_missing',
                'installed' => false,
                'database' => $dbName,
                'details' => ['message' => 'Database "' . $dbName . '" does not exist yet.'],
            ];
        }
        $state = Connection::getStatus();
        if ($state === 'installed') {
            return [
                'status' => 'installed',
                'installed' => true,
                'database' => $dbName,
                'details' => $this->installationInfo(),
            ];
        }
        return [
            'status' => $state,
            'installed' => false,
            'database' => $dbName,
            'details' => ['message' => 'Installation is incomplete (' . $state . ').'],
        ];
    }

    public function isInstalled(): bool
    {
        try {
            return Connection::getStatus() === 'installed';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Lightweight probe safe to call on every frontend request. Never throws. */
    public function isInstalledQuick(): bool
    {
        return $this->isInstalled();
    }

    private function installationInfo(): array
    {
        try {
            $version = Connection::fetchColumn('SELECT MAX(version) FROM schema_migrations');
            $users = (int)Connection::fetchColumn('SELECT COUNT(*) FROM users');
            return [
                'schema_version' => $version === false || $version === null ? 0 : (int)$version,
                'app_version' => self::APP_VERSION,
                'users' => $users,
            ];
        } catch (\Throwable $e) {
            return ['schema_version' => 0, 'app_version' => self::APP_VERSION];
        }
    }

    public function ensureDatabase(): void
    {
        // Throws PDOException when the server itself is unreachable.
        Connection::connectToServer();
        if (!Connection::databaseExists()) {
            Connection::createDatabase();
        }
        Connection::reset();
        Connection::connectToDatabase();
    }

    public function ensureSchema(): void
    {
        $this->ensureDatabase();
        $file = ROOT_PATH . '/database/schema.sql';
        if (!is_file($file)) {
            throw new \RuntimeException('Database schema file is missing: database/schema.sql');
        }
        $sql = (string)file_get_contents($file);
        $this->executeMulti($sql);
        $this->ensureMigrationsTable();
        $this->ensurePerformanceIndexes();
        $this->recordMigration(self::SCHEMA_VERSION);
        $this->ensureDirectories();
        Connection::resetStatusCache();
    }

    /**
     * Additive composite indexes for existing installs (fresh installs get
     * them from schema.sql). Idempotent: checks statistics before ALTER.
     */
    private function ensurePerformanceIndexes(): void
    {
        $targets = [
            ['services', 'idx_services_public', '(status, is_featured, display_order)'],
            ['service_images', 'idx_service_images_service_status', '(service_id, status)'],
            ['packages', 'idx_packages_public', '(status, is_featured, display_order)'],
            ['bookings', 'idx_bookings_customer_created', '(customer_id, created_at)'],
            ['quotations', 'idx_quotations_customer_created', '(customer_id, created_at)'],
            ['events', 'idx_events_booking_date', '(booking_id, event_date)'],
            ['reviews', 'idx_reviews_public', '(status, is_visible, created_at)'],
            ['reviews', 'idx_reviews_service_public', '(service_id, status, is_visible)'],
            ['notifications', 'idx_notifications_user_read', '(user_id, is_read, created_at)'],
        ];
        foreach ($targets as [$table, $index, $columns]) {
            try {
                $exists = (int)Connection::fetchColumn(
                    'SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
                    [$table, $index]
                );
                if ($exists === 0) {
                    Connection::pdo()->exec("ALTER TABLE `{$table}` ADD INDEX `{$index}` {$columns}");
                }
            } catch (\Throwable $e) {
                error_log('[INSTALL] index ensure skipped ' . $table . '.' . $index . ': ' . $e->getMessage());
            }
        }
    }

    private function executeMulti(string $sql): void
    {
        // Strip single-line comments, then split on semicolons at line end.
        $pdo = Connection::pdo();
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
        foreach ($statements as $statement) {
            $clean = trim($statement);
            if ($clean === '' || str_starts_with($clean, '--')) {
                // Skip pure comment blocks; otherwise strip leading comment lines.
                $lines = array_filter(
                    array_map('trim', explode("\n", $clean)),
                    fn(string $l): bool => $l !== '' && !str_starts_with($l, '--')
                );
                $clean = implode("\n", $lines);
            }
            if ($clean === '' || str_starts_with(strtoupper($clean), 'SET ')) {
                if (str_starts_with(strtoupper($clean), 'SET ')) {
                    try {
                        $pdo->exec($clean);
                    } catch (\Throwable $e) {
                        // SET NAMES / FOREIGN_KEY_CHECKS failures are non-fatal.
                    }
                }
                continue;
            }
            if ($clean !== '') {
                $pdo->exec($clean);
            }
        }
    }

    private function ensureMigrationsTable(): void
    {
        Connection::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version INT UNSIGNED NOT NULL PRIMARY KEY,
                app_version VARCHAR(20) NOT NULL DEFAULT \'' . self::APP_VERSION . '\',
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function recordMigration(int $version): void
    {
        $exists = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM schema_migrations WHERE version = ?',
            [$version]
        );
        if ($exists === 0) {
            Connection::execute(
                'INSERT INTO schema_migrations (version, app_version) VALUES (?, ?)',
                [$version, self::APP_VERSION]
            );
        }
    }

    public function ensureDirectories(): void
    {
        $dirs = [
            ROOT_PATH . '/storage/logs',
            ROOT_PATH . '/uploads',
            ROOT_PATH . '/uploads/customers',
            ROOT_PATH . '/uploads/services',
            ROOT_PATH . '/uploads/packages',
            ROOT_PATH . '/uploads/settings',
            ROOT_PATH . '/uploads/temp',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }

    /** @return array<string,string> */
    public function defaultSettings(): array
    {
        return [
            'company_name' => 'VivaahFlow',
            'tagline' => 'Weddings planned with heart, executed with precision.',
            'company_email' => 'hello@vivaahflow.in',
            'company_phone' => '+91 98765 43210',
            'company_address' => '2nd Floor, Celebration House, Linking Road, Bandra West',
            'company_city' => 'Mumbai',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'timezone' => 'Asia/Kolkata',
            'footer_text' => 'Crafting beautiful wedding experiences across India.',
            'registration_open' => '1',
            'logo_path' => 'uploads/settings/company-logo-01.png',
        ];
    }

    /** @return array<int,array<string,string>> */
    public function requiredRoles(): array
    {
        return [
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Full access to every module and action.'],
            ['name' => 'Manager', 'slug' => 'manager', 'description' => 'All operations except destructive and administrative areas.'],
            ['name' => 'Staff', 'slug' => 'staff', 'description' => 'Operational subset: bookings, events, assignments and follow-ups.'],
            ['name' => 'Customer', 'slug' => 'customer', 'description' => 'Portal-only self-service access.'],
        ];
    }

    public function ensureCoreData(array $overrides = []): void
    {
        $this->ensureSchema();
        Connection::transaction(function () use ($overrides): void {
            foreach ($this->requiredRoles() as $role) {
                $exists = (int)Connection::fetchColumn(
                    'SELECT COUNT(*) FROM roles WHERE slug = ?',
                    [$role['slug']]
                );
                if ($exists === 0) {
                    Connection::execute(
                        'INSERT INTO roles (name, slug, description) VALUES (?, ?, ?)',
                        [$role['name'], $role['slug'], $role['description']]
                    );
                }
            }
            $defaults = array_merge($this->defaultSettings(), $overrides);
            foreach ($defaults as $key => $value) {
                if ($key === 'logo_path' && $value !== '' && !is_file(ROOT_PATH . '/' . ltrim((string)$value, '/'))) {
                    $value = '';
                }
                $exists = (int)Connection::fetchColumn(
                    'SELECT COUNT(*) FROM settings WHERE setting_key = ?',
                    [$key]
                );
                if ($exists === 0) {
                    Connection::execute(
                        'INSERT INTO settings (setting_key, setting_value, category) VALUES (?, ?, ?)',
                        [$key, (string)$value, $this->settingCategory((string)$key)]
                    );
                }
            }
        });
    }

    private function settingCategory(string $key): string
    {
        if (in_array($key, ['company_name', 'tagline', 'company_email', 'company_phone', 'company_address', 'company_city', 'logo_path', 'footer_text'], true)) {
            return 'business';
        }
        if (in_array($key, ['currency', 'currency_symbol', 'timezone', 'registration_open'], true)) {
            return 'general';
        }
        return 'general';
    }

    public function adminExists(): bool
    {
        try {
            $count = (int)Connection::fetchColumn(
                "SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE r.slug = 'admin'"
            );
            return $count > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function createAdmin(string $name, string $email, string $password): int
    {
        $email = strtolower(trim($email));
        $name = trim($name);
        if ($name === '' || $email === '' || $password === '') {
            throw new \InvalidArgumentException('Admin name, email and password are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('The admin email address is invalid.');
        }
        if (mb_strlen($password) < 6) {
            throw new \InvalidArgumentException('The admin password must be at least 6 characters.');
        }
        return Connection::transaction(function () use ($name, $email, $password): int {
            $role = Connection::fetchOne('SELECT * FROM roles WHERE slug = ?', ['admin']);
            if ($role === null) {
                throw new \RuntimeException('Admin role is missing. Re-run core installation first.');
            }
            $existing = Connection::fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
            if ($existing !== null) {
                return (int)$existing['id'];
            }
            Connection::run(
                'INSERT INTO users (role_id, name, email, password_hash, status) VALUES (?, ?, ?, ?, ?)',
                [(int)$role['id'], $name, $email, password_hash($password, PASSWORD_DEFAULT), 'active']
            );
            return (int)Connection::pdo()->lastInsertId();
        });
    }

    /**
     * Full first-run installation. Idempotent.
     *
     * @param array $business Business settings overrides.
     * @param array $admin ['name','email','password']
     * @param bool $withDemo Whether to install demo data.
     * @return array Summary of what was done.
     */
    public function install(array $business, array $admin, bool $withDemo): array
    {
        $this->ensureCoreData($this->sanitizeBusiness($business));
        // Apply business overrides even when rows already existed.
        foreach ($this->sanitizeBusiness($business) as $key => $value) {
            Connection::execute(
                'UPDATE settings SET setting_value = ? WHERE setting_key = ?',
                [(string)$value, $key]
            );
        }
        $adminId = $this->createAdmin(
            (string)($admin['name'] ?? ''),
            (string)($admin['email'] ?? ''),
            (string)($admin['password'] ?? '')
        );
        $demo = null;
        if ($withDemo) {
            $demo = $this->seedDemo();
        }
        $this->recordMigration(self::SCHEMA_VERSION);
        return [
            'database' => Connection::databaseName(),
            'schema_version' => self::SCHEMA_VERSION,
            'admin_id' => $adminId,
            'demo' => $demo,
        ];
    }

    /** @return array<string,string> */
    private function sanitizeBusiness(array $business): array
    {
        $allowed = [
            'company_name', 'tagline', 'company_email', 'company_phone',
            'company_address', 'company_city', 'currency', 'currency_symbol',
            'timezone', 'footer_text', 'registration_open',
        ];
        $clean = [];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $business)) {
                continue;
            }
            $value = $business[$key];
            if ($key === 'registration_open') {
                $clean[$key] = in_array($value, [1, '1', true, 'true', 'yes', 'on'], true) ? '1' : '0';
                continue;
            }
            $clean[$key] = trim((string)$value);
        }
        if (($clean['company_name'] ?? '') === '') {
            unset($clean['company_name']);
        }
        return $clean;
    }

    /**
     * Install demo data. Idempotent — safe to run repeatedly.
     * @return array Summary counts.
     */
    public function seedDemo(): array
    {
        $this->ensureCoreData();
        $seederFile = ROOT_PATH . '/database/seeds/demo.php';
        if (is_file($seederFile)) {
            $seed = require $seederFile;
            if (is_callable($seed)) {
                return $seed($this);
            }
        }
        // Fallback: minimal demo (roles + admin already ensured).
        return ['note' => 'Demo seeder file missing; core data ensured.'];
    }

    // --- Helpers exposed to the demo seeder (idempotent primitives) ---

    public function ensureRole(string $slug, string $name, string $description = ''): int
    {
        $row = Connection::fetchOne('SELECT id FROM roles WHERE slug = ?', [$slug]);
        if ($row !== null) {
            return (int)$row['id'];
        }
        Connection::run(
            'INSERT INTO roles (name, slug, description) VALUES (?, ?, ?)',
            [$name, $slug, $description]
        );
        return (int)Connection::pdo()->lastInsertId();
    }

    public function ensureUser(string $email, string $name, string $roleSlug, string $password, array $extra = []): int
    {
        $email = strtolower(trim($email));
        $existing = Connection::fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing !== null) {
            return (int)$existing['id'];
        }
        $role = Connection::fetchOne('SELECT id FROM roles WHERE slug = ?', [$roleSlug]);
        if ($role === null) {
            throw new \RuntimeException('Role missing: ' . $roleSlug);
        }
        Connection::run(
            'INSERT INTO users (role_id, name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, ?, ?)',
            [
                (int)$role['id'],
                $name,
                $email,
                $extra['phone'] ?? null,
                password_hash($password, PASSWORD_DEFAULT),
                $extra['status'] ?? 'active',
            ]
        );
        return (int)Connection::pdo()->lastInsertId();
    }

    public function ensureSetting(string $key, string $value, string $category = 'general'): void
    {
        $exists = (int)Connection::fetchColumn('SELECT COUNT(*) FROM settings WHERE setting_key = ?', [$key]);
        if ($exists === 0) {
            Connection::execute(
                'INSERT INTO settings (setting_key, setting_value, category) VALUES (?, ?, ?)',
                [$key, $value, $category]
            );
        }
    }
}
