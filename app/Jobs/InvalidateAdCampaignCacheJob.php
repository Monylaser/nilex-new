<?php

namespace App\Jobs;

use App\Services\AdCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InvalidateAdCampaignCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $placement,
        public ?int $categoryId = null,
        public ?string $previousPlacement = null,
        public ?int $previousCategoryId = null,
    ) {}

    public function handle(AdCampaignService $adCampaignService): void
    {
        $adCampaignService->flushCacheKeys(
            $adCampaignService->resolveCacheKeysForCampaign(
                $this->placement,
                $this->categoryId,
                $this->previousPlacement,
                $this->previousCategoryId,
            )
        );
    }
}
