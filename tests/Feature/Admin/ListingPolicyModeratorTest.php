<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Admin Permission Fix — ListingPolicy (the REAL security proof).
 *
 * The pending-review queue is staffed by `moderator` users (NOT super_admin).
 * The old ListingPolicy checked Shield's new-style names (`View:Listing`,
 * `Update:Listing`, …) which **no seeded role has** — so a real moderator got
 * `canView = false` → the listing row was unclickable and `GET /admin/listings/{id}`
 * returned 403. Moderators literally could not open a listing to review its
 * images + description before approving/rejecting (a moderation blind spot).
 * super_admin only worked because Shield's Gate::before bypasses every policy.
 *
 * These tests act as a genuine `moderator` (with the actually-seeded
 * `view_listings` / `approve_listings` / `reject_listings` permissions) and
 * prove the View page now opens (200), shows the content + the approve/reject
 * buttons, while management (edit) stays blocked.
 */

use App\Filament\Admin\Resources\Listings\ListingResource;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Mirror the real DB: snake_case Shield permissions assigned to a moderator role.
    foreach (['view_listings', 'approve_listings', 'reject_listings'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $this->moderatorRole = Role::findOrCreate('moderator', 'web');
    $this->moderatorRole->givePermissionTo(['view_listings', 'approve_listings', 'reject_listings']);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->moderator = User::factory()->create(['is_phone_verified' => true]);
    $this->moderator->assignRole('moderator');

    $this->owner = User::factory()->create(['is_phone_verified' => true]);

    $this->category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-moderator-policy',
        'is_active' => true,
    ]);

    $this->listing = Listing::create([
        'title'       => 'إعلان معلّق لمراجعة المشرف',
        'slug'        => 'pending-moderator-' . uniqid(),
        'description' => '<p>وصف كامل يجب أن يراه المشرف قبل القبول.</p>',
        'price'       => 30_000,
        'category_id' => $this->category->id,
        'user_id'     => $this->owner->id,
        'status'      => Listing::STATUS_PENDING,
    ]);
});

// ═══════════════════════════════════════════════════════════════════════════
// The core regression: the old (broken) name vs the new (working) policy
// ═══════════════════════════════════════════════════════════════════════════

it('grants view to a moderator via the real permission name, not the old Shield name', function () {
    // Proof the bug existed: the moderator does NOT hold the old-style ability…
    expect($this->moderator->can('View:Listing'))->toBeFalse();
    // …yet the policy now authorizes view via the seeded snake_case permission.
    expect($this->moderator->can('view_listings'))->toBeTrue();

    $this->actingAs($this->moderator);
    expect(ListingResource::canViewAny())->toBeTrue();
    expect(ListingResource::canView($this->listing))->toBeTrue();

    // Management stays blocked (no update_listings permission seeded).
    expect(ListingResource::canEdit($this->listing))->toBeFalse();
});

// ═══════════════════════════════════════════════════════════════════════════
// The decisive end-to-end proof: a moderator can OPEN and review the listing
// ═══════════════════════════════════════════════════════════════════════════

it('lets a moderator open the listings index (200)', function () {
    $this->actingAs($this->moderator)
        ->get(ListingResource::getUrl('index'))
        ->assertOk();
});

it('lets a moderator open the View page and see content + approve/reject buttons', function () {
    $this->actingAs($this->moderator)
        ->get(ListingResource::getUrl('view', ['record' => $this->listing->id]))
        ->assertOk()                                   // was 403 before the fix
        ->assertSee('إعلان معلّق لمراجعة المشرف')        // title
        ->assertSee('وصف كامل يجب أن يراه المشرف قبل القبول', false) // full description (html)
        ->assertSee('صور الإعلان')                      // gallery section (infolist)
        ->assertSee('موافقة ونشر')                      // approve header button visible
        ->assertSee('رفض الإعلان');                     // reject header button visible
});

it('blocks a moderator from the Edit page (management stays restricted)', function () {
    $this->actingAs($this->moderator)
        ->get(ListingResource::getUrl('edit', ['record' => $this->listing->id]))
        ->assertForbidden();
});

it('rejects a user with no role entirely (sanity — not just super_admin bypass)', function () {
    $nobody = User::factory()->create(['is_phone_verified' => true]);

    $this->actingAs($nobody);
    expect(ListingResource::canViewAny())->toBeFalse();
    expect(ListingResource::canView($this->listing))->toBeFalse();

    $this->get(ListingResource::getUrl('view', ['record' => $this->listing->id]))
        ->assertForbidden();
});
