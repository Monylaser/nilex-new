<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('car_brand_id')
                ->nullable()
                ->after('category_id')
                ->constrained('car_brands')
                ->nullOnDelete();

            $table->foreignId('car_model_id')
                ->nullable()
                ->after('car_brand_id')
                ->constrained('car_models')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropForeign(['car_brand_id']);
            $table->dropForeign(['car_model_id']);
            $table->dropColumn(['car_brand_id', 'car_model_id']);
        });
    }
};