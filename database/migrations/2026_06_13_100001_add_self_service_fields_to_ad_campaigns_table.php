<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->nullable()->after('approval_status');
            $table->string('paymob_order_id')->nullable()->after('payment_status');
            $table->string('paymob_transaction_id')->nullable()->after('paymob_order_id');
            $table->decimal('amount_paid', 10, 2)->nullable()->after('paymob_transaction_id');
            $table->timestamp('paid_at')->nullable()->after('amount_paid');

            $table->index('seller_id');
            $table->index('payment_status');
            $table->unique('paymob_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropUnique(['paymob_transaction_id']);
            $table->dropIndex(['seller_id']);
            $table->dropIndex(['payment_status']);
            $table->dropColumn([
                'seller_id',
                'payment_status',
                'paymob_order_id',
                'paymob_transaction_id',
                'amount_paid',
                'paid_at',
            ]);
        });
    }
};
