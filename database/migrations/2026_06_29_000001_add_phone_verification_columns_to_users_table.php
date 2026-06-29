<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — post-registration phone verification flow.
 *
 * - pending_phone:            the new number staged while awaiting OTP confirmation
 *                             (the live verified `phone` is NOT overwritten until confirmed).
 * - otp_channel:              which channel the active OTP was sent through ('email' | 'phone').
 * - phone_bonus_claimed_at:   permanent, once-per-lifetime marker for the +50 phone bonus —
 *                             prevents re-granting even if the user changes & re-verifies a phone later.
 *
 * Note: `phone_verified_at` already exists (original create_users_table) and is reused.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'pending_phone')) {
                $table->string('pending_phone')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'otp_channel')) {
                $table->string('otp_channel')->nullable()->after('otp_expires_at');
            }
            if (! Schema::hasColumn('users', 'phone_bonus_claimed_at')) {
                $table->timestamp('phone_bonus_claimed_at')->nullable()->after('phone_verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['pending_phone', 'otp_channel', 'phone_bonus_claimed_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
