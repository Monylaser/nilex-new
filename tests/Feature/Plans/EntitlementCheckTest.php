<?php

use App\Models\PlanEntitlement;
use App\Models\User;
use App\Services\EntitlementService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    seedPlanEntitlementCatalog();
    $this->service = app(EntitlementService::class);
});

describe('Entitlement Checks', function () {

    it('returns correct boolean features for business tier', function () {
        $user = User::factory()->create();
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_BUSINESS));

        expect($this->service->hasFeature($user, EntitlementService::FEATURE_BUSINESS_BADGE))->toBeTrue()
            ->and($this->service->hasFeature($user, EntitlementService::FEATURE_PRIORITY_SUPPORT))->toBeTrue()
            ->and($this->service->hasFeature($user, EntitlementService::FEATURE_SEARCH_PRIORITY))->toBeTrue();
    });

    it('returns false for premium booleans on starter tier', function () {
        $user = User::factory()->create();
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_STARTER));

        expect($this->service->hasFeature($user, EntitlementService::FEATURE_BUSINESS_BADGE))->toBeFalse()
            ->and($this->service->hasFeature($user, EntitlementService::FEATURE_SEARCH_PRIORITY))->toBeFalse();
    });

    it('returns tier limits via getLimit and remainingUsage', function () {
        $user = User::factory()->create();
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_STARTER));

        expect($this->service->getLimit($user, EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT))->toBe(2)
            ->and($this->service->remainingUsage($user, EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT))->toBe(2)
            ->and($this->service->canUseFeature($user, EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT))->toBeTrue();
    });

    it('returns all user entitlements via getUserEntitlements', function () {
        $user = User::factory()->create();
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_GROWTH));

        $entitlements = $this->service->getUserEntitlements($user);

        expect($entitlements)->toHaveCount(13)
            ->and($entitlements->pluck('feature_key'))->toContain(EntitlementService::FEATURE_SEARCH_PRIORITY);
    });

});
