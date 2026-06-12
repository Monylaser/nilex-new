<?php

use App\Models\PlanEntitlement;
use App\Models\User;
use App\Models\UserEntitlement;
use App\Services\EntitlementService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    seedPlanEntitlementCatalog();
    $this->service = app(EntitlementService::class);
});

describe('Plan Upgrades', function () {

    it('upgrades entitlements when purchasing a higher tier', function () {
        $user = User::factory()->create();
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_STARTER));
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_BUSINESS));

        expect($user->fresh()->plan_tier)->toBe(PlanEntitlement::TIER_BUSINESS)
            ->and($this->service->hasFeature($user, EntitlementService::FEATURE_BUSINESS_BADGE))->toBeTrue()
            ->and($this->service->getLimit($user, EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT))->toBe(20);
    });

    it('does not downgrade when purchasing a lower tier after upgrade', function () {
        $user = User::factory()->create();
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_BUSINESS));
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_STARTER));

        expect($user->fresh()->plan_tier)->toBe(PlanEntitlement::TIER_BUSINESS)
            ->and($this->service->hasFeature($user, EntitlementService::FEATURE_BUSINESS_BADGE))->toBeTrue()
            ->and($this->service->getLimit($user, EntitlementService::FEATURE_FEATURED_LISTINGS_LIMIT))->toBe(10);
    });

    it('refreshes entitlements on same-tier repurchase without downgrade', function () {
        $user = User::factory()->create();
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_GROWTH));

        $before = UserEntitlement::query()->where('user_id', $user->id)->count();
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_GROWTH));

        expect(UserEntitlement::query()->where('user_id', $user->id)->count())->toBe($before)
            ->and($user->fresh()->plan_tier)->toBe(PlanEntitlement::TIER_GROWTH);
    });

});
