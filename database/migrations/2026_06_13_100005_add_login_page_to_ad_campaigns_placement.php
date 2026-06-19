<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE ad_campaigns MODIFY COLUMN placement ENUM(
            'hero_top',
            'home_feed',
            'category_page',
            'listing_detail',
            'search_results',
            'login_page'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE ad_campaigns MODIFY COLUMN placement ENUM(
            'hero_top',
            'home_feed',
            'category_page',
            'listing_detail',
            'search_results'
        ) NOT NULL");
    }
};
