<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('listings', function (Blueprint $table) {
        // بنضيف الحالة بس لأن Featured موجودة عندك فعلاً
        $table->string('condition')->nullable()->after('price')->default('new');
    });
}

public function down(): void
{
    Schema::table('listings', function (Blueprint $table) {
        $table->dropColumn('condition');
    });
}
};
