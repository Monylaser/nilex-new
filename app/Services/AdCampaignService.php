<?php

namespace App\Services;

use App\Jobs\InvalidateAdCampaignCacheJob;
use App\Jobs\TrackAdClickJob;
use App\Jobs\TrackAdImpressionJob;
use App\Models\AdCampaign;
use App\Models\AdCampaignAuditLog;
use App\Models\AdCampaignLog;
use App\Notifications\AdCampaignApprovedNotification;
use App\Notifications\AdCampaignRejectedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdCampaignService
{
    private const PLACEMENT_CACHE_TTL_SECONDS = 300;

    private const TRACKING_DEDUP_TTL_MINUTES = 60;

    public static function selfServiceEnabled(): bool
    {
        return (bool) config('features.self_service_ads', false);
    }

    public function placementCacheKey(string $placement, ?int $categoryId = null): string
    {
        return $placement === 'category_page'
            ? 'ad_campaign_category_page_' . ($categoryId ?? 'all')
            : 'ad_campaign_' . $placement;
    }

    public function getForPlacement(string $placement, ?int $categoryId = null): ?AdCampaign
    {
        if (app()->environment('local')) {
            return $this->fetchFromDb($placement, $categoryId);
        }

        $cacheKey = $this->placementCacheKey($placement, $categoryId);

        $campaign = Cache::remember($cacheKey, self::PLACEMENT_CACHE_TTL_SECONDS, function () use ($placement, $categoryId) {
            return $this->fetchFromDb($placement, $categoryId);
        });

        if ($campaign instanceof AdCampaign && ! $campaign->isDisplayable()) {
            Cache::forget($cacheKey);

            return null;
        }

        return $campaign;
    }

    private function fetchFromDb(string $placement, ?int $categoryId = null): ?AdCampaign
    {
        return AdCampaign::query()
            ->where('placement', $placement)
            ->when(
                self::selfServiceEnabled(),
                fn ($builder) => $builder->displayable()->where('status', 'active'),
                fn ($builder) => $builder->active(),
            )
            ->when(
                $placement === 'category_page',
                fn ($builder) => $builder->where('category_id', $categoryId)
            )
            ->orderByDesc('priority')
            ->inRandomOrder()
            ->with('media')
            ->limit(1)
            ->first();
    }

    public function trackImpression(AdCampaign $campaign, Request $request): void
    {
        if (! $campaign->isTrackable()) {
            return;
        }

        $ip     = $request->ip();
        $uaHash = md5($request->userAgent() ?? '');
        $key    = "ad_imp_{$campaign->id}_{$ip}_{$uaHash}";

        if (Cache::has($key)) {
            return;
        }

        Cache::put($key, true, now()->addMinutes(self::TRACKING_DEDUP_TTL_MINUTES));

        TrackAdImpressionJob::dispatch(
            $campaign->id,
            $ip,
            $request->userAgent(),
            auth()->id()
        );
    }

    public function trackClick(AdCampaign $campaign, Request $request): string
    {
        $targetUrl = $campaign->target_url ?? route('home');

        if (! $campaign->isTrackable()) {
            return $targetUrl;
        }

        $ip     = $request->ip();
        $uaHash = md5($request->userAgent() ?? '');
        $key    = "ad_clk_{$campaign->id}_{$ip}_{$uaHash}";

        if (Cache::has($key)) {
            return $targetUrl;
        }

        Cache::put($key, true, now()->addMinutes(self::TRACKING_DEDUP_TTL_MINUTES));

        TrackAdClickJob::dispatch(
            $campaign->id,
            $ip,
            $request->userAgent(),
            auth()->id()
        );

        return $targetUrl;
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
        $oldValues = $this->auditSnapshot($campaign);

        $durationDays = (int) ($campaign->duration_days ?? 7);
        $startsAt     = now();
        $endsAt       = $startsAt->copy()->addDays($durationDays);

        DB::transaction(function () use ($campaign, $adminId, $oldValues, $startsAt, $endsAt) {
            $campaign->update([
                'approval_status' => 'approved',
                'rejected_reason' => null,
                'status'          => 'active',
                'starts_at'       => $startsAt,
                'ends_at'         => $endsAt,
            ]);

            AdCampaignAuditLog::create([
                'campaign_id' => $campaign->id,
                'admin_id'    => $adminId,
                'action'      => 'approve',
                'old_values'  => $oldValues,
                'new_values'  => $this->auditSnapshot($campaign->fresh()),
            ]);

            Log::info('Ad Campaign Approved', [
                'campaign_id' => $campaign->id,
                'admin_id'    => $adminId,
            ]);
        });

        $campaign->refresh();
        $this->notifySeller($campaign, new AdCampaignApprovedNotification($campaign));
    }

    public function rejectCampaign(AdCampaign $campaign, int $adminId, string $reason): void
    {
        $oldValues = $this->auditSnapshot($campaign);

        DB::transaction(function () use ($campaign, $adminId, $reason, $oldValues) {
            $campaign->update([
                'approval_status' => 'rejected',
                'rejected_reason' => $reason,
            ]);

            AdCampaignAuditLog::create([
                'campaign_id' => $campaign->id,
                'admin_id'    => $adminId,
                'action'      => 'reject',
                'old_values'  => $oldValues,
                'new_values'  => $this->auditSnapshot($campaign->fresh()),
            ]);

            Log::info('Ad Campaign Rejected', [
                'campaign_id' => $campaign->id,
                'admin_id'    => $adminId,
                'reason'      => $reason,
            ]);
        });

        $campaign->refresh();
        $this->notifySeller($campaign, new AdCampaignRejectedNotification($campaign, $reason));
    }

    public function queueCampaignCacheInvalidation(AdCampaign $campaign): void
    {
        InvalidateAdCampaignCacheJob::dispatch(
            $campaign->placement,
            $campaign->category_id,
            $campaign->wasChanged('placement') ? $campaign->getOriginal('placement') : null,
            $campaign->wasChanged('category_id') ? $campaign->getOriginal('category_id') : null,
        );
    }

    /**
     * @return list<string>
     */
    public function resolveCacheKeysForCampaign(
        string $placement,
        ?int $categoryId = null,
        ?string $previousPlacement = null,
        ?int $previousCategoryId = null,
    ): array {
        $keys = [$this->placementCacheKey($placement, $categoryId)];

        if ($previousPlacement !== null && $previousPlacement !== $placement) {
            $keys[] = $this->placementCacheKey(
                $previousPlacement,
                $previousPlacement === 'category_page' ? $previousCategoryId : null,
            );
        }

        if ($previousCategoryId !== null && $placement === 'category_page' && $previousCategoryId !== $categoryId) {
            $keys[] = $this->placementCacheKey('category_page', $previousCategoryId);
        }

        return array_values(array_unique($keys));
    }

    public function flushCacheKeys(array $cacheKeys): void
    {
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    public function clearCampaignCache(AdCampaign $campaign): void
    {
        $this->flushCacheKeys($this->resolveCacheKeysForCampaign(
            $campaign->placement,
            $campaign->category_id,
            $campaign->wasChanged('placement') ? $campaign->getOriginal('placement') : null,
            $campaign->wasChanged('category_id') ? $campaign->getOriginal('category_id') : null,
        ));
    }

    public function clearPlacementCache(string $placement, ?int $categoryId = null): void
    {
        Cache::forget($this->placementCacheKey($placement, $categoryId));
    }

    private function auditSnapshot(AdCampaign $campaign): array
    {
        return $campaign->only([
            'approval_status',
            'rejected_reason',
            'status',
            'starts_at',
            'ends_at',
            'payment_status',
            'amount_paid',
        ]);
    }

    private function notifySeller(AdCampaign $campaign, object $notification): void
    {
        $seller = $campaign->seller;

        if ($seller !== null) {
            $seller->notify($notification);
        }
    }
}
