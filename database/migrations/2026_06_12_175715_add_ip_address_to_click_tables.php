<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_phone_clicks', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('user_id');
            $table->index(['listing_id', 'ip_address', 'created_at']);
        });

        Schema::table('listing_whatsapp_clicks', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('user_id');
            $table->index(['listing_id', 'ip_address', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('listing_phone_clicks', function (Blueprint $table) {
            $table->dropIndex(['listing_id', 'ip_address', 'created_at']);
            $table->dropColumn('ip_address');
        });

        Schema::table('listing_whatsapp_clicks', function (Blueprint $table) {
            $table->dropIndex(['listing_id', 'ip_address', 'created_at']);
            $table->dropColumn('ip_address');
        });
    }
};