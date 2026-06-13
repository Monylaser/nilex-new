<?php

namespace App\Observers;

use App\Models\AdCampaign;
use Illuminate\Support\Facades\Cache;

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
        $this->clearCache($campaign);
    }

    public function created(AdCampaign $campaign): void
    {
        $this->clearCache($campaign);
    }

    public function updated(AdCampaign $campaign): void
    {
        $this->clearCache($campaign);
    }

    public function deleted(AdCampaign $campaign): void
    {
        $this->clearCache($campaign);
    }

    private function clearCache(AdCampaign $campaign): void
    {
        Cache::forget('ad_campaign_hero_top');
        Cache::forget('ad_campaign_home_feed');
        Cache::forget('ad_campaign_listing_detail');
        Cache::forget('ad_campaign_search_results');
        Cache::forget('ad_campaign_category_page_' . ($campaign->category_id ?? 'all'));
    }
}
