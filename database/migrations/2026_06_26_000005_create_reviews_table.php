<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // تقييم واحد فقط لكل عملية بيع مؤكدة (unique)
            $table->foreignId('sale_confirmation_id')->unique()->constrained()->restrictOnDelete();

            // المشتري (الكاتب) والبائع (المُقيَّم)
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->restrictOnDelete();

            // مكرر denormalized لتسهيل "كل تقييمات إعلان معيّن" دون join
            $table->foreignId('listing_id')->constrained()->restrictOnDelete();

            $table->unsignedTinyInteger('rating'); // 1..5 (يُتحقق منه في طبقة التطبيق)
            $table->text('comment')->nullable();    // يُخزَّن من الآن، يُعرض في مرحلة لاحقة

            $table->timestamps();

            // محور استعلام متوسط/عدد تقييمات البائع
            $table->index('reviewee_id');
            $table->index('listing_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
