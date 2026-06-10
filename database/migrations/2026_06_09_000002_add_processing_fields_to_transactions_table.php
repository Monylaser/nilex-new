<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_processed')->default(false)->after('status');
            $table->timestamp('processed_at')->nullable()->after('is_processed');
            $table->string('paymob_order_id')->nullable()->after('gateway_reference');

            $table->index('paymob_order_id');
            $table->index('is_processed');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['paymob_order_id']);
            $table->dropIndex(['is_processed']);
            $table->dropColumn(['is_processed', 'processed_at', 'paymob_order_id']);
        });
    }
};
