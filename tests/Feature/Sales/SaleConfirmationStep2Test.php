<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Listing-Closing Modal — Step 2 (buyer selection for "sold_platform").
 *
 * Builds on the Step 1 closing-type modal and the SaleConfirmation data layer.
 * When a seller picks "sold_platform" and confirms, the modal switches to Step 2
 * (same modal, $closingStep = 2) showing the buyers who contacted this listing
 * (phone reveal / offer). Selecting one + confirming:
 *   - re-verifies listing ownership,
 *   - validates the buyer is a real SellerLead for THIS listing (IDOR guard),
 *   - rejects the seller picking themselves,
 *   - enforces SaleConfirmation::canInitiateForListing (one confirmed sale),
 *   - creates the pending SaleConfirmation AND soft-deletes the listing
 *     atomically (single transaction),
 *   - shows a clear empty state when no buyer ever contacted the listing.
 */

use App\Livewire\Frontend\UserDashboard;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\SaleConfirmation;
use App\Models\SellerLead;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create(['is_phone_verified' => true]);

    $this->category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-step2-' . uniqid(),
        'is_active' => true,
    ]);
});

function step2Listing(User $user, Category $category, array $overrides = []): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create(array_merge([
        'title'       => 'إعلان الخطوة 2 ' . $counter,
        'slug'        => 'step2-listing-' . $counter . '-' . uniqid(),
        'description' => 'وصف تجريبي.',
        'price'       => 10_000,
        'category_id' => $category->id,
        'user_id'     => $user->id,
        'status'      => Listing::STATUS_PUBLISHED,
    ], $overrides));
}

function step2Lead(User $seller, User $buyer, Listing $listing, string $sourceType = SellerLead::SOURCE_PHONE_REVEAL): SellerLead
{
    static $sourceCounter = 0;
    $sourceCounter++;

    return SellerLead::create([
        'seller_id'   => $seller->id,
        'listing_id'  => $listing->id,
        'buyer_id'    => $buyer->id,
        'source_type' => $sourceType,
        'source_id'   => $sourceCounter,
        'status'      => SellerLead::STATUS_NEW,
    ]);
}

// ═══════════════════════════════════════════════════════════════════════════
// 1) sold_platform transitions to Step 2 (no delete yet)
// ═══════════════════════════════════════════════════════════════════════════

it('moves to step 2 when confirming sold_platform, without deleting the listing', function () {
    $listing = step2Listing($this->seller, $this->category);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->assertSet('closingStep', 1)
        ->set('closingType', 'sold_platform')
        ->call('confirmClosing')
        ->assertSet('closingStep', 2)
        ->assertSet('closingModalOpen', true)
        ->assertSet('selectedBuyerId', null);

    // Step 2 must NOT delete — the listing only closes after a buyer is confirmed.
    $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
});

// ═══════════════════════════════════════════════════════════════════════════
// 2) buyerLeads() returns only eligible, listing-scoped, de-duplicated buyers
// ═══════════════════════════════════════════════════════════════════════════

it('lists only phone_reveal/offer buyers for the exact listing, de-duplicated per buyer', function () {
    $listing      = step2Listing($this->seller, $this->category);
    $otherListing = step2Listing($this->seller, $this->category);

    $buyerPhone = User::factory()->create();
    $buyerOffer = User::factory()->create();
    $buyerWa    = User::factory()->create();
    $buyerOther = User::factory()->create();

    // Eligible leads on the target listing
    step2Lead($this->seller, $buyerPhone, $listing, SellerLead::SOURCE_PHONE_REVEAL);
    step2Lead($this->seller, $buyerOffer, $listing, SellerLead::SOURCE_OFFER);
    // Same buyer contacted twice (phone + offer) → must appear once
    step2Lead($this->seller, $buyerPhone, $listing, SellerLead::SOURCE_OFFER);
    // WhatsApp click is NOT an eligible buyer source → excluded
    step2Lead($this->seller, $buyerWa, $listing, SellerLead::SOURCE_WHATSAPP_CLICK);
    // A buyer that contacted a DIFFERENT listing → excluded
    step2Lead($this->seller, $buyerOther, $otherListing, SellerLead::SOURCE_PHONE_REVEAL);

    $component = Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id);

    $buyerIds = $component->instance()->buyerLeads()->pluck('buyer_id')->sort()->values()->all();

    expect($buyerIds)->toEqual(collect([$buyerPhone->id, $buyerOffer->id])->sort()->values()->all());
});

// ═══════════════════════════════════════════════════════════════════════════
// 3) Empty buyer list when nobody contacted the listing
// ═══════════════════════════════════════════════════════════════════════════

it('returns an empty buyer list when no eligible buyer contacted the listing', function () {
    $listing = step2Listing($this->seller, $this->category);

    // Only a whatsapp_click lead exists — not an eligible buyer source.
    step2Lead($this->seller, User::factory()->create(), $listing, SellerLead::SOURCE_WHATSAPP_CLICK);

    $component = Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->set('closingType', 'sold_platform')
        ->call('confirmClosing')
        ->assertSet('closingStep', 2);

    expect($component->instance()->buyerLeads())->toBeEmpty();
    $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
});

