<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id');
            $table->string('status', 32)->default('new');
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
            $table->index(['seller_id', 'created_at']);
            $table->index(['seller_id', 'status']);
            $table->index(['listing_id', 'created_at']);
        });

        Schema::create('seller_lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_lead_id')->constrained()->cascadeOnDelete();
            $table->string('type', 64);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['seller_lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_lead_activities');
        Schema::dropIfExists('seller_leads');
    }
};
