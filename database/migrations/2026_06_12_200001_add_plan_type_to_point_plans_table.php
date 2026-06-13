<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_plans', function (Blueprint $table) {
            $table->string('plan_type')->default('individual')->after('is_active');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('plan_type')->default('individual')->after('plan_tier');
        });
    }

    public function down(): void
    {
        Schema::table('point_plans', function (Blueprint $table) {
            $table->dropColumn('plan_type');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('plan_type');
        });
    }
};
