<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // rejection_reason may already exist from the base create migration (string type).
            // Skip adding it to avoid duplicate column errors on SQLite / fresh installs.
            if (! Schema::hasColumn('listings', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }
            if (! Schema::hasColumn('listings', 'moderated_by')) {
                $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete()->after('rejection_reason');
            }
            if (! Schema::hasColumn('listings', 'moderated_at')) {
                $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            }
            if (! Schema::hasColumn('listings', 'flag_reason')) {
                $table->enum('flag_reason', ['ethical', 'security', 'fraud', 'spam', 'other'])->nullable()->after('moderated_at');
            }
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