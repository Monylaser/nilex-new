<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Buyer Confirmation + Reviews (the buyer side of the sale-confirmation flow).
 *
 * After a seller records a sale (SaleConfirmation pending, Step 2), the buyer:
 *   - receives a SaleConfirmationRequested notification (database + mail),
 *   - opens /dashboard/purchases (BuyerPurchases Livewire component),
 *   - confirms the purchase (confirmByBuyer → status confirmed),
 *   - rates the seller inline (Review created → ReviewObserver updates aggregates).
 *
 * Covers: notification dispatch, buyer confirmation, IDOR guards, review creation
 * + rating denormalization, and the review state guards (not-confirmed / already
 * reviewed / rating required).
 */

use App\Livewire\Frontend\BuyerPurchases;
use App\Livewire\Frontend\UserDashboard;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Review;
use App\Models\SaleConfirmation;
use App\Models\SellerLead;
use App\Models\User;
use App\Notifications\SaleConfirmationRequested;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create(['is_phone_verified' => true]);
    $this->buyer  = User::factory()->create(['is_phone_verified' => true]);

    $this->category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-buyer-' . uniqid(),
        'is_active' => true,
    ]);
});

function buyerConfListing(User $user, Category $category, array $overrides = []): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create(array_merge([
        'title'       => 'إعلان تأكيد المشتري ' . $counter,
        'slug'        => 'buyerconf-listing-' . $counter . '-' . uniqid(),
        'description' => 'وصف تجريبي.',
        'price'       => 10_000,
        'category_id' => $category->id,
        'user_id'     => $user->id,
        'status'      => Listing::STATUS_PUBLISHED,
    ], $overrides));
}

function pendingSale(User $seller, User $buyer, Listing $listing): SaleConfirmation
{
    return SaleConfirmation::create([
        'listing_id'          => $listing->id,
        'seller_id'           => $seller->id,
        'buyer_id'            => $buyer->id,
        'status'              => SaleConfirmation::STATUS_PENDING,
        'seller_confirmed_at' => now(),
    ]);
}

// ═══════════════════════════════════════════════════════════════════════════
// 1) Notification is dispatched to the buyer on confirmSaleToBuyer
// ═══════════════════════════════════════════════════════════════════════════

it('notifies the buyer when the seller confirms a sale to them', function () {
    Notification::fake();

    $listing = buyerConfListing($this->seller, $this->category);

    SellerLead::create([
        'seller_id'   => $this->seller->id,
        'listing_id'  => $listing->id,
        'buyer_id'    => $this->buyer->id,
        'source_type' => SellerLead::SOURCE_OFFER,
        'source_id'   => 1,
        'status'      => SellerLead::STATUS_NEW,
    ]);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->set('closingType', 'sold_platform')
        ->call('confirmClosing')
        ->call('selectBuyer', $this->buyer->id)
        ->call('confirmSaleToBuyer');

    Notification::assertSentTo(
        $this->buyer,
        SaleConfirmationRequested::class,
        function ($notification) use ($listing) {
            $data = $notification->toArray($this->buyer);

            return $data['listing_id'] === $listing->id
                && str_contains($data['url'], '/dashboard/purchases')
                && ! empty($data['message']);
        }
    );

    // Seller is never notified for the buyer-confirmation request.
    Notification::assertNotSentTo($this->seller, SaleConfirmationRequested::class);
});

// ═══════════════════════════════════════════════════════════════════════════
// 2) The buyer can confirm their pending purchase
// ═══════════════════════════════════════════════════════════════════════════

it('lets the buyer confirm a pending purchase', function () {
    $listing = buyerConfListing($this->seller, $this->category);
    $sale    = pendingSale($this->seller, $this->buyer, $listing);

    Livewire::actingAs($this->buyer)
        ->test(BuyerPurchases::class)
        ->call('confirmPurchase', $sale->id);

    $sale->refresh();
    expect($sale->status)->toBe(SaleConfirmation::STATUS_CONFIRMED);
    expect($sale->buyer_confirmed_at)->not->toBeNull();
    expect($sale->isFullyConfirmed())->toBeTrue();
});

// ═══════════════════════════════════════════════════════════════════════════
// 3) IDOR — a user cannot confirm someone else's purchase
// ═══════════════════════════════════════════════════════════════════════════

