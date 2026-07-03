<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ad_campaigns', 'display_duration_seconds')) {
            Schema::table('ad_campaigns', function (Blueprint $table) {
                $table->unsignedInteger('display_duration_seconds')->default(5)->after('priority');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ad_campaigns', 'display_duration_seconds')) {
            Schema::table('ad_campaigns', function (Blueprint $table) {
                $table->dropColumn('display_duration_seconds');
            });
        }
    }
};
