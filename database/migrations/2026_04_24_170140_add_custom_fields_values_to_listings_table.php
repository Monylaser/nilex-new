<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('listings', 'custom_fields_values')) {
            return;
        }

        Schema::table('listings', function (Blueprint $table) {
            $table->json('custom_fields_values')->nullable()->after('category_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('listings', 'custom_fields_values')) {
            return;
        }

        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('custom_fields_values');
        });
    }
};
