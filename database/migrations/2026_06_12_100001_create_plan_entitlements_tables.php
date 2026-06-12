<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_entitlements', function (Blueprint $table) {
            $table->id();
            $table->string('plan_tier', 32);
            $table->string('feature_key', 64);
            $table->string('value_type', 16);
            $table->text('value');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['plan_tier', 'feature_key']);
        });

        Schema::create('user_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key', 64);
            $table->string('value_type', 16);
            $table->text('value');
            $table->string('source', 32);
            $table->foreignId('source_plan_id')->nullable()->constrained('point_plans')->nullOnDelete();
            $table->foreignId('source_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamp('granted_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'feature_key']);
            $table->index('user_id');
            $table->index('feature_key');
        });

        Schema::create('user_entitlement_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key', 64);
            $table->string('period_key', 16);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'feature_key', 'period_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_entitlement_usage');
        Schema::dropIfExists('user_entitlements');
        Schema::dropIfExists('plan_entitlements');
    }
};