it('rejects confirming a purchase that belongs to another buyer (IDOR)', function () {
    $listing  = buyerConfListing($this->seller, $this->category);
    $sale     = pendingSale($this->seller, $this->buyer, $listing);
    $intruder = User::factory()->create(['is_phone_verified' => true]);

    expect(fn () => Livewire::actingAs($intruder)
        ->test(BuyerPurchases::class)
        ->call('confirmPurchase', $sale->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    $sale->refresh();
    expect($sale->status)->toBe(SaleConfirmation::STATUS_PENDING);
});

// ═══════════════════════════════════════════════════════════════════════════
// 4) The buyer can rate the seller after confirmation (+ aggregates update)
// ═══════════════════════════════════════════════════════════════════════════

it('creates a review after confirmation and updates seller rating aggregates', function () {
    $listing = buyerConfListing($this->seller, $this->category);
    $sale    = pendingSale($this->seller, $this->buyer, $listing);
    $sale->confirmByBuyer();

    Livewire::actingAs($this->buyer)
        ->test(BuyerPurchases::class)
        ->call('setRating', $sale->id, 5)
        ->set("ratingComments.{$sale->id}", 'بائع ممتاز')
        ->call('submitReview', $sale->id);

    $review = Review::where('sale_confirmation_id', $sale->id)->first();
    expect($review)->not->toBeNull();
    expect($review->rating)->toBe(5);
    expect($review->reviewer_id)->toBe($this->buyer->id);
    expect($review->reviewee_id)->toBe($this->seller->id);
    expect($review->listing_id)->toBe($listing->id);
    expect($review->comment)->toBe('بائع ممتاز');

    // ReviewObserver denormalized the seller aggregates.
    $this->seller->refresh();
    expect((float) $this->seller->ratings_avg)->toBe(5.0);
    expect($this->seller->ratings_count)->toBe(1);
});

// ═══════════════════════════════════════════════════════════════════════════
// 5) Review blocked before the sale is fully confirmed
// ═══════════════════════════════════════════════════════════════════════════

it('does not allow reviewing a sale that is still pending', function () {
    $listing = buyerConfListing($this->seller, $this->category);
    $sale    = pendingSale($this->seller, $this->buyer, $listing);

    Livewire::actingAs($this->buyer)
        ->test(BuyerPurchases::class)
        ->call('setRating', $sale->id, 4)
        ->call('submitReview', $sale->id);

    expect(Review::where('sale_confirmation_id', $sale->id)->exists())->toBeFalse();
});

// ═══════════════════════════════════════════════════════════════════════════
// 6) Review blocked when one already exists (one review per sale)
// ═══════════════════════════════════════════════════════════════════════════

it('does not allow a second review for the same sale', function () {
    $listing = buyerConfListing($this->seller, $this->category);
    $sale    = pendingSale($this->seller, $this->buyer, $listing);
    $sale->confirmByBuyer();

    Review::create([
        'sale_confirmation_id' => $sale->id,
        'reviewer_id'          => $this->buyer->id,
        'reviewee_id'          => $this->seller->id,
        'listing_id'           => $listing->id,
        'rating'               => 3,
    ]);

    Livewire::actingAs($this->buyer)
        ->test(BuyerPurchases::class)
        ->call('setRating', $sale->id, 5)
        ->call('submitReview', $sale->id);

    // Still exactly one review, and the rating wasn't overwritten.
    expect(Review::where('sale_confirmation_id', $sale->id)->count())->toBe(1);
    expect(Review::where('sale_confirmation_id', $sale->id)->first()->rating)->toBe(3);
});

// ═══════════════════════════════════════════════════════════════════════════
// 7) Rating is required (1–5) — empty/zero is rejected
// ═══════════════════════════════════════════════════════════════════════════

it('requires a rating between 1 and 5 to submit a review', function () {
    $listing = buyerConfListing($this->seller, $this->category);
    $sale    = pendingSale($this->seller, $this->buyer, $listing);
    $sale->confirmByBuyer();

    // No setRating call → no rating value.
    Livewire::actingAs($this->buyer)
        ->test(BuyerPurchases::class)
        ->call('submitReview', $sale->id);

    expect(Review::where('sale_confirmation_id', $sale->id)->exists())->toBeFalse();
});

it('ignores an out-of-range star value', function () {
    $listing = buyerConfListing($this->seller, $this->category);
    $sale    = pendingSale($this->seller, $this->buyer, $listing);

    $component = Livewire::actingAs($this->buyer)
        ->test(BuyerPurchases::class)
        ->call('setRating', $sale->id, 9);

    expect($component->get('ratingValues'))->not->toHaveKey((string) $sale->id);
});

// ═══════════════════════════════════════════════════════════════════════════
// 8) The page lists only the current buyer's purchases
// ═══════════════════════════════════════════════════════════════════════════

it('shows only the authenticated buyer purchases', function () {
    $listingMine   = buyerConfListing($this->seller, $this->category, ['title' => 'My bought item']);
    $listingOther  = buyerConfListing($this->seller, $this->category, ['title' => 'Someone elses item']);
    $otherBuyer    = User::factory()->create();

    pendingSale($this->seller, $this->buyer, $listingMine);
    pendingSale($this->seller, $otherBuyer, $listingOther);

    Livewire::actingAs($this->buyer)
        ->test(BuyerPurchases::class)
        ->assertOk()
        ->assertSee('My bought item')
        ->assertDontSee('Someone elses item');
});
