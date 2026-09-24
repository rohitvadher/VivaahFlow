<?php

declare(strict_types=1);

/**
 * VivaahFlow database seeder (CLI).
 *
 * Usage:
 *   php database/seed.php                 # schema + core data (idempotent)
 *   php database/seed.php --with-demo     # schema + core + demo data
 *   php database/seed.php --demo-only     # demo data only (schema must exist)
 *   php database/seed.php --help
 *
 * Idempotent: safe to run repeatedly; existing rows are skipped.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.' . PHP_EOL);
}

define('ROOT_PATH', dirname(__DIR__));
define('BACKEND_PATH', ROOT_PATH . '/backend');
define('STORAGE_PATH', ROOT_PATH . '/storage');

require BACKEND_PATH . '/Helpers/Functions.php';
date_default_timezone_set((string)app_config('timezone', 'UTC'));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $file = BACKEND_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

use App\Services\InstallationService;

$options = getopt('', ['with-demo', 'demo-only', 'help']);
if (isset($options['help'])) {
    echo "VivaahFlow seeder\n";
    echo "  php database/seed.php [--with-demo] [--demo-only]\n";
    exit(0);
}

$installer = new InstallationService();
$withDemo = isset($options['with-demo']) || isset($options['demo-only']);
$demoOnly = isset($options['demo-only']);

try {
    if (!$demoOnly) {
        echo "Ensuring database + schema...\n";
        $installer->ensureCoreData();
        echo "Schema ready (" . App\Database\Connection::databaseName() . ").\n";
    } else {
        $installer->ensureSchema();
    }
    if ($withDemo) {
        echo "Installing demo data...\n";
        $summary = $installer->seedDemo();
        echo 'Demo installed: ' . json_encode($summary) . "\n";
    }
    $status = $installer->status();
    echo 'Status: ' . $status['status'] . "\n";
    echo "Done.\n";
    echo "Demo accounts: admin@example.com / Admin@123, manager@example.com / Manager@123, staff@example.com / Staff@123, customer@example.com / Customer@123\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
