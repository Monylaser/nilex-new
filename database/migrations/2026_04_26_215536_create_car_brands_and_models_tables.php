<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_brand_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('car_brands');
    }
};