<?php

use App\Models\PlanEntitlement;
use App\Models\User;
use App\Models\UserEntitlementUsage;
use App\Services\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedPlanEntitlementCatalog();
    $this->service = app(EntitlementService::class);
});

describe('Monthly Usage Tracking', function () {

    it('records boost usage in the current period', function () {
        $user = User::factory()->create(['points' => 500]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_STARTER));

        $listing = createPublishedListing($user);
        $listing->featureWithPoints(1);

        $usage = UserEntitlementUsage::query()
            ->where('user_id', $user->id)
            ->where('feature_key', EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT)
            ->where('period_key', now()->format('Y-m'))
            ->first();

        expect($usage)->not->toBeNull()
            ->and($usage->used_count)->toBe(1);
    });

    it('blocks boosts when monthly limit is exceeded', function () {
        $user = User::factory()->create(['points' => 5000]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_STARTER));

        $listing = createPublishedListing($user);
        $listing->featureWithPoints(1);
        $listing->featureWithPoints(1);

        expect(fn () => $listing->featureWithPoints(1))->toThrow(Exception::class);
    });

    it('decrements remaining featured listing slots', function () {
        $user = User::factory()->create(['points' => 5000]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_STARTER));

        expect($this->service->remainingUsage($user, EntitlementService::FEATURE_FEATURED_LISTINGS_LIMIT))->toBe(1);

        $listing = createPublishedListing($user);
        $listing->featureWithPoints(1);

        expect($this->service->remainingUsage($user, EntitlementService::FEATURE_FEATURED_LISTINGS_LIMIT))->toBe(0);
    });

    it('recordUsage enforces monthly boost limit under row lock', function () {
        $user = User::factory()->create();
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_STARTER));

        $this->service->recordUsage($user, EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT);
        $this->service->recordUsage($user, EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT);

        $usage = UserEntitlementUsage::query()
            ->where('user_id', $user->id)
            ->where('feature_key', EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT)
            ->where('period_key', now()->format('Y-m'))
            ->first();

        expect($usage)->not->toBeNull()
            ->and($usage->used_count)->toBe(2);

        expect(fn () => $this->service->recordUsage($user, EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT))
            ->toThrow(Exception::class);
    });

});
