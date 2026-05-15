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
        $table->unsignedBigInteger('views_count')->default(0)->after('status');
        $table->unsignedBigInteger('whatsapp_clicks')->default(0)->after('views_count');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            //
        });
    }
};
