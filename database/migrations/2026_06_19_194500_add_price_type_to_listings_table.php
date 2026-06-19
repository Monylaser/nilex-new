<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds only the genuinely missing wizard field: price_type.
     * `condition`, `phone` already exist; `location` is handled via location_id.
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasColumn('listings', 'price_type')) {
                $table->string('price_type')->nullable()->after('price');
                $table->index('price_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * Safely drops the index first, then the column.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (Schema::hasColumn('listings', 'price_type')) {
                $table->dropIndex(['price_type']);
                $table->dropColumn('price_type');
            }
        });
    }
};
