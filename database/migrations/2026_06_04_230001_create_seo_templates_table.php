<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_templates', function (Blueprint $table) {
            $table->id();
            $table->string('target_model');          // App\Models\Category | App\Models\Location
            $table->unsignedBigInteger('target_id');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('keywords', 500)->nullable();
            $table->string('og_image')->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('schema_markup')->nullable();
            $table->timestamps();

            $table->unique(['target_model', 'target_id'], 'seo_target_unique');
            $table->index(['target_model', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_templates');
    }
};
