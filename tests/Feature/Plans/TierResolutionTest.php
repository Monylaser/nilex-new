<?php

use App\Models\PlanEntitlement;
use App\Models\PointPlan;
use App\Models\User;
use App\Services\EntitlementService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    seedPlanEntitlementCatalog();
    $this->service = app(EntitlementService::class);
});

describe('Tier Resolution', function () {

    it('resolves tier_key from explicit column', function () {
        $plan = PointPlan::query()->create([
            'name_ar'   => 'Custom',
            'name_en'   => 'Custom Plan',
            'points'    => 50,
            'price'     => 25,
            'tier_key'  => PlanEntitlement::TIER_PRO_SELLER,
            'is_active' => true,
        ]);

        expect($plan->resolveTierKey())->toBe(PlanEntitlement::TIER_PRO_SELLER);
    });

    it('falls back to name heuristics when tier_key is null', function () {
        $plan = PointPlan::query()->create([
            'name_ar'   => 'باقة أعمال',
            'name_en'   => 'Business Pack',
            'points'    => 1500,
            'price'     => 499,
            'is_active' => true,
        ]);

        expect($plan->resolveTierKey())->toBe(PlanEntitlement::TIER_BUSINESS);
    });

    it('resolves user tier after plan purchase', function () {
        $user = User::factory()->create();
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_GROWTH));

        expect($this->service->resolveTier($user->fresh()))->toBe(PlanEntitlement::TIER_GROWTH);
    });

});
