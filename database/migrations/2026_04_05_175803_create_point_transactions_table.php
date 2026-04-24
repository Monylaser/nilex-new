<?php
// database/migrations/xxxx_xx_xx_create_point_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->integer('amount');            // Positive = credit, Negative = debit
            $table->unsignedInteger('current_balance'); // Balance AFTER this transaction
            $table->string('description');
            $table->nullableMorphs('reference');  // reference_id + reference_type (polymorphic)
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
