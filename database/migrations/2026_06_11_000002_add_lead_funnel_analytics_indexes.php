<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $tables = [
        'listing_views'           => 'listing_views_created_at_index',
        'listing_phone_clicks'    => 'listing_phone_clicks_created_at_index',
        'listing_whatsapp_clicks' => 'listing_whatsapp_clicks_created_at_index',
        'offers'                  => 'offers_created_at_index',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $indexName) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if ($this->createdAtIndexExists($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($indexName): void {
                $table->index('created_at', $indexName);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table => $indexName) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! $this->indexExists($table, $indexName)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($indexName): void {
                $table->dropIndex($indexName);
            });
        }
    }

    private function createdAtIndexExists(string $table): bool
    {
        return Schema::hasIndex($table, ['created_at']);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return Schema::hasIndex($table, $indexName);
    }
};
