<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * TD-01 Phase 2: SellerListingAnalyticsService
 *   - Event-based totals scoped to seller-owned listings
 *   - Multi-listing aggregation for views, phone clicks, WhatsApp clicks
 *   - Isolation from other sellers' events
 *   - Additive dashboard stats keys (legacy keys unchanged)
 */

use App\Livewire\Frontend\UserDashboard;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingPhoneClick;
use App\Models\ListingView;
use App\Models\ListingWhatsappClick;
use App\Models\User;
use App\Services\SellerListingAnalyticsService;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeSellerListing(User $seller, Category $category, array $overrides = []): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create(array_merge([
        'title'           => fake()->sentence(3),
        'slug'            => 'analytics-listing-' . $counter . '-' . uniqid(),
        'description'     => fake()->paragraph(),
        'price'           => fake()->randomNumber(5),
        'category_id'     => $category->id,
        'user_id'         => $seller->id,
        'status'          => Listing::STATUS_PUBLISHED,
        'views_count'     => 0,
        'whatsapp_clicks' => 0,
    ], $overrides));
}

function recordView(Listing $listing, ?User $viewer = null): void
{
    ListingView::query()->create([
        'listing_id' => $listing->id,
        'user_id'    => $viewer?->id,
        'ip_address' => $viewer === null ? '127.0.0.1' : null,
    ]);
}

function recordPhoneClick(Listing $listing, ?User $clicker = null): void
{
    ListingPhoneClick::query()->create([
        'listing_id' => $listing->id,
        'user_id'    => $clicker?->id,
    ]);
}

function recordWhatsappClick(Listing $listing, ?User $clicker = null): void
{
    ListingWhatsappClick::query()->create([
        'listing_id' => $listing->id,
        'user_id'    => $clicker?->id,
    ]);
}

describe('SellerListingAnalyticsService (TD-01 Phase 2)', function () {

    beforeEach(function () {
        $this->seller = User::factory()->create(['is_phone_verified' => true]);
        $this->buyer  = User::factory()->create(['is_phone_verified' => true]);

        $this->category = Category::create([
            'name_ar'   => 'إلكترونيات',
            'name_en'   => 'Electronics',
            'slug'      => 'electronics-analytics',
            'is_active' => true,
        ]);

        $this->service = app(SellerListingAnalyticsService::class);
    });

    it('returns zero event totals when seller has no listings', function () {
        expect($this->service->totalViewsForUser($this->seller))->toBe(0)
            ->and($this->service->totalPhoneClicksForUser($this->seller))->toBe(0)
            ->and($this->service->totalWhatsappClicksForUser($this->seller))->toBe(0);
    });

    it('returns zero event totals when seller listings have no events', function () {
        makeSellerListing($this->seller, $this->category);
        makeSellerListing($this->seller, $this->category);

        expect($this->service->totalViewsForUser($this->seller))->toBe(0)
            ->and($this->service->totalPhoneClicksForUser($this->seller))->toBe(0)
            ->and($this->service->totalWhatsappClicksForUser($this->seller))->toBe(0);
    });

    it('aggregates views across multiple seller listings', function () {
        $listingA = makeSellerListing($this->seller, $this->category);
        $listingB = makeSellerListing($this->seller, $this->category);

        recordView($listingA, $this->buyer);
        recordView($listingA, User::factory()->create());
        recordView($listingB, $this->buyer);

        expect($this->service->totalViewsForUser($this->seller))->toBe(3);
    });

    it('aggregates phone clicks across multiple seller listings', function () {
        $listingA = makeSellerListing($this->seller, $this->category);
        $listingB = makeSellerListing($this->seller, $this->category);

        recordPhoneClick($listingA, $this->buyer);
        recordPhoneClick($listingB, $this->buyer);
        recordPhoneClick($listingB, User::factory()->create());

        expect($this->service->totalPhoneClicksForUser($this->seller))->toBe(3);
    });

    it('aggregates whatsapp clicks across multiple seller listings', function () {
        $listingA = makeSellerListing($this->seller, $this->category);
        $listingB = makeSellerListing($this->seller, $this->category);

        recordWhatsappClick($listingA, $this->buyer);
        recordWhatsappClick($listingB, $this->buyer);
        recordWhatsappClick($listingB, $this->buyer);

        expect($this->service->totalWhatsappClicksForUser($this->seller))->toBe(3);
    });

    it('does not count events on listings owned by other sellers', function () {
        $otherSeller = User::factory()->create();
        $ownListing  = makeSellerListing($this->seller, $this->category);
        $otherListing = makeSellerListing($otherSeller, $this->category);

        recordView($ownListing, $this->buyer);
        recordPhoneClick($ownListing, $this->buyer);
        recordWhatsappClick($ownListing, $this->buyer);

        recordView($otherListing, $this->buyer);
        recordPhoneClick($otherListing, $this->buyer);
        recordWhatsappClick($otherListing, $this->buyer);
        recordView($otherListing, User::factory()->create());

        expect($this->service->totalViewsForUser($this->seller))->toBe(1)
            ->and($this->service->totalPhoneClicksForUser($this->seller))->toBe(1)
            ->and($this->service->totalWhatsappClicksForUser($this->seller))->toBe(1);
    });

    it('getDashboardStats returns all event-based keys', function () {
        $listing = makeSellerListing($this->seller, $this->category);

        recordView($listing, $this->buyer);
        recordPhoneClick($listing, $this->buyer);
        recordWhatsappClick($listing, $this->buyer);
        recordWhatsappClick($listing, User::factory()->create());

        $stats = $this->service->getDashboardStats($this->seller);

        expect($stats)->toBe([
            'views_events'           => 1,
            'phone_clicks'           => 1,
            'whatsapp_clicks_events' => 2,
        ]);
    });
});

describe('User Dashboard event stats (TD-01 Phase 2)', function () {

    beforeEach(function () {
        $this->seller = User::factory()->create(['is_phone_verified' => true]);
        $this->buyer  = User::factory()->create(['is_phone_verified' => true]);

        $this->category = Category::create([
            'name_ar'   => 'إلكترونيات',
            'name_en'   => 'Electronics',
            'slug'      => 'electronics-dashboard-events',
            'is_active' => true,
        ]);
    });

    it('exposes additive event stats while preserving legacy stats keys', function () {
        $listingA = makeSellerListing($this->seller, $this->category, [
            'views_count'     => 100,
            'whatsapp_clicks' => 30,
        ]);
        $listingB = makeSellerListing($this->seller, $this->category, [
            'views_count'     => 50,
            'whatsapp_clicks' => 20,
        ]);

        recordView($listingA, $this->buyer);
        recordView($listingB, $this->buyer);
        recordPhoneClick($listingA, $this->buyer);
        recordWhatsappClick($listingB, $this->buyer);

        Livewire::actingAs($this->seller)
            ->test(UserDashboard::class)
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['views'] === 150
                    && $stats['clicks'] === 50
                    && $stats['views_events'] === 2
                    && $stats['phone_clicks'] === 1
                    && $stats['whatsapp_clicks_events'] === 1;
            });
    });
});
