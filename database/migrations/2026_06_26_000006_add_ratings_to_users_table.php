<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // قيم denormalized يحدّثها ReviewObserver (نمط points_balance)
            if (! Schema::hasColumn('users', 'ratings_avg')) {
                $table->decimal('ratings_avg', 3, 2)->nullable()->after('plan_type');
            }
            if (! Schema::hasColumn('users', 'ratings_count')) {
                $table->unsignedInteger('ratings_count')->default(0)->after('ratings_avg');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'ratings_count')) {
                $table->dropColumn('ratings_count');
            }
            if (Schema::hasColumn('users', 'ratings_avg')) {
                $table->dropColumn('ratings_avg');
            }
        });
    }
};
