<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composite and single-column indexes for high-traffic listing, offer, and media queries.
     *
     * @var array<int, array{table: string, columns: array<int, string>, name: string}>
     */
    private array $indexes = [
        // listings — public grids, category pages, moderation, seller dashboard
        ['table' => 'listings', 'columns' => ['status'], 'name' => 'listings_status_index'],
        ['table' => 'listings', 'columns' => ['status', 'created_at'], 'name' => 'listings_status_created_at_index'],
        ['table' => 'listings', 'columns' => ['user_id', 'status'], 'name' => 'listings_user_id_status_index'],
        ['table' => 'listings', 'columns' => ['user_id', 'created_at'], 'name' => 'listings_user_id_created_at_index'],
        ['table' => 'listings', 'columns' => ['category_id', 'status'], 'name' => 'listings_category_id_status_index'],
        ['table' => 'listings', 'columns' => ['is_featured', 'featured_until'], 'name' => 'listings_is_featured_featured_until_index'],
        ['table' => 'listings', 'columns' => ['deleted_at'], 'name' => 'listings_deleted_at_index'],

        // offers — seller dashboard pending-offer inbox
        ['table' => 'offers', 'columns' => ['receiver_id', 'status'], 'name' => 'offers_receiver_id_status_index'],

        // media (Spatie) — eager-loaded per listing card; collection_name narrows morph lookup
        ['table' => 'media', 'columns' => ['model_type', 'model_id', 'collection_name'], 'name' => 'media_model_type_model_id_collection_name_index'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $index) {
            $table = $index['table'];
            $columns = $index['columns'];
            $name = $index['name'];

            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue 2;
                }
            }

            if ($this->indexExists($table, $name, $columns)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($columns, $name): void {
                $table->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->indexes) as $index) {
            $table = $index['table'];
            $name = $index['name'];

            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($name): void {
                $table->dropIndex($name);
            });
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function indexExists(string $table, string $name, array $columns): bool
    {
        return Schema::hasIndex($table, $name) || Schema::hasIndex($table, $columns);
    }
};
