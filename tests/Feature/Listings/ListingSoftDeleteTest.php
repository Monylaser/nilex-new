<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Listing Soft Deletes (foundation for the upcoming sale-confirmation +
 * ratings system — a deleted listing must survive in the DB so a rating
 * can always reference it).
 *
 *   - deleteListing() now soft-deletes (row stays, deleted_at set).
 *   - A soft-deleted listing disappears from home / search / category.
 *   - Its detail page returns 404 (route-model-binding honours the scope).
 *   - Its media rows are PRESERVED (deleteListing no longer purges them).
 *   - The admin TrashedFilter query semantics reveal trashed records.
 *   - CategoryPerformanceWidget aggregation excludes soft-deleted listings.
 *
 * Notes:
 *   SCOUT_DRIVER=collection (phpunit.xml) → search runs through Eloquent,
 *   so the SoftDeletes global scope excludes trashed rows automatically.
 */

use App\Filament\Admin\Resources\Listings\ListingResource;
use App\Livewire\Frontend\UserDashboard;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingPhoneClick;
use App\Models\ListingView;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create(['is_phone_verified' => true]);

    $this->category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-soft-delete',
        'is_active' => true,
    ]);
});

function makeSoftDeleteListing(User $user, Category $category, array $overrides = []): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create(array_merge([
        'title'       => 'إعلان قابل للحذف ' . $counter,
        'slug'        => 'soft-delete-listing-' . $counter . '-' . uniqid(),
        'description' => 'وصف تجريبي.',
        'price'       => 10_000,
        'category_id' => $category->id,
        'user_id'     => $user->id,
        'status'      => Listing::STATUS_PUBLISHED,
    ], $overrides));
}

// ═══════════════════════════════════════════════════════════════════════════
// 1) deleteListing is now a soft delete
// ═══════════════════════════════════════════════════════════════════════════

it('soft-deletes the listing instead of hard-deleting it', function () {
    $listing = makeSoftDeleteListing($this->seller, $this->category);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('deleteListing', $listing->id);

    // The row survives in the table with deleted_at set.
    $this->assertSoftDeleted('listings', ['id' => $listing->id]);

    // Default (scoped) lookup hides it; withTrashed() still finds it.
    expect(Listing::find($listing->id))->toBeNull();
    expect(Listing::withTrashed()->find($listing->id))->not->toBeNull();
    expect(Listing::withTrashed()->find($listing->id)->deleted_at)->not->toBeNull();
});

