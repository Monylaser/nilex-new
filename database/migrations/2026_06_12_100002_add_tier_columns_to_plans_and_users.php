<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_plans', function (Blueprint $table) {
            $table->string('tier_key', 32)->nullable()->after('is_active');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('plan_tier', 32)->nullable()->after('points');
        });
    }

    public function down(): void
    {
        Schema::table('point_plans', function (Blueprint $table) {
            $table->dropColumn('tier_key');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('plan_tier');
        });
    }
};
