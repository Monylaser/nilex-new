<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\PlanEntitlement;
use App\Models\PointPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserEntitlement;
use App\Models\UserEntitlementUsage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EntitlementService
{
    public const FEATURE_FEATURED_LISTINGS_LIMIT = 'featured_listings_limit';

    public const FEATURE_MONTHLY_BOOST_LIMIT = 'monthly_boost_limit';

    public const FEATURE_SEARCH_PRIORITY = 'search_priority';

    public const FEATURE_HOME_PROMOTION = 'home_promotion';

    public const FEATURE_BUSINESS_BADGE = 'business_badge';

    public const FEATURE_PRIORITY_SUPPORT = 'priority_support';

    public const FEATURE_ANALYTICS_ACCESS = 'analytics_access';

    public const FEATURE_ANALYTICS_CHARTS = 'analytics_charts';

    public const FEATURE_PHONE_CLICKS_ACCESS = 'phone_clicks_access';

    public const FEATURE_WHATSAPP_CLICKS_ACCESS = 'whatsapp_clicks_access';

    public const FEATURE_EVENT_VIEWS_ACCESS = 'event_views_access';

    public const FEATURE_BUSINESS_DASHBOARD = 'business_dashboard';

    public const FEATURE_MONTHLY_REPORTS = 'monthly_reports';

    private const LIMIT_FEATURES = [
        self::FEATURE_FEATURED_LISTINGS_LIMIT,
        self::FEATURE_MONTHLY_BOOST_LIMIT,
    ];

    private const BOOLEAN_FEATURES = [
        self::FEATURE_SEARCH_PRIORITY,
        self::FEATURE_HOME_PROMOTION,
        self::FEATURE_BUSINESS_BADGE,
        self::FEATURE_PRIORITY_SUPPORT,
        self::FEATURE_ANALYTICS_ACCESS,
        self::FEATURE_ANALYTICS_CHARTS,
        self::FEATURE_PHONE_CLICKS_ACCESS,
        self::FEATURE_WHATSAPP_CLICKS_ACCESS,
        self::FEATURE_EVENT_VIEWS_ACCESS,
        self::FEATURE_BUSINESS_DASHBOARD,
        self::FEATURE_MONTHLY_REPORTS,
    ];

    public function hasFeature(User $user, string $featureKey): bool
    {
        if ($this->isLegacyGrandfathered($user)) {
            return $featureKey === self::FEATURE_HOME_PROMOTION;
        }

        $entitlement = $this->getEntitlementRecord($user, $featureKey);

        if (! $entitlement || $entitlement->isExpired()) {
            return false;
        }

        $value = $entitlement->castValue();

        return is_bool($value) ? $value : (bool) $value;
    }

    public function canUseFeature(User $user, string $featureKey, ?Listing $listing = null): bool
    {
        if (in_array($featureKey, self::BOOLEAN_FEATURES, true)) {
            return $this->hasFeature($user, $featureKey);
        }

        if (! in_array($featureKey, self::LIMIT_FEATURES, true)) {
            return false;
        }

        $remaining = $this->remainingUsage($user, $featureKey, $listing);

        return $remaining === null || $remaining > 0;
    }

    public function remainingUsage(User $user, string $featureKey, ?Listing $listing = null): ?int
    {
        $limit = $this->getLimit($user, $featureKey);

        if ($limit === null) {
            return null;
        }

        $used = match ($featureKey) {
            self::FEATURE_FEATURED_LISTINGS_LIMIT => $this->activeFeaturedCount($user, $listing),
            self::FEATURE_MONTHLY_BOOST_LIMIT     => $this->currentPeriodUsage($user, $featureKey),
            default                               => 0,
        };

        return max(0, $limit - $used);
    }

    public function getLimit(User $user, string $featureKey): ?int
    {
        if ($this->isLegacyGrandfathered($user)) {
            return null;
        }

        $entitlement = $this->getEntitlementRecord($user, $featureKey);

        if (! $entitlement || $entitlement->isExpired()) {
            return null;
        }

        if ($entitlement->value_type !== PlanEntitlement::TYPE_INTEGER) {
            return null;
        }

        return (int) $entitlement->castValue();
    }

    public function assignFromPlan(User $user, PointPlan $plan, Transaction $transaction): void
    {
        $tier = $plan->resolveTierKey();

        if ($tier === null) {
            return;
        }

        $currentRank  = PlanEntitlement::tierRank($user->plan_tier);
        $incomingRank = PlanEntitlement::tierRank($tier);

        if ($currentRank > $incomingRank) {
            return;
        }

        $userUpdates = [
            'plan_type' => $plan->plan_type ?? PointPlan::PLAN_TYPE_INDIVIDUAL,
        ];

        if ($incomingRank > $currentRank) {
            $userUpdates['plan_tier'] = $tier;
        }

        $user->update($userUpdates);

        $planEntitlements = PlanEntitlement::query()
            ->where('plan_tier', $tier)
            ->get();

        foreach ($planEntitlements as $planEntitlement) {
            UserEntitlement::query()->updateOrCreate(
                [
                    'user_id'     => $user->id,
                    'feature_key' => $planEntitlement->feature_key,
                ],
                [
                    'value_type'              => $planEntitlement->value_type,
                    'value'                   => $planEntitlement->value,
                    'source'                  => UserEntitlement::SOURCE_PURCHASE,
                    'source_plan_id'          => $plan->id,
                    'source_transaction_id'   => $transaction->id,
                    'granted_at'              => now(),
                    'expires_at'              => null,
                ],
            );
        }

        $this->clearUserCache($user);
    }

    public function recordUsage(User $user, string $featureKey, int $amount = 1): void
    {
        if ($amount <= 0) {
            return;
        }

        $usage = UserEntitlementUsage::query()->firstOrCreate(
            [
                'user_id'     => $user->id,
                'feature_key' => $featureKey,
                'period_key'  => $this->currentPeriodKey(),
            ],
            ['used_count' => 0],
        );

        $usage->increment('used_count', $amount);
        $this->clearUserCache($user);
    }

    public function resolveTier(User $user): ?string
    {
        return $user->plan_tier;
    }

    public function getUserEntitlements(User $user): Collection
    {
        return $this->loadUserEntitlements($user)
            ->map(fn (UserEntitlement $entitlement) => [
                'feature_key' => $entitlement->feature_key,
                'value_type'  => $entitlement->value_type,
                'value'       => $entitlement->castValue(),
                'source'      => $entitlement->source,
                'granted_at'  => $entitlement->granted_at,
                'expires_at'  => $entitlement->expires_at,
            ]);
    }

    public function isLegacyGrandfathered(User $user): bool
    {
        return ! UserEntitlement::query()->where('user_id', $user->id)->exists();
    }

    public function activeFeaturedCount(User $user, ?Listing $excluding = null): int
    {
        $query = Listing::query()
            ->where('user_id', $user->id)
            ->where('is_featured', true)
            ->where('featured_until', '>=', now());

        if ($excluding !== null) {
            $query->whereKeyNot($excluding->id);
        }

        return (int) $query->count();
    }

    private function getEntitlementRecord(User $user, string $featureKey): ?UserEntitlement
    {
        return $this->loadUserEntitlements($user)
            ->first(fn (UserEntitlement $entitlement) => $entitlement->feature_key === $featureKey);
    }

    private function loadUserEntitlements(User $user): Collection
    {
        return Cache::remember(
            $this->cacheKey($user),
            300,
            fn () => UserEntitlement::query()
                ->where('user_id', $user->id)
                ->get(),
        );
    }

    private function currentPeriodUsage(User $user, string $featureKey): int
    {
        return (int) UserEntitlementUsage::query()
            ->where('user_id', $user->id)
            ->where('feature_key', $featureKey)
            ->where('period_key', $this->currentPeriodKey())
            ->value('used_count');
    }

    private function currentPeriodKey(): string
    {
        return now()->format('Y-m');
    }

    private function cacheKey(User $user): string
    {
        return "entitlements:user:{$user->id}";
    }

    private function clearUserCache(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }
}
