<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'strike_count')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('strike_count')->default(0)->after('is_banned');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('strike_count');
        });
    }
};