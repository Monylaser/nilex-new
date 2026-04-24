<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // السطر ده هو اللي هيحل الخطأ

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // سطر طوارئ: فك قيود الـ Foreign Keys ومسح الجدول يدوياً لو اتحشر
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('locations');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->nullable()->unique();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->onDelete('cascade');
            $table->integer('level')->default(0);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
