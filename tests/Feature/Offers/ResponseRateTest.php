<?php

/**
 * Nilex Platform — Seller Response Rate
 *
 * Covers the response-rate analytics built on the offers.responded_at column:
 *   - correct rate calculation (decided / received)
 *   - canceled offers excluded from BOTH numerator and denominator
 *   - statistical minimum (< 5 non-canceled offers => null = "insufficient data")
 *   - responded_at is written once and NOT overwritten on repeated accept/reject
 */

use App\Livewire\Frontend\UserDashboard;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use App\Services\SellerListingAnalyticsService;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ═══════════════════════════════════════════════════════════════════════════
// Helpers
// ═══════════════════════════════════════════════════════════════════════════

function rrCategory(): Category
{
    return Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-rr-' . uniqid(),
        'is_active' => true,
    ]);
}

function rrListing(User $seller, Category $category): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create([
        'title'           => 'RR listing ' . $counter,
        'slug'            => 'rr-listing-' . $counter . '-' . uniqid(),
        'description'     => 'desc',
        'price'           => 1000,
        'category_id'     => $category->id,
        'user_id'         => $seller->id,
        'status'          => Listing::STATUS_PUBLISHED,
        'views_count'     => 0,
        'whatsapp_clicks' => 0,
    ]);
}

function rrOffer(User $seller, User $buyer, Listing $listing, string $status, array $overrides = []): Offer
{
    // created_at is guarded (not fillable) and Eloquent stamps it on insert,
    // so timestamp overrides must be force-applied after creation.
    $timestamps = array_intersect_key($overrides, array_flip(['created_at', 'responded_at']));

    $offer = Offer::create(array_merge([
        'listing_id'  => $listing->id,
        'sender_id'   => $buyer->id,
        'receiver_id' => $seller->id,
        'amount'      => 500,
        'status'      => $status,
    ], array_diff_key($overrides, $timestamps)));

    if ($timestamps !== []) {
        $offer->forceFill($timestamps)->save();
    }

    return $offer;
}

// ═══════════════════════════════════════════════════════════════════════════
// responseRate()
// ═══════════════════════════════════════════════════════════════════════════

