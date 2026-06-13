<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('placement', [
                'hero_top',
                'home_feed',
                'category_page',
                'listing_detail',
                'search_results',
            ]);
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('target_url')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'active', 'paused', 'expired'])->default('draft');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejected_reason')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->integer('priority')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['placement', 'status', 'approval_status']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};
