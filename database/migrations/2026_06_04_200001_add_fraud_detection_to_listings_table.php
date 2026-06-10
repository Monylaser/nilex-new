<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_flagged column
        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasColumn('listings', 'is_flagged')) {
                $table->boolean('is_flagged')->default(false)->after('status');
            }
        });

        // Promote flag_reason from enum → string so the observer can store arbitrary text
        if (Schema::hasColumn('listings', 'flag_reason')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement('ALTER TABLE `listings` MODIFY COLUMN `flag_reason` VARCHAR(255) NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE listings ALTER COLUMN flag_reason TYPE VARCHAR(255)');
            } elseif ($driver === 'sqlite') {
                // SQLite does not support ALTER COLUMN; drop + re-add
                Schema::table('listings', function (Blueprint $table) {
                    $table->dropColumn('flag_reason');
                });
                Schema::table('listings', function (Blueprint $table) {
                    $table->string('flag_reason')->nullable();
                });
            }
        } else {
            Schema::table('listings', function (Blueprint $table) {
                $table->string('flag_reason')->nullable()->after('is_flagged');
            });
        }
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (Schema::hasColumn('listings', 'is_flagged')) {
                $table->dropColumn('is_flagged');
            }
        });
    }
};