describe('Seller responseRate()', function () {

    beforeEach(function () {
        $this->seller   = User::factory()->create();
        $this->buyer    = User::factory()->create();
        $this->category = rrCategory();
        $this->listing  = rrListing($this->seller, $this->category);
        $this->service  = app(SellerListingAnalyticsService::class);
    });

    it('returns null below the minimum offers threshold', function () {
        // Only 4 non-canceled offers (< 5) — not statistically meaningful.
        rrOffer($this->seller, $this->buyer, $this->listing, 'accepted');
        rrOffer($this->seller, $this->buyer, $this->listing, 'rejected');
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');

        expect($this->service->responseRate($this->seller))->toBeNull();
    });

    it('computes the rate correctly at/above the threshold', function () {
        // 5 non-canceled offers, 3 decided (accepted/rejected) => 60.0%
        rrOffer($this->seller, $this->buyer, $this->listing, 'accepted');
        rrOffer($this->seller, $this->buyer, $this->listing, 'accepted');
        rrOffer($this->seller, $this->buyer, $this->listing, 'rejected');
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');

        expect($this->service->responseRate($this->seller))->toBe(60.0);
    });

    it('excludes canceled offers from both numerator and denominator', function () {
        // 5 non-canceled (3 decided => 60%) + 3 canceled which must be ignored entirely.
        rrOffer($this->seller, $this->buyer, $this->listing, 'accepted');
        rrOffer($this->seller, $this->buyer, $this->listing, 'accepted');
        rrOffer($this->seller, $this->buyer, $this->listing, 'rejected');
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');
        rrOffer($this->seller, $this->buyer, $this->listing, 'canceled');
        rrOffer($this->seller, $this->buyer, $this->listing, 'canceled');
        rrOffer($this->seller, $this->buyer, $this->listing, 'canceled');

        // If canceled leaked in, the rate would change — assert it stays 60.0.
        expect($this->service->responseRate($this->seller))->toBe(60.0);
    });

    it('canceled offers do not count toward reaching the minimum threshold', function () {
        // 4 non-canceled + 4 canceled = still below minimum on non-canceled count.
        rrOffer($this->seller, $this->buyer, $this->listing, 'accepted');
        rrOffer($this->seller, $this->buyer, $this->listing, 'rejected');
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');
        rrOffer($this->seller, $this->buyer, $this->listing, 'canceled');
        rrOffer($this->seller, $this->buyer, $this->listing, 'canceled');
        rrOffer($this->seller, $this->buyer, $this->listing, 'canceled');
        rrOffer($this->seller, $this->buyer, $this->listing, 'canceled');

        expect($this->service->responseRate($this->seller))->toBeNull();
    });

    it('returns 100.0 when all received offers were decided', function () {
        for ($i = 0; $i < 5; $i++) {
            rrOffer($this->seller, $this->buyer, $this->listing, 'accepted');
        }

        expect($this->service->responseRate($this->seller))->toBe(100.0);
    });

    it('only counts offers received by the given seller', function () {
        $otherSeller  = User::factory()->create();
        $otherListing = rrListing($otherSeller, $this->category);

        // 5 offers for the target seller (3 decided => 60%).
        rrOffer($this->seller, $this->buyer, $this->listing, 'accepted');
        rrOffer($this->seller, $this->buyer, $this->listing, 'accepted');
        rrOffer($this->seller, $this->buyer, $this->listing, 'rejected');
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');

        // Noise for another seller — must not affect the target.
        rrOffer($otherSeller, $this->buyer, $otherListing, 'pending');
        rrOffer($otherSeller, $this->buyer, $otherListing, 'pending');

        expect($this->service->responseRate($this->seller))->toBe(60.0);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// averageResponseTime()
// ═══════════════════════════════════════════════════════════════════════════

describe('Seller averageResponseTime()', function () {

    beforeEach(function () {
        $this->seller   = User::factory()->create();
        $this->buyer    = User::factory()->create();
        $this->category = rrCategory();
        $this->listing  = rrListing($this->seller, $this->category);
        $this->service  = app(SellerListingAnalyticsService::class);
    });

    it('returns null below the minimum offers threshold', function () {
        rrOffer($this->seller, $this->buyer, $this->listing, 'accepted', [
            'created_at'   => now()->subHours(2),
            'responded_at' => now(),
        ]);

        expect($this->service->averageResponseTime($this->seller))->toBeNull();
    });

    it('averages responded_at minus created_at in seconds', function () {
        // 5 non-canceled offers to clear the threshold; 2 of them responded.
        rrOffer($this->seller, $this->buyer, $this->listing, 'accepted', [
            'created_at'   => now()->subSeconds(100),
            'responded_at' => now(),
        ]);
        rrOffer($this->seller, $this->buyer, $this->listing, 'rejected', [
            'created_at'   => now()->subSeconds(300),
            'responded_at' => now(),
        ]);
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');
        rrOffer($this->seller, $this->buyer, $this->listing, 'pending');

        // (100 + 300) / 2 = 200 seconds
        expect($this->service->averageResponseTime($this->seller))->toBe(200.0);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// responded_at write semantics (UserDashboard accept/reject)
// ═══════════════════════════════════════════════════════════════════════════

describe('responded_at write semantics', function () {

    beforeEach(function () {
        $this->seller   = User::factory()->create(['is_phone_verified' => true]);
        $this->buyer    = User::factory()->create();
        $this->category = rrCategory();
        $this->listing  = rrListing($this->seller, $this->category);
    });

    it('sets responded_at when accepting a pending offer', function () {
        $offer = rrOffer($this->seller, $this->buyer, $this->listing, 'pending');
        expect($offer->responded_at)->toBeNull();

        Livewire::actingAs($this->seller)
            ->test(UserDashboard::class)
            ->call('acceptOffer', $offer->id);

        expect($offer->fresh()->responded_at)->not->toBeNull();
    });

    it('does not overwrite responded_at on a repeated decision', function () {
        $original = now()->subDays(3);
        $offer = rrOffer($this->seller, $this->buyer, $this->listing, 'accepted', [
            'responded_at' => $original,
        ]);

        Livewire::actingAs($this->seller)
            ->test(UserDashboard::class)
            ->call('rejectOffer', $offer->id);

        // Status changes, but the first-response timestamp is preserved.
        $fresh = $offer->fresh();
        expect($fresh->status)->toBe('rejected');
        expect($fresh->responded_at->timestamp)->toBe($original->timestamp);
    });
});
