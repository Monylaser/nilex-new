<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Listing-Closing Modal — Step 1 (closing-type selection).
 *
 * Foundation for the upcoming sale-confirmation flow. Before a seller
 * "closes" (soft-deletes) a listing they pick HOW it was closed via a
 * custom Livewire/Alpine modal that replaces the old native wire:confirm.
 *
 *   - openClosingModal() sets the modal state (verified by ownership).
 *   - confirmClosing() with sold_external / canceled soft-deletes the listing.
 *   - sold_platform does NOT delete (Step 2 — buyer list — is a later phase).
 */

use App\Livewire\Frontend\UserDashboard;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create(['is_phone_verified' => true]);

    $this->category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-closing-modal',
        'is_active' => true,
    ]);
});

function makeClosingListing(User $user, Category $category, array $overrides = []): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create(array_merge([
        'title'       => 'إعلان للإغلاق ' . $counter,
        'slug'        => 'closing-listing-' . $counter . '-' . uniqid(),
        'description' => 'وصف تجريبي.',
        'price'       => 10_000,
        'category_id' => $category->id,
        'user_id'     => $user->id,
        'status'      => Listing::STATUS_PUBLISHED,
    ], $overrides));
}

// ═══════════════════════════════════════════════════════════════════════════
// 1) openClosingModal opens the modal and pins the exact listing
// ═══════════════════════════════════════════════════════════════════════════

it('opens the closing modal for the owner and pins the exact listing id', function () {
    $listing = makeClosingListing($this->seller, $this->category, ['title' => 'إعلان محدد للإغلاق']);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->assertSet('closingModalOpen', true)
        ->assertSet('closingListingId', $listing->id)
        ->assertSet('closingListingTitle', 'إعلان محدد للإغلاق')
        ->assertSet('closingType', null);

    // The listing is untouched merely by opening the modal.
    $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
});

it('rejects opening the closing modal for a listing owned by someone else (IDOR)', function () {
    $listing  = makeClosingListing($this->seller, $this->category);
    $intruder = User::factory()->create(['is_phone_verified' => true]);

    expect(fn () => Livewire::actingAs($intruder)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
});

// ═══════════════════════════════════════════════════════════════════════════
// 2) confirmClosing soft-deletes for sold_external / canceled
// ═══════════════════════════════════════════════════════════════════════════

it('soft-deletes the listing when closing type is sold_external or canceled', function (string $type) {
    $listing = makeClosingListing($this->seller, $this->category);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->set('closingType', $type)
        ->call('confirmClosing')
        ->assertSet('closingModalOpen', false)
        ->assertSet('closingListingId', null);

    $this->assertSoftDeleted('listings', ['id' => $listing->id]);
    expect(Listing::withTrashed()->find($listing->id)->deleted_at)->not->toBeNull();
})->with(['sold_external', 'canceled']);

// ═══════════════════════════════════════════════════════════════════════════
// 3) sold_platform does NOT delete in this step (Step 2 is deferred)
// ═══════════════════════════════════════════════════════════════════════════

it('does not delete the listing when closing type is sold_platform (Step 1 stub)', function () {
    $listing = makeClosingListing($this->seller, $this->category);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->set('closingType', 'sold_platform')
        ->call('confirmClosing');

    // The listing survives untouched — the buyer-selection flow comes later.
    $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
    expect(Listing::find($listing->id))->not->toBeNull();
});

// ═══════════════════════════════════════════════════════════════════════════
// 4) toggleClosingType — radio behaves as a toggle (re-click clears it)
// ═══════════════════════════════════════════════════════════════════════════

it('toggles the closing type off when the same option is picked twice', function () {
    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        // First click selects the type…
        ->call('toggleClosingType', 'sold_external')
        ->assertSet('closingType', 'sold_external')
        // …re-clicking the SAME type clears it back to null…
        ->call('toggleClosingType', 'sold_external')
        ->assertSet('closingType', null)
        // …and clicking a DIFFERENT type switches to it.
        ->call('toggleClosingType', 'canceled')
        ->assertSet('closingType', 'canceled');
});

it('ignores an invalid closing type (allowed-values guard)', function () {
    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->set('closingType', 'sold_platform')
        ->call('toggleClosingType', 'not_a_real_type')
        // Unchanged — the guard rejects values outside CLOSING_TYPES.
        ->assertSet('closingType', 'sold_platform');
});

// ═══════════════════════════════════════════════════════════════════════════
// 5) closeClosingModal fully resets the modal state
// ═══════════════════════════════════════════════════════════════════════════

it('resets all modal state (incl. closingModalOpen) on close', function () {
    $listing = makeClosingListing($this->seller, $this->category);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('openClosingModal', $listing->id)
        ->set('closingType', 'sold_external')
        ->assertSet('closingModalOpen', true)
        ->call('closeClosingModal')
        ->assertSet('closingModalOpen', false)
        ->assertSet('closingListingId', null)
        ->assertSet('closingListingTitle', null)
        ->assertSet('closingType', null);

    // Closing alone must never delete the listing.
    $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
});
