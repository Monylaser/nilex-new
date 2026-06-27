<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Seller trust card — real rating display (closes the sale-confirmation +
 * reviews feature). The card on the listing detail page now renders the
 * denormalized users.ratings_avg / ratings_count (no extra query):
 *   - ratings_count = 0 → the "no ratings yet" placeholder.
 *   - ratings_count > 0 → real stars + a translated "AVG out of 5 (N reviews)".
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-trust-' . uniqid(),
        'is_active' => true,
    ]);
});

function trustListing(User $seller, Category $category): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create([
        'title'       => 'إعلان بطاقة الثقة ' . $counter,
        'slug'        => 'trust-listing-' . $counter . '-' . uniqid(),
        'description' => 'وصف تجريبي.',
        'price'       => 10_000,
        'category_id' => $category->id,
        'user_id'     => $seller->id,
        'status'      => Listing::STATUS_PUBLISHED,
    ]);
}

// ═══════════════════════════════════════════════════════════════════════════
// 1) No ratings → placeholder
// ═══════════════════════════════════════════════════════════════════════════

it('shows the no-ratings placeholder for a seller with no reviews', function () {
    $seller = User::factory()->create([
        'is_phone_verified' => true,
        'ratings_avg'       => null,
        'ratings_count'     => 0,
    ]);

    $listing = trustListing($seller, $this->category);

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee(__('ui.seller_trust.no_ratings'));
});

// ═══════════════════════════════════════════════════════════════════════════
// 2) Has ratings → real average + count
// ═══════════════════════════════════════════════════════════════════════════

it('shows the real average and count for a seller with reviews', function () {
    $seller = User::factory()->create(['is_phone_verified' => true]);
    $seller->forceFill(['ratings_avg' => 4.5, 'ratings_count' => 12])->save();

    $listing = trustListing($seller, $this->category);

    $expected = __('ui.seller_trust.rating_summary', ['avg' => '4.5', 'count' => '12']);

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee($expected)
        ->assertDontSee(__('ui.seller_trust.no_ratings'));
});

// ═══════════════════════════════════════════════════════════════════════════
// 3) Whole-number average drops the trailing ".0" (Latin digits, no "5.0")
// ═══════════════════════════════════════════════════════════════════════════

it('renders a whole-number average without a trailing decimal', function () {
    $seller = User::factory()->create(['is_phone_verified' => true]);
    $seller->forceFill(['ratings_avg' => 5.0, 'ratings_count' => 3])->save();

    $listing = trustListing($seller, $this->category);

    $expected = __('ui.seller_trust.rating_summary', ['avg' => '5', 'count' => '3']);

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee($expected);
});
