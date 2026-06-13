<?php

namespace App\Services;

use App\Jobs\TrackAdImpressionJob;
use App\Models\AdCampaign;
use App\Models\AdCampaignLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdCampaignService
{
    public function getForPlacement(string $placement, ?int $categoryId = null): ?AdCampaign
    {
        $cacheKey = $placement === 'category_page'
            ? 'ad_campaign_category_page_' . $categoryId
            : 'ad_campaign_' . $placement;

        return Cache::remember($cacheKey, 300, function () use ($placement, $categoryId) {
            return AdCampaign::query()
                ->active()
                ->where('placement', $placement)
                ->when(
                    $placement === 'category_page',
                    fn ($query) => $query->where('category_id', $categoryId)
                )
                ->orderByDesc('priority')
                ->inRandomOrder()
                ->with('media')
                ->first();
        });
    }

    public function trackImpression(AdCampaign $campaign, Request $request): void
    {
        $ip     = $request->ip();
        $uaHash = md5($request->userAgent() ?? '');
        $key    = "ad_imp_{$campaign->id}_{$ip}_{$uaHash}";

        if (Cache::has($key)) {
            return;
        }

        Cache::put($key, true, 3600);

        TrackAdImpressionJob::dispatch(
            $campaign->id,
            $ip,
            $request->userAgent(),
            auth()->id()
        );
    }

    public function trackClick(AdCampaign $campaign, Request $request): string
    {
        $ip     = $request->ip();
        $uaHash = md5($request->userAgent() ?? '');
        $key    = "ad_clk_{$campaign->id}_{$ip}_{$uaHash}";

        if (Cache::has($key)) {
            return $campaign->target_url ?? route('home');
        }

        Cache::put($key, true, 3600);

        $campaign->increment('clicks_count');

        AdCampaignLog::create([
            'ad_campaign_id' => $campaign->id,
            'user_id'        => auth()->id(),
            'ip_address'     => $ip,
            'user_agent'     => $request->userAgent(),
            'event_type'     => 'click',
        ]);

        return $campaign->target_url ?? route('home');
    }

    public function getDailyStats(int $days = 30): Collection
    {
        return AdCampaignLog::query()
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->selectRaw('DATE(created_at) as date, event_type, COUNT(*) as count')
            ->groupBy('date', 'event_type')
            ->orderBy('date')
            ->get();
    }

    public function getTopCampaigns(int $limit = 10): Collection
    {
        return AdCampaign::query()
            ->orderByDesc('clicks_count')
            ->limit($limit)
            ->get();
    }

    public function getPendingCampaigns(): Collection
    {
        return AdCampaign::query()
            ->pending()
            ->with(['createdBy', 'media'])
            ->latest()
            ->get();
    }

    public function approveCampaign(AdCampaign $campaign, int $adminId): void
    {
        DB::transaction(function () use ($campaign, $adminId) {
            $campaign->approve();

            Log::info('Ad Campaign Approved', [
                'campaign_id' => $campaign->id,
                'admin_id'    => $adminId,
            ]);
        });

        $this->clearCampaignCache($campaign);
    }

    public function rejectCampaign(AdCampaign $campaign, int $adminId, string $reason): void
    {
        DB::transaction(function () use ($campaign, $adminId, $reason) {
            $campaign->reject($reason);

            Log::info('Ad Campaign Rejected', [
                'campaign_id' => $campaign->id,
                'admin_id'    => $adminId,
                'reason'      => $reason,
            ]);
        });

        $this->clearCampaignCache($campaign);
    }

    private function clearCampaignCache(AdCampaign $campaign): void
    {
        if ($campaign->placement === 'category_page') {
            Cache::forget('ad_campaign_category_page_' . ($campaign->category_id ?? 'all'));
        } else {
            Cache::forget('ad_campaign_' . $campaign->placement);
        }
    }
}
