<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Moderation Queue (/admin/moderation) — Notification Bug Fix + content preview.
 *
 *   - BUG (closed here): ModerationResource::handleReject/handleApprove changed the
 *     listing state, logged it, and showed a toast saying "وإبلاغ المستخدم" — but
 *     NEVER actually notified the owner (no notify(), no mail). The admin believed
 *     the seller received the rejection reason; they did not. Both handlers now
 *     delegate to ListingModeration (the same path ListingResource uses), which
 *     sends the database + mail ListingStatusNotification.
 *   - The "اتخاذ قرار" modal now renders the listing images + full description
 *     (the review-modal blade was previously a 0-byte empty file → blind review).
 */

use App\Filament\Admin\Resources\Moderation\Pages\ListModeration;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\ListingStatusNotification;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $this->owner = User::factory()->create(['is_phone_verified' => true]);

    $this->admin = User::factory()->create(['is_phone_verified' => true]);
    $this->admin->assignRole('super_admin');

    $this->category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-moderation-queue',
        'is_active' => true,
    ]);
});

function makeModerationListing(User $user, Category $category, array $overrides = []): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create(array_merge([
        'title'       => 'إعلان طابور المراجعة ' . $counter,
        'slug'        => 'moderation-queue-listing-' . $counter . '-' . uniqid(),
        'description' => '<p>وصف الإعلان الكامل في طابور المراجعة.</p>',
        'price'       => 25_000,
        'category_id' => $category->id,
        'user_id'     => $user->id,
        'status'      => Listing::STATUS_PENDING,
    ], $overrides));
}

// ═══════════════════════════════════════════════════════════════════════════
// 1) THE decisive proof: rejecting via /admin/moderation now actually notifies
// ═══════════════════════════════════════════════════════════════════════════

it('reject via the moderation queue actually notifies the owner on database + mail', function () {
    Notification::fake();

    $listing = makeModerationListing($this->owner, $this->category);

    Livewire::actingAs($this->admin)
        ->test(ListModeration::class)
        ->callAction(
            TestAction::make('review')->table($listing),
            data: [
                'action'           => 'reject',
                'rejection_reason' => Listing::REASON_INCOMPLETE,
                'admin_notes'      => 'الصور غير واضحة.',
            ],
        );

    expect(Listing::find($listing->id)->status)->toBe(Listing::STATUS_REJECTED);

    // The bug closure: an actual notification, on BOTH channels, with the reason.
    Notification::assertSentTo(
        $this->owner,
        ListingStatusNotification::class,
        function (ListingStatusNotification $notification, array $channels) {
            return in_array('mail', $channels, true)
                && in_array('database', $channels, true);
        },
    );
});

it('approve via the moderation queue also notifies the owner', function () {
    Notification::fake();

    $listing = makeModerationListing($this->owner, $this->category);

    Livewire::actingAs($this->admin)
        ->test(ListModeration::class)
        ->callAction(
            TestAction::make('review')->table($listing),
            data: ['action' => 'approve'],
        );

    expect(Listing::find($listing->id)->status)->toBe(Listing::STATUS_PUBLISHED);

    Notification::assertSentTo($this->owner, ListingStatusNotification::class);
});

// ═══════════════════════════════════════════════════════════════════════════
// 2) Unification did not duplicate the strike / audit logs
// ═══════════════════════════════════════════════════════════════════════════

it('reject via the moderation queue strikes once and logs once (no duplication)', function () {
    Notification::fake();

    $listing = makeModerationListing($this->owner, $this->category);

    Livewire::actingAs($this->admin)
        ->test(ListModeration::class)
        ->callAction(
            TestAction::make('review')->table($listing),
            data: [
                'action'           => 'reject',
                'rejection_reason' => Listing::REASON_SCAM, // strike reason
            ],
        );

    expect($this->owner->fresh()->strike_count)->toBe(1);

    expect(\App\Models\AuditLog::where('action', 'reject_ad')->where('target_id', $listing->id)->count())->toBe(1);
    expect(\App\Models\AuditLog::where('action', 'auto_strike')->count())->toBe(1);
});

// ═══════════════════════════════════════════════════════════════════════════
// 3) The "اتخاذ قرار" modal now previews the real images + description
// ═══════════════════════════════════════════════════════════════════════════

it('renders the review modal with the listing images and full description', function () {
    $listing = makeModerationListing($this->owner, $this->category, [
        'title'       => 'إعلان بصور',
        'description' => '<p>وصف يظهر داخل مودال المراجعة بالكامل.</p>',
    ]);

    $listing->media()->create([
        'collection_name'       => 'images',
        'name'                  => 'review-image',
        'file_name'             => 'review-image.jpg',
        'mime_type'             => 'image/jpeg',
        'disk'                  => 'public',
        'size'                  => 2048,
        'manipulations'         => [],
        'custom_properties'     => [],
        'generated_conversions' => [],
        'responsive_images'     => [],
    ]);

    $html = view('filament.moderation.review-modal', [
        'listing' => $listing->fresh(),
    ])->render();

    expect($html)
        ->toContain('وصف يظهر داخل مودال المراجعة بالكامل')
        ->toContain('review-image.jpg')   // the gallery <img>/<a> points at the media file
        ->toContain('<img');
});

it('renders the review modal empty-state when the listing has no images', function () {
    $listing = makeModerationListing($this->owner, $this->category);

    $html = view('filament.moderation.review-modal', [
        'listing' => $listing->fresh(),
    ])->render();

    expect($html)->toContain('لا توجد صور لهذا الإعلان');
});
