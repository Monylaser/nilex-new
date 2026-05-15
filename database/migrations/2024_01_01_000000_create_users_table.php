<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique(); // 🟢 يقبل Null لتسجيل الموبايل
            $table->timestamp('email_verified_at')->nullable();
            
            $table->string('phone')->nullable()->unique(); // 🟢 تم إصلاح التكرار
            $table->timestamp('phone_verified_at')->nullable();
            
            $table->string('password')->nullable(); // 🟢 يقبل Null لتسجيل السوشيال ميديا
            $table->string('avatar')->nullable();
            
            // 🟢 حقول السوشيال ميديا
            $table->string('provider_name')->nullable(); // google, tiktok, instagram
            $table->string('provider_id')->nullable();
            
            // 🟢 حقول الحماية والـ OTP
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->boolean('is_phone_verified')->default(false);
            $table->text('device_id')->nullable();
            $table->string('ip_address')->nullable();
            
            // 🟢 حقول الإدارة والنقاط
            $table->boolean('is_banned')->default(false);
            $table->text('ban_reason')->nullable();
            $table->integer('points')->default(0);
            $table->integer('strike_count')->default(0);
            
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};