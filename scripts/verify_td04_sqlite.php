<?php

declare(strict_types=1);

/**
 * SQLite file-DB verification — single connection for migrate + introspection.
 */

$dbFile = sys_get_temp_dir() . '/td04_verify.sqlite';
if (file_exists($dbFile)) {
    unlink($dbFile);
}

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . $dbFile);
putenv('DB_URL=');

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

Artisan::call('migrate:fresh', ['--force' => true]);

$tables = [
    'listing_views' => 'listing_views_created_at_index',
    'listing_phone_clicks' => 'listing_phone_clicks_created_at_index',
    'listing_whatsapp_clicks' => 'listing_whatsapp_clicks_created_at_index',
    'offers' => 'offers_created_at_index',
];

$results = [
    'driver' => 'sqlite',
    'database' => $dbFile,
    'migrate_fresh' => 'success',
    'indexes_after_up' => [],
    'idempotent_rerun' => null,
    'rollback' => null,
    'indexes_after_down' => [],
];

foreach ($tables as $table => $name) {
    $results['indexes_after_up'][$table] = [
        'by_name' => Schema::hasIndex($table, $name),
        'by_column' => Schema::hasIndex($table, ['created_at']),
    ];
}

$migration = require __DIR__ . '/../database/migrations/2026_06_11_000002_add_lead_funnel_analytics_indexes.php';

try {
    $migration->up();
    $results['idempotent_rerun'] = 'success';
} catch (Throwable $e) {
    $results['idempotent_rerun'] = 'failed: ' . $e->getMessage();
}

try {
    $migration->down();
    $results['rollback'] = 'success';
} catch (Throwable $e) {
    $results['rollback'] = 'failed: ' . $e->getMessage();
}

foreach ($tables as $table => $name) {
    $results['indexes_after_down'][$table] = [
        'by_name' => Schema::hasIndex($table, $name),
        'by_column' => Schema::hasIndex($table, ['created_at']),
    ];
}

$outPath = __DIR__ . '/../storage/app/td04_sqlite_migration_verification.json';
file_put_contents($outPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode($results, JSON_PRETTY_PRINT) . "\n";

unlink($dbFile);