// ═══════════════════════════════════════════════════════════════════════════
// 4) confirmSaleToBuyer creates the SaleConfirmation AND soft-deletes (atomic)
// ═══════════════════════════════════════════════════════════════════════════

it('creates a pending sale_confirmation and soft-deletes the listing on confirm', function () {
    $listing = step2Listing($this->seller, $this->category);
    $buyer   = User::factory()->create();
    step2Lead($this->seller, $buyer, $listing, SellerLead::SOURCE_OFFER);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->set('closingType', 'sold_platform')
        ->call('confirmClosing')
        ->call('selectBuyer', $buyer->id)
        ->assertSet('selectedBuyerId', $buyer->id)
        ->call('confirmSaleToBuyer')
        ->assertSet('closingModalOpen', false)
        ->assertSet('closingListingId', null)
        ->assertSet('closingStep', 1);

    $this->assertSoftDeleted('listings', ['id' => $listing->id]);

    $sale = SaleConfirmation::where('listing_id', $listing->id)->first();
    expect($sale)->not->toBeNull();
    expect($sale->buyer_id)->toBe($buyer->id);
    expect($sale->seller_id)->toBe($this->seller->id);
    expect($sale->status)->toBe(SaleConfirmation::STATUS_PENDING);
    expect($sale->seller_confirmed_at)->not->toBeNull();
    expect($sale->buyer_confirmed_at)->toBeNull();
});

// ═══════════════════════════════════════════════════════════════════════════
// 5) IDOR — buyer must be a real lead for THIS listing
// ═══════════════════════════════════════════════════════════════════════════

it('rejects confirming a buyer who never contacted this listing (IDOR)', function () {
    $listing  = step2Listing($this->seller, $this->category);
    $stranger = User::factory()->create(); // no lead at all

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->set('closingType', 'sold_platform')
        ->call('confirmClosing')
        ->set('selectedBuyerId', $stranger->id)
        ->call('confirmSaleToBuyer');

    expect(SaleConfirmation::where('listing_id', $listing->id)->exists())->toBeFalse();
    $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
});

it('rejects confirming a buyer who is a lead on a different listing only (IDOR)', function () {
    $listing      = step2Listing($this->seller, $this->category);
    $otherListing = step2Listing($this->seller, $this->category);
    $buyer        = User::factory()->create();

    // Buyer is a lead on otherListing, NOT on $listing.
    step2Lead($this->seller, $buyer, $otherListing, SellerLead::SOURCE_PHONE_REVEAL);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->set('closingType', 'sold_platform')
        ->call('confirmClosing')
        ->set('selectedBuyerId', $buyer->id)
        ->call('confirmSaleToBuyer');

    expect(SaleConfirmation::where('listing_id', $listing->id)->exists())->toBeFalse();
    $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
});

// ═══════════════════════════════════════════════════════════════════════════
// 6) canInitiateForListing — a confirmed sale blocks a new initiation
// ═══════════════════════════════════════════════════════════════════════════

it('blocks initiating a second sale when one is already confirmed for the listing', function () {
    $listing  = step2Listing($this->seller, $this->category);
    $buyerOne = User::factory()->create();
    $buyerTwo = User::factory()->create();
    step2Lead($this->seller, $buyerTwo, $listing, SellerLead::SOURCE_OFFER);

    // A pre-existing CONFIRMED sale for this listing (different buyer).
    SaleConfirmation::create([
        'listing_id'          => $listing->id,
        'seller_id'           => $this->seller->id,
        'buyer_id'            => $buyerOne->id,
        'status'              => SaleConfirmation::STATUS_CONFIRMED,
        'seller_confirmed_at' => now(),
        'buyer_confirmed_at'  => now(),
    ]);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->set('closingType', 'sold_platform')
        ->call('confirmClosing')
        ->set('selectedBuyerId', $buyerTwo->id)
        ->call('confirmSaleToBuyer')
        ->assertSet('closingModalOpen', false);

    // No new sale for buyerTwo, and the listing is NOT deleted.
    expect(SaleConfirmation::where('listing_id', $listing->id)->where('buyer_id', $buyerTwo->id)->exists())->toBeFalse();
    $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
});

// ═══════════════════════════════════════════════════════════════════════════
// 7) Seller cannot pick themselves as the buyer
// ═══════════════════════════════════════════════════════════════════════════

it('rejects the seller selecting themselves as the buyer', function () {
    $listing = step2Listing($this->seller, $this->category);
    // (Even if a self-lead somehow existed, the seller===buyer guard rejects first.)
    step2Lead($this->seller, $this->seller, $listing, SellerLead::SOURCE_PHONE_REVEAL);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->set('closingType', 'sold_platform')
        ->call('confirmClosing')
        ->set('selectedBuyerId', $this->seller->id)
        ->call('confirmSaleToBuyer');

    expect(SaleConfirmation::where('listing_id', $listing->id)->exists())->toBeFalse();
    $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
});

