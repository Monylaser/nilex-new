<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

$tables = [
    'listing_views' => 'listing_views_created_at_index',
    'listing_phone_clicks' => 'listing_phone_clicks_created_at_index',
    'listing_whatsapp_clicks' => 'listing_whatsapp_clicks_created_at_index',
    'offers' => 'offers_created_at_index',
];

$results = [
    'driver' => Schema::getConnection()->getDriverName(),
    'indexes' => [],
    'idempotent_rerun' => null,
];

foreach ($tables as $table => $name) {
    $results['indexes'][$table] = [
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

$outPath = __DIR__ . '/../storage/app/td04_migration_verification.json';
file_put_contents($outPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Wrote {$outPath}\n";
echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
