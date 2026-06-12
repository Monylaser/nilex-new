<?php

use App\Livewire\Frontend\UserDashboard;
use App\Models\PlanEntitlement;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    seedPlanEntitlementCatalog();
});

describe('Upgrade Prompt Component', function () {

    it('shows upgrade prompt for locked analytics on starter tier', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_STARTER));

        Livewire::actingAs($user)
            ->test(UserDashboard::class)
            ->assertSee('🔒')
            ->assertSee(__('ui.analytics.upgrade_locked', ['tier' => __('ui.analytics.tiers.growth')]));
    });

    it('shows correct tier in prompt for charts', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_GROWTH));

        Livewire::actingAs($user)
            ->test(UserDashboard::class)
            ->assertSee(__('ui.analytics.upgrade_locked', ['tier' => __('ui.analytics.tiers.pro_seller')]));
    });

    it('CTA links to pricing page', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_STARTER));

        Livewire::actingAs($user)
            ->test(UserDashboard::class)
            ->assertSee(route('pricing', [], false))
            ->assertSee(__('ui.analytics.upgrade_cta'));
    });
});
