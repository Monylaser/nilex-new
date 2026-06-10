<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'fingerprint_hash')) {
                $table->string('fingerprint_hash', 64)->nullable()->after('device_id');
                $table->index('fingerprint_hash');
            }

            if (! Schema::hasColumn('users', 'otp_attempts')) {
                $table->unsignedTinyInteger('otp_attempts')->default(0)->after('otp_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'fingerprint_hash')) {
                $table->dropIndex(['fingerprint_hash']);
                $table->dropColumn('fingerprint_hash');
            }

            if (Schema::hasColumn('users', 'otp_attempts')) {
                $table->dropColumn('otp_attempts');
            }
        });
    }
};
