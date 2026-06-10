<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasColumn('listings', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('status');
            }
            if (! Schema::hasColumn('listings', 'featured_until')) {
                $table->timestamp('featured_until')->nullable()->after('is_featured');
            }
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
           $table->dropColumn(['is_featured', 'featured_until']);
        });
    }
};