it('only lets the owner delete the listing (IDOR is rejected)', function () {
    $listing = makeSoftDeleteListing($this->seller, $this->category);
    $intruder = User::factory()->create(['is_phone_verified' => true]);

    // findOrFail scopes by user_id → another user hits ModelNotFoundException
    // (rendered as a 404 over HTTP); the listing is never touched.
    expect(fn () => Livewire::actingAs($intruder)
        ->test(UserDashboard::class)
        ->call('deleteListing', $listing->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    $this->assertNotSoftDeleted('listings', ['id' => $listing->id]);
});

// ═══════════════════════════════════════════════════════════════════════════
// 2) A soft-deleted listing disappears from public surfaces
// ═══════════════════════════════════════════════════════════════════════════

it('hides a soft-deleted listing from the home page', function () {
    $kept    = makeSoftDeleteListing($this->seller, $this->category, ['title' => 'إعلان باقٍ في الرئيسية']);
    $deleted = makeSoftDeleteListing($this->seller, $this->category, ['title' => 'إعلان محذوف من الرئيسية']);

    $deleted->delete();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('إعلان باقٍ في الرئيسية')
        ->assertDontSee('إعلان محذوف من الرئيسية');
});

it('hides a soft-deleted listing from search results', function () {
    $kept    = makeSoftDeleteListing($this->seller, $this->category, ['title' => 'سامسونج باقٍ']);
    $deleted = makeSoftDeleteListing($this->seller, $this->category, ['title' => 'سامسونج محذوف']);

    $deleted->delete();

    $this->get(route('listings.search'))
        ->assertOk()
        ->assertViewHas('listings', function ($listings) use ($deleted) {
            return ! $listings->contains('id', $deleted->id);
        })
        ->assertDontSee('سامسونج محذوف');
});

it('hides a soft-deleted listing from its category page', function () {
    $kept    = makeSoftDeleteListing($this->seller, $this->category, ['title' => 'إعلان قسم باقٍ']);
    $deleted = makeSoftDeleteListing($this->seller, $this->category, ['title' => 'إعلان قسم محذوف']);

    $deleted->delete();

    $this->get(route('category.show', $this->category))
        ->assertOk()
        ->assertSee('إعلان قسم باقٍ')
        ->assertDontSee('إعلان قسم محذوف');
});

it('returns 404 for a soft-deleted listing detail page', function () {
    $listing = makeSoftDeleteListing($this->seller, $this->category);

    $this->get(route('listings.show', $listing))->assertOk();

    $listing->delete();

    $this->get(route('listings.show', $listing))->assertNotFound();
});

// ═══════════════════════════════════════════════════════════════════════════
// 3) Media is preserved on soft delete
// ═══════════════════════════════════════════════════════════════════════════

it('keeps the listing media rows after a soft delete', function () {
    $listing = makeSoftDeleteListing($this->seller, $this->category);

    // Create a media row directly (no file I/O / conversions) to assert the
    // deleteListing() flow no longer purges the collection.
    $listing->media()->create([
        'collection_name'       => 'images',
        'name'                  => 'test-image',
        'file_name'             => 'test-image.jpg',
        'mime_type'             => 'image/jpeg',
        'disk'                  => 'public',
        'size'                  => 1024,
        'manipulations'         => [],
        'custom_properties'     => [],
        'generated_conversions' => [],
        'responsive_images'     => [],
    ]);

    Livewire::actingAs($this->seller)
        ->test(UserDashboard::class)
        ->call('deleteListing', $listing->id);

    $this->assertSoftDeleted('listings', ['id' => $listing->id]);

    // The media row must still exist (images retained with the deleted listing).
    $this->assertDatabaseHas('media', [
        'model_type'      => Listing::class,
        'model_id'        => $listing->id,
        'collection_name' => 'images',
    ]);
});

// ═══════════════════════════════════════════════════════════════════════════
// 4) Admin TrashedFilter query semantics
//    (mirrors Filament v5 TrashedFilter: it removes the SoftDeletingScope and
//    toggles withTrashed()/onlyTrashed()/withoutTrashed() on the base query.)
// ═══════════════════════════════════════════════════════════════════════════

it('admin base query hides trashed but the TrashedFilter reveals them', function () {
    $published = makeSoftDeleteListing($this->seller, $this->category, ['title' => 'منشور للأدمن']);
    $trashed   = makeSoftDeleteListing($this->seller, $this->category, ['title' => 'محذوف للأدمن']);
    $trashed->delete();

    // Default admin listing query (no filter) → trashed are hidden.
    $defaultIds = ListingResource::getEloquentQuery()->pluck('id');
    expect($defaultIds)->toContain($published->id);
    expect($defaultIds)->not->toContain($trashed->id);

    // "With trashed" path of the filter → trashed visible alongside the rest.
    $withTrashedIds = ListingResource::getEloquentQuery()
        ->withoutGlobalScopes([SoftDeletingScope::class])
        ->withTrashed()
        ->pluck('id');
    expect($withTrashedIds)->toContain($trashed->id);
    expect($withTrashedIds)->toContain($published->id);

    // "Only trashed" path → just the deleted records.
    $onlyTrashedIds = ListingResource::getEloquentQuery()
        ->withoutGlobalScopes([SoftDeletingScope::class])
        ->onlyTrashed()
        ->pluck('id');
    expect($onlyTrashedIds)->toContain($trashed->id);
    expect($onlyTrashedIds)->not->toContain($published->id);
});

// ═══════════════════════════════════════════════════════════════════════════
// 5) CategoryPerformanceWidget aggregation excludes soft-deleted listings
// ═══════════════════════════════════════════════════════════════════════════

it('excludes soft-deleted listings from CategoryPerformanceWidget aggregation', function () {
    $listing = makeSoftDeleteListing($this->seller, $this->category);

    // Recent events that the widget would aggregate (within the 7-day window).
    ListingView::create(['listing_id' => $listing->id]);
    ListingPhoneClick::create(['listing_id' => $listing->id]);

    $widget = new \App\Filament\Admin\Widgets\CategoryPerformanceWidget();
    $widget->filter = 'last_7_days';

    $invoke = function () use ($widget) {
        $method = new ReflectionMethod($widget, 'getCategoryPerformanceQuery');
        $method->setAccessible(true);

        return $method->invoke($widget)->get();
    };

    // Before deletion: the category shows up with its metrics.
    $before = $invoke();
    expect($before->firstWhere('id', $this->category->id))->not->toBeNull();
    expect((int) $before->firstWhere('id', $this->category->id)->views_count)->toBe(1);
    expect((int) $before->firstWhere('id', $this->category->id)->phone_clicks_count)->toBe(1);

    // After soft delete: its events are excluded → category drops out.
    $listing->delete();

    $after = $invoke();
    expect($after->firstWhere('id', $this->category->id))->toBeNull();
});
