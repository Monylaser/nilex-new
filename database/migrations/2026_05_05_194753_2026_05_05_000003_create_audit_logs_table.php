<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('action');                          // approve_ad, reject_ad, ban_user …
            $table->string('target_type');                     // App\Models\Listing | App\Models\User
            $table->unsignedBigInteger('target_id');
            $table->json('payload')->nullable();               // extra context (reason, old_status …)
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};