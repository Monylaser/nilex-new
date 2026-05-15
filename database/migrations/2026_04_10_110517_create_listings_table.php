<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // فك القيود مؤقتاً لمسح الجدول القديم العالق
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('listings');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->json('images')->nullable();

            // تأكد إن ترتيب الجداول صح (الـ categories لازم تكون اتكريتت قبل السطر ده)
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('status')->default('pending');
            
            // --- الإضافات الجديدة (فقط) ---
            $table->string('rejection_reason')->nullable(); // سبب الرفض لليوزر
            $table->boolean('is_featured')->default(false); // تمييز الإعلان
            $table->unsignedBigInteger('views_count')->default(0); // عداد المشاهدات
            $table->unsignedBigInteger('whatsapp_clicks')->default(0); // عداد نقرات الواتساب
            $table->json('custom_fields_values')->nullable(); // قيم الحقول المخصصة
            // ----------------------------

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};