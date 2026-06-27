<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Admin Review — Image Collection Fix + unified approve/reject + seller
 * dashboard rating display.
 *
 *   - listings:migrate-media-collection moves media from the legacy 'listings'
 *     collection to the canonical 'images' collection (the moderation blind-spot
 *     fix: admin reads now match where user uploads land).
 *   - ListingModeration::approve/reject is the single source of truth shared by
 *     the table row actions AND the View page header (AuditLog + owner
 *     notification + auto-strike), removing the previous divergent paths.
 *   - The admin View page now renders an infolist (full description + image
 *     gallery from 'images') instead of the disabled edit form.
 *   - The seller dashboard shows the seller's own rating via <x-rating-stars>.
 */

use App\Filament\Admin\Resources\Listings\Support\ListingModeration;
use App\Livewire\Frontend\UserDashboard;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\ListingStatusNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::factory()->create(['is_phone_verified' => true]);

    $this->category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-admin-review',
        'is_active' => true,
    ]);
});

function makeReviewListing(User $user, Category $category, array $overrides = []): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create(array_merge([
        'title'       => 'إعلان للمراجعة ' . $counter,
        'slug'        => 'admin-review-listing-' . $counter . '-' . uniqid(),
        'description' => '<p>وصف الإعلان الكامل للمراجعة.</p>',
        'price'       => 25_000,
        'category_id' => $category->id,
        'user_id'     => $user->id,
        'status'      => Listing::STATUS_PENDING,
    ], $overrides));
}

// ═══════════════════════════════════════════════════════════════════════════
// 1) Media migration command: 'listings' → 'images'
// ═══════════════════════════════════════════════════════════════════════════

it('migrates listing media from the legacy listings collection to images', function () {
    // Avoid real file I/O / GD conversions — the command regenerates derived
    // files, which we stub since the fixture media has no physical file.
    $this->mock(FileManipulator::class, function ($mock) {
        $mock->shouldReceive('createDerivedFiles')->andReturnNull();
    });

    $listing = makeReviewListing($this->owner, $this->category);

    $legacy = $listing->media()->create([
        'collection_name'       => 'listings',
        'name'                  => 'legacy-image',
        'file_name'             => 'legacy-image.jpg',
        'mime_type'             => 'image/jpeg',
        'disk'                  => 'public',
        'size'                  => 2048,
        'manipulations'         => [],
        'custom_properties'     => [],
        'generated_conversions' => [],
        'responsive_images'     => [],
    ]);

    $this->artisan('listings:migrate-media-collection')->assertExitCode(0);

    $this->assertDatabaseHas('media', [
        'id'              => $legacy->id,
        'collection_name' => 'images',
    ]);
    $this->assertDatabaseMissing('media', [
        'id'              => $legacy->id,
        'collection_name' => 'listings',
    ]);
});

it('does nothing when there is no legacy listings media', function () {
    $listing = makeReviewListing($this->owner, $this->category);

    // A media row already in 'images' must be left untouched.
    $listing->media()->create([
        'collection_name'       => 'images',
        'name'                  => 'already-images',
        'file_name'             => 'already-images.jpg',
        'mime_type'             => 'image/jpeg',
        'disk'                  => 'public',
        'size'                  => 2048,
        'manipulations'         => [],
        'custom_properties'     => [],
        'generated_conversions' => [],
        'responsive_images'     => [],
    ]);

    $this->artisan('listings:migrate-media-collection')->assertExitCode(0);

    $this->assertDatabaseHas('media', [
        'model_id'        => $listing->id,
        'collection_name' => 'images',
    ]);
});

// ═══════════════════════════════════════════════════════════════════════════
// 2) Unified approve/reject (shared by table + View page)
// ═══════════════════════════════════════════════════════════════════════════

it('approve publishes the listing, logs the action, and notifies the owner', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_phone_verified' => true]);
    $listing = makeReviewListing($this->owner, $this->category);

    $this->actingAs($admin);
    ListingModeration::approve($listing->fresh());

    expect(Listing::find($listing->id)->status)->toBe(Listing::STATUS_PUBLISHED);

    $this->assertDatabaseHas('audit_logs', [
        'action'      => 'approve_ad',
        'target_type' => Listing::class,
        'target_id'   => $listing->id,
    ]);

    Notification::assertSentTo($this->owner, ListingStatusNotification::class);
});

it('reject with a strike reason rejects, strikes the owner, logs both, notifies', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_phone_verified' => true]);
    $listing = makeReviewListing($this->owner, $this->category);

    expect($this->owner->fresh()->strike_count)->toBe(0);

    $this->actingAs($admin);
    $label = ListingModeration::reject($listing->fresh(), Listing::REASON_SCAM);

    expect(Listing::find($listing->id)->status)->toBe(Listing::STATUS_REJECTED);
    expect($this->owner->fresh()->strike_count)->toBe(1);
    expect($label)->toBe(Listing::rejectionReasonOptions()[Listing::REASON_SCAM]);

    $this->assertDatabaseHas('audit_logs', ['action' => 'auto_strike']);
    $this->assertDatabaseHas('audit_logs', [
        'action'    => 'reject_ad',
        'target_id' => $listing->id,
    ]);

    Notification::assertSentTo($this->owner, ListingStatusNotification::class);
});

it('reject with a non-strike reason does not strike the owner', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_phone_verified' => true]);
    $listing = makeReviewListing($this->owner, $this->category);

    $this->actingAs($admin);
    ListingModeration::reject($listing->fresh(), Listing::REASON_DUPLICATE);

    expect(Listing::find($listing->id)->status)->toBe(Listing::STATUS_REJECTED);
    expect($this->owner->fresh()->strike_count)->toBe(0);

    $this->assertDatabaseMissing('audit_logs', ['action' => 'auto_strike']);
    $this->assertDatabaseHas('audit_logs', [
        'action'    => 'reject_ad',
        'target_id' => $listing->id,
    ]);
});

// ═══════════════════════════════════════════════════════════════════════════
// 3) Admin View page renders the infolist (full description + gallery section)
// ═══════════════════════════════════════════════════════════════════════════

it('renders the admin view infolist with full description and an images section', function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $admin = User::factory()->create(['is_phone_verified' => true]);
    $admin->assignRole('super_admin');

    $listing = makeReviewListing($this->owner, $this->category, [
        'title'       => 'إعلان معروض في الأدمن',
        'description' => '<p>هذا وصف كامل يظهر في صفحة العرض.</p>',
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.listings.view', ['record' => $listing->id]))
        ->assertOk()
        ->assertSee('إعلان معروض في الأدمن')
        ->assertSee('هذا وصف كامل يظهر في صفحة العرض', false)
        ->assertSee('صور الإعلان');
});

// ═══════════════════════════════════════════════════════════════════════════
// 4) Seller dashboard shows the seller's own rating (shared <x-rating-stars>)
// ═══════════════════════════════════════════════════════════════════════════

it('shows the seller rating summary on the dashboard when the seller has reviews', function () {
    $seller = User::factory()->create([
        'is_phone_verified' => true,
        'ratings_avg'       => 4.5,
        'ratings_count'     => 12,
    ]);

    Livewire::actingAs($seller)
        ->test(UserDashboard::class)
        ->assertSee('4.5 من 5')
        ->assertSee('12');
});

it('shows the no-ratings placeholder on the dashboard when the seller has none', function () {
    $seller = User::factory()->create([
        'is_phone_verified' => true,
        'ratings_avg'       => null,
        'ratings_count'     => 0,
    ]);

    Livewire::actingAs($seller)
        ->test(UserDashboard::class)
        ->assertSee('لا توجد تقييمات بعد');
});
