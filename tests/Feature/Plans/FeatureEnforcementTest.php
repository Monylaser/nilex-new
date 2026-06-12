<?php

use App\Models\Category;
use App\Models\Listing;
use App\Models\PlanEntitlement;
use App\Models\User;
use App\Services\EntitlementService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    seedPlanEntitlementCatalog();
    $this->service = app(EntitlementService::class);
});

describe('Feature Enforcement', function () {

    it('shows business badge only for business tier sellers', function () {
        $businessUser = User::factory()->create();
        assignPlanToUser($businessUser, createTierPlan(PlanEntitlement::TIER_BUSINESS));

        $starterUser = User::factory()->create();
        assignPlanToUser($starterUser, createTierPlan(PlanEntitlement::TIER_STARTER));

        expect($this->service->hasFeature($businessUser, EntitlementService::FEATURE_BUSINESS_BADGE))->toBeTrue()
            ->and($this->service->hasFeature($starterUser, EntitlementService::FEATURE_BUSINESS_BADGE))->toBeFalse();
    });

    it('prioritizes search results for entitled sellers', function () {
        $category = Category::query()->create([
            'name_ar'   => 'سيارات',
            'name_en'   => 'Cars',
            'slug'      => 'cars-search',
            'is_active' => true,
        ]);

        $prioritySeller = User::factory()->create();
        assignPlanToUser($prioritySeller, createTierPlan(PlanEntitlement::TIER_GROWTH));

        $regularSeller = User::factory()->create();
        assignPlanToUser($regularSeller, createTierPlan(PlanEntitlement::TIER_STARTER));

        $priorityListing = Listing::query()->create([
            'title'       => 'سيارة أولوية',
            'slug'        => 'priority-car',
            'description' => 'وصف.',
            'price'       => 100_000,
            'category_id' => $category->id,
            'user_id'     => $prioritySeller->id,
            'status'      => Listing::STATUS_PUBLISHED,
        ]);

        $regularListing = Listing::query()->create([
            'title'       => 'سيارة عادية',
            'slug'        => 'regular-car',
            'description' => 'وصف.',
            'price'       => 90_000,
            'category_id' => $category->id,
            'user_id'     => $regularSeller->id,
            'status'      => Listing::STATUS_PUBLISHED,
        ]);

        $priorityListing->searchable();
        $regularListing->searchable();

        $response = $this->get('/search?q=سيارة');

        $response->assertOk();
        $body = $response->getContent();

        expect($body)->toContain('سيارة أولوية')
            ->and($body)->toContain('سيارة عادية')
            ->and(strpos($body, 'سيارة أولوية'))->toBeLessThan(strpos($body, 'سيارة عادية'));
    });

    it('exposes priority_support flag for business users in service layer', function () {
        $user = User::factory()->create();
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_BUSINESS));

        expect($this->service->hasFeature($user, EntitlementService::FEATURE_PRIORITY_SUPPORT))->toBeTrue();
    });

});
