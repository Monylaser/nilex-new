<?php

use App\Livewire\Frontend\BusinessDashboard;
use App\Models\Category;
use App\Models\Listing;
use App\Models\PlanEntitlement;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    seedPlanEntitlementCatalog();

    $this->category = Category::query()->create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-business-' . uniqid(),
        'is_active' => true,
    ]);
});

describe('Business Dashboard', function () {

    it('redirects non-business user', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_GROWTH));

        Livewire::actingAs($user)
            ->test(BusinessDashboard::class)
            ->assertRedirect(route('dashboard'));
    });

    it('business user sees dashboard', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_BUSINESS));

        createPublishedListing($user, $this->category);

        Livewire::actingAs($user)
            ->test(BusinessDashboard::class)
            ->assertSee(__('ui.analytics.business_title'))
            ->assertSee(__('ui.analytics.top_listings'))
            ->assertStatus(200);
    });

    it('CSV export works', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_BUSINESS));

        $listing = createPublishedListing($user, $this->category);
        $listing->update([
            'title'           => 'Business Export Test Listing',
            'views_count'     => 100,
            'whatsapp_clicks' => 10,
        ]);

        Livewire::actingAs($user)
            ->test(BusinessDashboard::class)
            ->call('exportCsv')
            ->assertFileDownloaded('business-analytics-' . now()->format('Y-m-d') . '.csv');
    });
});
