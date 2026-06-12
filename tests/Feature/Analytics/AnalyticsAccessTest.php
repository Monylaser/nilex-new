<?php

use App\Livewire\Frontend\UserDashboard;
use App\Models\PlanEntitlement;
use App\Models\User;
use App\Services\EntitlementService;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    seedPlanEntitlementCatalog();
});

describe('Analytics Access Control', function () {

    it('starter user cannot see analytics', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_STARTER));

        $service = app(EntitlementService::class);

        expect($service->hasFeature($user, EntitlementService::FEATURE_ANALYTICS_ACCESS))->toBeFalse();

        Livewire::actingAs($user)
            ->test(UserDashboard::class)
            ->assertSee(__('ui.analytics.upgrade_locked', ['tier' => __('ui.analytics.tiers.growth')]))
            ->assertSee(__('ui.analytics.upgrade_cta'));
    });

    it('growth user can see basic analytics', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_GROWTH));

        $service = app(EntitlementService::class);

        expect($service->hasFeature($user, EntitlementService::FEATURE_ANALYTICS_ACCESS))->toBeTrue()
            ->and($service->hasFeature($user, EntitlementService::FEATURE_ANALYTICS_CHARTS))->toBeFalse()
            ->and($service->hasFeature($user, EntitlementService::FEATURE_WHATSAPP_CLICKS_ACCESS))->toBeTrue()
            ->and($service->hasFeature($user, EntitlementService::FEATURE_PHONE_CLICKS_ACCESS))->toBeFalse();

        Livewire::actingAs($user)
            ->test(UserDashboard::class)
            ->assertSee(__('ui.analytics.verified_title'))
            ->assertSee(__('ui.analytics.upgrade_locked', ['tier' => __('ui.analytics.tiers.pro_seller')]));
    });

    it('pro_seller can see charts', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_PRO_SELLER));

        $service = app(EntitlementService::class);

        expect($service->hasFeature($user, EntitlementService::FEATURE_ANALYTICS_CHARTS))->toBeTrue()
            ->and($service->hasFeature($user, EntitlementService::FEATURE_PHONE_CLICKS_ACCESS))->toBeTrue();

        Livewire::actingAs($user)
            ->test(UserDashboard::class)
            ->assertSee(__('ui.analytics.chart_views_daily'))
            ->assertSee('viewsDailyChart');
    });

    it('business user can see all analytics features', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_BUSINESS));

        $service = app(EntitlementService::class);

        expect($service->hasFeature($user, EntitlementService::FEATURE_ANALYTICS_ACCESS))->toBeTrue()
            ->and($service->hasFeature($user, EntitlementService::FEATURE_ANALYTICS_CHARTS))->toBeTrue()
            ->and($service->hasFeature($user, EntitlementService::FEATURE_BUSINESS_DASHBOARD))->toBeTrue()
            ->and($service->hasFeature($user, EntitlementService::FEATURE_MONTHLY_REPORTS))->toBeTrue();
    });
});
