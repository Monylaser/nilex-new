<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks how many OTP resends occurred in the current verification window.
 * Cooldown is derived from otp_expires_at − expires_minutes (no second column).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'otp_resend_count')) {
                $table->unsignedTinyInteger('otp_resend_count')->default(0)->after('otp_attempts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'otp_resend_count')) {
                $table->dropColumn('otp_resend_count');
            }
        });
    }
};
