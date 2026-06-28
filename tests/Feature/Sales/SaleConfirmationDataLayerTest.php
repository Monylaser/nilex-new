<?php

/**
 * Nilex Platform — Sale Confirmation & Reviews (data layer).
 *
 * Covers the DB/model layer only (the buyer-selection Modal Step 2 and the
 * rating form UI are a later phase):
 *   - creating a sale_confirmation
 *   - unique(listing_id, buyer_id) constraint
 *   - state machine: pending → confirmed, pending → canceled,
 *     and NO cancel after confirmed (locked permanently)
 *   - "one buyer per listing" guard (a confirmed sale blocks new initiations)
 *   - ReviewObserver keeps users.ratings_avg / ratings_count in sync
 *     on created / updated / deleted
 *   - account-deletion guard rejects any user who is party to a
 *     sale_confirmation or review of ANY status (incl. pending/canceled)
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\Review;
use App\Models\SaleConfirmation;
use App\Models\User;
use Illuminate\Database\QueryException;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ═══════════════════════════════════════════════════════════════════════════
// Helpers
// ═══════════════════════════════════════════════════════════════════════════

function scCategory(): Category
{
    return Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-sc-' . uniqid(),
        'is_active' => true,
    ]);
}

function scListing(User $seller, Category $category): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create([
        'title'           => 'SC listing ' . $counter,
        'slug'            => 'sc-listing-' . $counter . '-' . uniqid(),
        'description'     => 'desc',
        'price'           => 1000,
        'category_id'     => $category->id,
        'user_id'         => $seller->id,
        'status'          => Listing::STATUS_PUBLISHED,
        'views_count'     => 0,
        'whatsapp_clicks' => 0,
    ]);
}

function scPending(User $seller, User $buyer, Listing $listing): SaleConfirmation
{
    return SaleConfirmation::create([
        'listing_id'          => $listing->id,
        'seller_id'           => $seller->id,
        'buyer_id'            => $buyer->id,
        'status'              => SaleConfirmation::STATUS_PENDING,
        'seller_confirmed_at' => now(),
    ]);
}

function scReview(SaleConfirmation $sc, int $rating): Review
{
    return Review::create([
        'sale_confirmation_id' => $sc->id,
        'reviewer_id'          => $sc->buyer_id,
        'reviewee_id'          => $sc->seller_id,
        'listing_id'           => $sc->listing_id,
        'rating'               => $rating,
    ]);
}

function scConfirmedFor(User $seller, Category $category): SaleConfirmation
{
    $buyer   = User::factory()->create();
    $listing = scListing($seller, $category);
    $sc = scPending($seller, $buyer, $listing);
    $sc->confirmByBuyer();

    return $sc->fresh();
}

// ═══════════════════════════════════════════════════════════════════════════
// Creation + unique constraint
// ═══════════════════════════════════════════════════════════════════════════

describe('sale_confirmation creation & uniqueness', function () {

    beforeEach(function () {
        $this->seller   = User::factory()->create();
        $this->buyer    = User::factory()->create();
        $this->category = scCategory();
        $this->listing  = scListing($this->seller, $this->category);
    });

    it('creates a pending confirmation with the seller already confirmed', function () {
        $sc = scPending($this->seller, $this->buyer, $this->listing);

        expect($sc->status)->toBe('pending')
            ->and($sc->seller_confirmed_at)->not->toBeNull()
            ->and($sc->buyer_confirmed_at)->toBeNull()
            ->and($sc->isFullyConfirmed())->toBeFalse();
    });

    it('enforces unique(listing_id, buyer_id)', function () {
        scPending($this->seller, $this->buyer, $this->listing);

        expect(fn () => scPending($this->seller, $this->buyer, $this->listing))
            ->toThrow(QueryException::class);
    });

    it('allows the same buyer on a different listing', function () {
        $other = scListing($this->seller, $this->category);

        scPending($this->seller, $this->buyer, $this->listing);
        $sc = scPending($this->seller, $this->buyer, $other);

        expect($sc->exists)->toBeTrue();
    });

    it('keeps the listing relation working after the listing is soft-deleted', function () {
        $sc = scPending($this->seller, $this->buyer, $this->listing);
        $this->listing->delete(); // soft delete

        expect($sc->fresh()->listing)->not->toBeNull()
            ->and($sc->fresh()->listing->trashed())->toBeTrue();
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// State machine
// ═══════════════════════════════════════════════════════════════════════════

describe('sale_confirmation state machine', function () {

    beforeEach(function () {
        $this->seller   = User::factory()->create();
        $this->buyer    = User::factory()->create();
        $this->category = scCategory();
        $this->listing  = scListing($this->seller, $this->category);
    });

    it('transitions pending → confirmed on buyer confirmation', function () {
        $sc = scPending($this->seller, $this->buyer, $this->listing);

        expect($sc->confirmByBuyer())->toBeTrue();

        $fresh = $sc->fresh();
        expect($fresh->status)->toBe('confirmed')
            ->and($fresh->buyer_confirmed_at)->not->toBeNull()
            ->and($fresh->isFullyConfirmed())->toBeTrue();
    });

    it('transitions pending → canceled on seller cancel', function () {
        $sc = scPending($this->seller, $this->buyer, $this->listing);

        expect($sc->cancelBySeller($this->seller->id))->toBeTrue();

        $fresh = $sc->fresh();
        expect($fresh->status)->toBe('canceled')
            ->and($fresh->canceled_at)->not->toBeNull()
            ->and($fresh->canceled_by)->toBe($this->seller->id);
    });

    it('does NOT allow canceling after confirmed (locked permanently)', function () {
        $sc = scPending($this->seller, $this->buyer, $this->listing);
        $sc->confirmByBuyer();

        expect($sc->cancelBySeller($this->seller->id))->toBeFalse();
        expect($sc->fresh()->status)->toBe('confirmed');
    });

    it('does NOT allow confirming a canceled sale', function () {
        $sc = scPending($this->seller, $this->buyer, $this->listing);
        $sc->cancelBySeller($this->seller->id);

        expect($sc->confirmByBuyer())->toBeFalse();
        expect($sc->fresh()->status)->toBe('canceled');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// "One buyer per listing" guard
// ═══════════════════════════════════════════════════════════════════════════

describe('one-buyer-per-listing guard', function () {

    beforeEach(function () {
        $this->seller   = User::factory()->create();
        $this->buyer    = User::factory()->create();
        $this->category = scCategory();
        $this->listing  = scListing($this->seller, $this->category);
    });

    it('allows initiation when no confirmed sale exists', function () {
        expect(SaleConfirmation::canInitiateForListing($this->listing->id))->toBeTrue();
    });

    it('a pending sale does NOT block new initiations', function () {
        scPending($this->seller, $this->buyer, $this->listing);

        expect(SaleConfirmation::canInitiateForListing($this->listing->id))->toBeTrue();
    });

    it('a confirmed sale blocks new initiations for the same listing', function () {
        $sc = scPending($this->seller, $this->buyer, $this->listing);
        $sc->confirmByBuyer();

        expect(SaleConfirmation::canInitiateForListing($this->listing->id))->toBeFalse();
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// ReviewObserver → users.ratings_avg / ratings_count
// ═══════════════════════════════════════════════════════════════════════════

describe('ReviewObserver denormalization', function () {

    beforeEach(function () {
        $this->seller   = User::factory()->create();
        $this->category = scCategory();
    });

    it('starts with null avg and zero count', function () {
        expect($this->seller->ratings_avg)->toBeNull()
            ->and((int) $this->seller->ratings_count)->toBe(0);
    });

    it('updates avg/count on review created', function () {
        $sc = scConfirmedFor($this->seller, $this->category);
        scReview($sc, 4);

        $fresh = $this->seller->fresh();
        expect((float) $fresh->ratings_avg)->toBe(4.0)
            ->and((int) $fresh->ratings_count)->toBe(1);
    });

    it('averages multiple reviews correctly', function () {
        scReview(scConfirmedFor($this->seller, $this->category), 4);
        scReview(scConfirmedFor($this->seller, $this->category), 2);

        $fresh = $this->seller->fresh();
        expect((float) $fresh->ratings_avg)->toBe(3.0)
            ->and((int) $fresh->ratings_count)->toBe(2);
    });

    it('recomputes on review updated', function () {
        $review = scReview(scConfirmedFor($this->seller, $this->category), 5);
        scReview(scConfirmedFor($this->seller, $this->category), 1);

        // avg = 3.0 now
        expect((float) $this->seller->fresh()->ratings_avg)->toBe(3.0);

        $review->update(['rating' => 3]); // (3 + 1) / 2 = 2.0
        expect((float) $this->seller->fresh()->ratings_avg)->toBe(2.0);
    });

    it('recomputes on review deleted', function () {
        $r1 = scReview(scConfirmedFor($this->seller, $this->category), 4);
        scReview(scConfirmedFor($this->seller, $this->category), 2);

        expect((int) $this->seller->fresh()->ratings_count)->toBe(2);

        $r1->delete();

        $fresh = $this->seller->fresh();
        expect((int) $fresh->ratings_count)->toBe(1)
            ->and((float) $fresh->ratings_avg)->toBe(2.0);
    });

    it('resets avg to null when the last review is removed', function () {
        $r = scReview(scConfirmedFor($this->seller, $this->category), 5);
        $r->delete();

        $fresh = $this->seller->fresh();
        expect((int) $fresh->ratings_count)->toBe(0)
            ->and($fresh->ratings_avg)->toBeNull();
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// Account-deletion = anonymization (the old hasSalesOrReviews guard was removed)
// ═══════════════════════════════════════════════════════════════════════════

describe('account-deletion anonymization', function () {

    beforeEach(function () {
        $this->seller   = User::factory()->create();
        $this->buyer    = User::factory()->create();
        $this->category = scCategory();
        $this->listing  = scListing($this->seller, $this->category);
    });

    it('anonymizes a user with no sales/reviews instead of hard-deleting', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $fresh = User::find($user->id);
        expect($fresh)->not->toBeNull()
            ->and($fresh->anonymized_at)->not->toBeNull();
    });

    it('allows a seller in a pending sale to delete (anonymize) — old block removed', function () {
        scPending($this->seller, $this->buyer, $this->listing);

        $this->actingAs($this->seller)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        // الصف يبقى (لحماية الـ FK على sale_confirmations) لكنه مُجمّد.
        $fresh = User::find($this->seller->id);
        expect($fresh)->not->toBeNull()
            ->and($fresh->isAnonymized())->toBeTrue()
            ->and($fresh->name)->toBe(__('server.account.deleted_name'));

        // الـ sale_confirmation لا يزال موجوداً ويشير لنفس البائع.
        expect(SaleConfirmation::where('seller_id', $this->seller->id)->exists())->toBeTrue();
    });

    it('allows a buyer in a canceled sale to delete (anonymize)', function () {
        $sc = scPending($this->seller, $this->buyer, $this->listing);
        $sc->cancelBySeller($this->seller->id);

        $this->actingAs($this->buyer)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $fresh = User::find($this->buyer->id);
        expect($fresh)->not->toBeNull()
            ->and($fresh->isAnonymized())->toBeTrue();
    });

    it('allows a seller who has received a review to delete (anonymize)', function () {
        $sc = scPending($this->seller, $this->buyer, $this->listing);
        $sc->confirmByBuyer();
        scReview($sc->fresh(), 5);

        $this->actingAs($this->seller)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $fresh = User::find($this->seller->id);
        expect($fresh)->not->toBeNull()
            ->and($fresh->isAnonymized())->toBeTrue();

        // التقييم القديم لا يزال يشير للبائع المُجمّد (لم يُكسر).
        expect(Review::where('reviewee_id', $this->seller->id)->exists())->toBeTrue();
    });
});