// ═══════════════════════════════════════════════════════════════════════════
// 8) Ownership re-check inside confirmSaleToBuyer
// ═══════════════════════════════════════════════════════════════════════════

it('rejects confirming a sale on a listing owned by someone else (IDOR)', function () {
    $listing  = step2Listing($this->seller, $this->category);
    $buyer    = User::factory()->create();
    step2Lead($this->seller, $buyer, $listing, SellerLead::SOURCE_OFFER);

    $intruder = User::factory()->create(['is_phone_verified' => true]);

    expect(fn () => Livewire::actingAs($intruder)
        ->test(UserDashboard::class)
        ->set('closingListingId', $listing->id)
        ->set('closingStep', 2)
        ->set('selectedBuyerId', $buyer->id)
        ->call('confirmSaleToBuyer'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(SaleConfirmation::where('listing_id', $listing->id)->exists())->toBeFalse();
    $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
});

// ═══════════════════════════════════════════════════════════════════════════
// 9) selectBuyer toggles off on a repeat click
// ═══════════════════════════════════════════════════════════════════════════

it('toggles the selected buyer off when picked twice', function () {
    $listing = step2Listing($this->seller, $this->category);
    $buyer   = User::factory()->create();
    step2Lead($this->seller, $buyer, $listing, SellerLead::SOURCE_OFFER);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->call('selectBuyer', $buyer->id)
        ->assertSet('selectedBuyerId', $buyer->id)
        ->call('selectBuyer', $buyer->id)
        ->assertSet('selectedBuyerId', null);
});

// ═══════════════════════════════════════════════════════════════════════════
// 10) backToStep1 returns to type selection and clears the buyer choice
// ═══════════════════════════════════════════════════════════════════════════

it('returns to step 1 and clears the buyer selection on back', function () {
    $listing = step2Listing($this->seller, $this->category);
    $buyer   = User::factory()->create();
    step2Lead($this->seller, $buyer, $listing, SellerLead::SOURCE_OFFER);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->set('closingType', 'sold_platform')
        ->call('confirmClosing')
        ->call('selectBuyer', $buyer->id)
        ->call('backToStep1')
        ->assertSet('closingStep', 1)
        ->assertSet('selectedBuyerId', null);
});

// ═══════════════════════════════════════════════════════════════════════════
// 11) REGRESSION — a real pending Offer on a sold_platform-closed listing must
//     NOT crash the dashboard re-render (Offer::listing() withTrashed).
//
//     Real offers create BOTH an Offer (pending) AND a SellerLead (via
//     OfferLeadObserver). After sold_platform soft-deletes the listing, the
//     still-pending offer is re-fetched on render with ->with('listing'); a
//     missing withTrashed() makes $offer->listing null → "Attempt to read
//     property 'title' on null" 500. This is the gap the unit-style tests
//     (which only create SellerLead rows, no real Offer) didn't cover.
// ═══════════════════════════════════════════════════════════════════════════

it('does not crash the dashboard when a pending offer points to a sold_platform-closed listing', function () {
    $listing = step2Listing($this->seller, $this->category);
    $buyer   = User::factory()->create();

    // A real offer: pending, received by the seller. OfferLeadObserver
    // auto-creates the matching SOURCE_OFFER SellerLead → buyer is eligible.
    $offer = Offer::create([
        'listing_id'  => $listing->id,
        'sender_id'   => $buyer->id,
        'receiver_id' => $this->seller->id,
        'amount'      => 5000,
        'message'     => 'عرض تجريبي',
        'status'      => 'pending',
    ]);

    // The observer created the eligible lead from the offer.
    expect(SellerLead::where('listing_id', $listing->id)->where('buyer_id', $buyer->id)->where('source_type', SellerLead::SOURCE_OFFER)->exists())->toBeTrue();

    // Close via sold_platform, select the offering buyer, confirm. The confirm
    // round-trip re-renders the dashboard while the (still-pending) offer now
    // points to a soft-deleted listing — this would 500 without withTrashed.
    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->set('closingType', 'sold_platform')
        ->call('confirmClosing')
        ->call('selectBuyer', $buyer->id)
        ->call('confirmSaleToBuyer')
        ->assertOk()
        ->assertSet('closingModalOpen', false);

    // Sale recorded + listing soft-deleted.
    $this->assertSoftDeleted('listings', ['id' => $listing->id]);
    expect(SaleConfirmation::where('listing_id', $listing->id)->where('buyer_id', $buyer->id)->exists())->toBeTrue();

    // The offer is untouched (still pending) but its listing now resolves via
    // withTrashed instead of returning null.
    $offer->refresh();
    expect($offer->status)->toBe('pending');
    expect($offer->listing)->not->toBeNull();
    expect($offer->listing->trashed())->toBeTrue();

    // A fresh dashboard mount (the original 500 surface) renders cleanly.
    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->assertOk()
        ->assertSee($listing->title);
});
