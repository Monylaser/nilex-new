<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            
            // ربط العرض بالإعلان
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            
            // مين اللي باعت العرض (المشتري)
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            
            // مين اللي هيستقبل العرض (البائع) - للسرعة في الاستعلامات
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            
            // السعر المعروض
            $table->decimal('amount', 12, 2);
            
            // رسالة إضافية من المشتري (اختياري)
            $table->text('message')->nullable();
            
            // حالة العرض
            $table->enum('status', ['pending', 'accepted', 'rejected', 'canceled'])->default('pending');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};