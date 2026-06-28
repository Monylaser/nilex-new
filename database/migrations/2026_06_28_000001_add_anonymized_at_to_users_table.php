<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'anonymized_at')) {
                // علم + ختم زمني للحسابات المُجمّدة (anonymized). null = حساب فعّال.
                // ليس SoftDeletes ولا global scope — الصف يبقى queryable طبيعياً،
                // يُستخدم فقط للتمييز ومنع تسجيل الدخول.
                $table->timestamp('anonymized_at')->nullable()->after('is_banned');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'anonymized_at')) {
                $table->dropColumn('anonymized_at');
            }
        });
    }
};
