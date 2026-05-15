<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('phone_brand_id')
                ->nullable()
                ->after('car_model_id')
                ->constrained('phone_brands')
                ->nullOnDelete();

            $table->foreignId('phone_model_id')
                ->nullable()
                ->after('phone_brand_id')
                ->constrained('phone_models')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropForeign(['phone_brand_id']);
            $table->dropForeign(['phone_model_id']);
            $table->dropColumn(['phone_brand_id', 'phone_model_id']);
        });
    }
};