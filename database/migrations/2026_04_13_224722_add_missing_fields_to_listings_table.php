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
        // لو الـ slug مش موجود ضيفه (احتياطي)
        if (!Schema::hasColumn('listings', 'slug')) {
            $table->string('slug')->unique()->after('title');
        }

        // إضافة الوصف لو مش موجود
        if (!Schema::hasColumn('listings', 'description')) {
            $table->longText('description')->nullable()->after('price');
        }

        // إضافة حقول الموقع
        $table->foreignId('province_id')->nullable()->constrained('locations')->nullOnDelete();
        $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

        // إضافة حقل الصور الإضافية (لأنه Repeater بيتحفظ كـ JSON)
        $table->json('extra_images')->nullable();
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
