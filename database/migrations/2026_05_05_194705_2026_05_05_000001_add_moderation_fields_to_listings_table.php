<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // status موجود بالفعل — بس نأكد القيم الصح
            // rejection_reason و moderated_by جدد
            $table->text('rejection_reason')->nullable()->after('status');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete()->after('rejection_reason');
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            $table->enum('flag_reason', ['ethical', 'security', 'fraud', 'spam', 'other'])->nullable()->after('moderated_at');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropForeign(['moderated_by']);
            $table->dropColumn(['rejection_reason', 'moderated_by', 'moderated_at', 'flag_reason']);
        });
    }
};