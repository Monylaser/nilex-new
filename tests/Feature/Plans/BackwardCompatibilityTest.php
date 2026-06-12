<?php

use App\Models\Listing;
use App\Models\PlanEntitlement;
use App\Models\User;
use App\Services\EntitlementService;
use App\Services\SellerListingAnalyticsService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    seedPlanEntitlementCatalog();
    $this->service = app(EntitlementService::class);
});

describe('Backward Compatibility', function () {

    it('treats legacy users without entitlements as grandfathered unlimited', function () {
        $user = User::factory()->create(['points' => 500]);

        expect($this->service->isLegacyGrandfathered($user))->toBeTrue()
            ->and($this->service->getLimit($user, EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT))->toBeNull()
            ->and($this->service->canUseFeature($user, EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT))->toBeTrue();
    });

    it('allows legacy users to boost listings without limit enforcement', function () {
        $user = User::factory()->create(['points' => 5000]);

        $category = \App\Models\Category::query()->create([
            'name_ar'   => 'خدمات',
            'name_en'   => 'Services',
            'slug'      => 'services-legacy',
            'is_active' => true,
        ]);

        foreach (range(1, 5) as $i) {
            $listing = Listing::query()->create([
                'title'       => "إعلان {$i}",
                'slug'        => "legacy-listing-{$i}",
                'description' => 'وصف.',
                'price'       => 1000,
                'category_id' => $category->id,
                'user_id'     => $user->id,
                'status'      => Listing::STATUS_PUBLISHED,
            ]);

            $listing->featureWithPoints(1);
            expect($listing->fresh()->is_featured)->toBeTrue();
        }
    });

    it('keeps seller analytics available for legacy users', function () {
        $user = User::factory()->create();

        $stats = app(SellerListingAnalyticsService::class)->getDashboardStats($user);

        expect($stats)->toHaveKeys(['views_events', 'phone_clicks', 'whatsapp_clicks_events']);
    });

    it('does not assign entitlements when plan tier cannot be resolved', function () {
        $user = User::factory()->create();
        $plan = \App\Models\PointPlan::query()->create([
            'name_ar'   => 'باقة مخصصة',
            'name_en'   => 'Custom Pack',
            'points'    => 50,
            'price'     => 10,
            'is_active' => true,
        ]);

        assignPlanToUser($user, $plan);

        expect($user->fresh()->plan_tier)->toBeNull()
            ->and($this->service->isLegacyGrandfathered($user))->toBeTrue();
    });

    it('preserves purchase flow event contract without modifying payment services', function () {
        $user = User::factory()->create(['points' => 150]);
        $plan = createTierPlan(PlanEntitlement::TIER_GROWTH);

        assignPlanToUser($user, $plan);

        expect($user->fresh()->plan_tier)->toBe(PlanEntitlement::TIER_GROWTH)
            ->and($user->fresh()->points)->toBe(150);
    });

});
