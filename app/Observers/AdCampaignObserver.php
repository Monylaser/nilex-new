<?php

namespace App\Observers;

use App\Models\AdCampaign;
use App\Services\AdCampaignService;

class AdCampaignObserver
{
    public function saving(AdCampaign $campaign): void
    {
        if (
            $campaign->ends_at !== null
            && $campaign->ends_at->isPast()
            && $campaign->status !== 'expired'
        ) {
            $campaign->status = 'expired';
        }
    }

    public function saved(AdCampaign $campaign): void
    {
        app(AdCampaignService::class)->queueCampaignCacheInvalidation($campaign);
    }

    public function deleted(AdCampaign $campaign): void
    {
        app(AdCampaignService::class)->queueCampaignCacheInvalidation($campaign);
    }

    public function restored(AdCampaign $campaign): void
    {
        app(AdCampaignService::class)->queueCampaignCacheInvalidation($campaign);
    }
}
