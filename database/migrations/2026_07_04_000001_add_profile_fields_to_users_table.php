<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ── حقول البروفايل الشخصي (كانت في الـ view لكن لم تكن موجودة في الـ DB) ──
            if (! Schema::hasColumn('users', 'whatsapp')) {
                $table->string('whatsapp')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('whatsapp');
            }
            if (! Schema::hasColumn('users', 'governorate')) {
                $table->string('governorate')->nullable()->after('bio');
            }
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('governorate');
            }

            // ── الموقع عبر جدول locations (لـ wizard pre-fill) ──
            if (! Schema::hasColumn('users', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('city');
                $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
            }

            // ── توثيق الإيميل بعد التسجيل ──
            if (! Schema::hasColumn('users', 'pending_email')) {
                $table->string('pending_email')->nullable()->after('pending_phone');
            }
            if (! Schema::hasColumn('users', 'email_bonus_claimed_at')) {
                $table->timestamp('email_bonus_claimed_at')->nullable()->after('phone_bonus_claimed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['whatsapp', 'bio', 'governorate', 'city', 'location_id', 'pending_email', 'email_bonus_claimed_at'];

            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    if ($col === 'location_id') {
                        $table->dropForeign(['location_id']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
