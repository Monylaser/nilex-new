<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Listing Edit feature (HomeController::edit / update — reuses the wizard view).
 *
 * Contract covered:
 *   - edit() renders the wizard in "edit" mode with a correctly pre-filled DTO
 *     (editData / editImages / editRootId / mode).
 *   - The pending-SaleConfirmation guard blocks BOTH edit (GET) and update (PUT).
 *   - category_id is locked: a category_id in the request is ignored.
 *   - No points are granted on edit (points only on create — proven against store).
 *   - The slug is preserved (never regenerated).
 *   - status returns to pending after ANY successful update.
 *   - Images: new uploads are appended, current images can be deleted, and the
 *     simple ordering holds (remaining current block first, new appended last).
 *   - Server-side image mime/size validation rejects invalid uploads (the
 *     security fix — client-side is not trusted).
 *   - Ownership is enforced (404 for non-owners) on both edit and update.
 *   - store() is unaffected (still creates pending + grants +3 points).
 *
 * NB: the legacy media-collection migration (listings→images) is covered by
 * tests/Feature/Admin/AdminListingReviewTest.php and is not duplicated here.
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\SaleConfirmation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\FileManipulator;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create(['is_phone_verified' => true, 'points' => 100]);

    $this->category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-edit-' . uniqid(),
        'is_active' => true,
    ]);

    $this->otherCategory = Category::create([
        'name_ar'   => 'أثاث',
        'name_en'   => 'Furniture',
        'slug'      => 'furniture-edit-' . uniqid(),
        'is_active' => true,
    ]);
});

function makeEditListing(User $user, Category $category, array $overrides = []): Listing
{
    static $c = 0;
    $c++;

    return Listing::create(array_merge([
        'title'       => 'Listing Edit ' . $c,
        'slug'        => 'listing-edit-' . $c . '-' . uniqid(),
        'description' => 'A valid original description long enough.',
        'price'       => 12345,
        'category_id' => $category->id,
        'user_id'     => $user->id,
        'status'      => Listing::STATUS_PUBLISHED,
        'condition'   => 'used',
        'price_type'  => 'fixed',
        'phone'       => '01000000000',
    ], $overrides));
}

function editPayload(array $overrides = []): array
{
    return array_merge([
        'title'       => 'Updated title',
        'description' => 'Updated description that is definitely long enough.',
        'price'       => 22222,
        'condition'   => 'new',
        'price_type'  => 'negotiable',
        'phone'       => '01111111111',
    ], $overrides);
}

function jsonHeaders(): array
{
    return ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'];
}

function fakeImageMedia(Listing $listing, int $order): \Spatie\MediaLibrary\MediaCollections\Models\Media
{
    return $listing->media()->create([
        'collection_name'       => 'images',
        'name'                  => 'img-' . $order,
        'file_name'             => 'img-' . $order . '.jpg',
        'mime_type'             => 'image/jpeg',
        'disk'                  => 'public',
        'conversions_disk'      => 'public',
        'size'                  => 2048,
        'manipulations'         => [],
        'custom_properties'     => [],
        'generated_conversions' => [],
        'responsive_images'     => [],
        'order_column'          => $order,
    ]);
}

// ═══════════════════════════════════════════════════════════════════════════
// 1) edit() renders the wizard in edit mode with a correctly pre-filled DTO
// ═══════════════════════════════════════════════════════════════════════════

it('renders the edit wizard pre-filled with the listing data', function () {
    $listing = makeEditListing($this->seller, $this->category, [
        'location_id' => null,
    ]);
    fakeImageMedia($listing, 1);
    fakeImageMedia($listing, 2);

    $response = $this->actingAs($this->seller)->get(route('listings.edit', $listing));

    $response->assertOk();
    expect($response->viewData('mode'))->toBe('edit');

    $dto = $response->viewData('editData');
    expect($dto['title'])->toBe($listing->title);
    expect($dto['description'])->toBe($listing->description);
    expect($dto['condition'])->toBe($listing->condition);
    expect($dto['price_type'])->toBe($listing->price_type);
    expect((int) $dto['category_id'])->toBe($listing->category_id);
    expect($dto['feature_days'])->toBe(0);

    // Root id = the category itself (electronics has no parent).
    expect($response->viewData('editRootId'))->toBe($listing->category_id);

    // Existing images exposed as {id,url} for the fixed current-images block.
    expect($response->viewData('editImages'))->toHaveCount(2);
});

// ═══════════════════════════════════════════════════════════════════════════
// 2) Pending SaleConfirmation guard blocks edit (GET) and update (PUT)
// ═══════════════════════════════════════════════════════════════════════════

it('blocks editing (GET) when a pending sale confirmation exists', function () {
    $listing = makeEditListing($this->seller, $this->category);
    $buyer   = User::factory()->create();

    SaleConfirmation::create([
        'listing_id'          => $listing->id,
        'seller_id'           => $this->seller->id,
        'buyer_id'            => $buyer->id,
        'status'              => SaleConfirmation::STATUS_PENDING,
        'seller_confirmed_at' => now(),
    ]);

    $this->actingAs($this->seller)
        ->get(route('listings.edit', $listing))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');
});

it('blocks updating (PUT) when a pending sale confirmation exists', function () {
    $listing = makeEditListing($this->seller, $this->category);
    $buyer   = User::factory()->create();

    SaleConfirmation::create([
        'listing_id'          => $listing->id,
        'seller_id'           => $this->seller->id,
        'buyer_id'            => $buyer->id,
        'status'              => SaleConfirmation::STATUS_PENDING,
        'seller_confirmed_at' => now(),
    ]);

    $this->actingAs($this->seller)
        ->putJson(route('listings.update', $listing), editPayload())
        ->assertStatus(422);

    // Nothing changed.
    expect($listing->fresh()->title)->toBe($listing->title);
    expect($listing->fresh()->status)->toBe(Listing::STATUS_PUBLISHED);
});

// ═══════════════════════════════════════════════════════════════════════════
// 3) category_id is locked — a request category_id is ignored
// ═══════════════════════════════════════════════════════════════════════════

it('ignores any category_id sent in the update request (category locked)', function () {
    $listing = makeEditListing($this->seller, $this->category);

    $this->actingAs($this->seller)
        ->putJson(route('listings.update', $listing), editPayload([
            'category_id' => $this->otherCategory->id,
        ]))
        ->assertOk();

    expect($listing->fresh()->category_id)->toBe($this->category->id);
});

// ═══════════════════════════════════════════════════════════════════════════
// 4) No points are granted on edit
// ═══════════════════════════════════════════════════════════════════════════

it('does not grant points when editing a listing', function () {
    $listing = makeEditListing($this->seller, $this->category);
    $before  = $this->seller->fresh()->points;

    $this->actingAs($this->seller)
        ->putJson(route('listings.update', $listing), editPayload())
        ->assertOk();

    expect($this->seller->fresh()->points)->toBe($before);
});

// ═══════════════════════════════════════════════════════════════════════════
// 5) Slug is preserved (never regenerated)
// ═══════════════════════════════════════════════════════════════════════════

it('preserves the original slug after an update', function () {
    $listing      = makeEditListing($this->seller, $this->category);
    $originalSlug = $listing->slug;

    $this->actingAs($this->seller)
        ->putJson(route('listings.update', $listing), editPayload(['title' => 'A Completely Different Title']))
        ->assertOk();

    expect($listing->fresh()->slug)->toBe($originalSlug);
});

// ═══════════════════════════════════════════════════════════════════════════
// 6) status always returns to pending after a successful update
// ═══════════════════════════════════════════════════════════════════════════

it('always sets status back to pending after an update', function () {
    foreach ([Listing::STATUS_PUBLISHED, Listing::STATUS_REJECTED, Listing::STATUS_FLAGGED] as $status) {
        $listing = makeEditListing($this->seller, $this->category, ['status' => $status]);

        $this->actingAs($this->seller)
            ->putJson(route('listings.update', $listing), editPayload())
            ->assertOk();

        expect($listing->fresh()->status)->toBe(Listing::STATUS_PENDING);
    }
});

it('updates the editable fields on a successful update', function () {
    $listing = makeEditListing($this->seller, $this->category);

    $this->actingAs($this->seller)
        ->putJson(route('listings.update', $listing), editPayload([
            'title' => 'My new title',
            'price' => 33333,
        ]))
        ->assertOk();

    $fresh = $listing->fresh();
    expect($fresh->title)->toBe('My new title');
    expect((int) $fresh->price)->toBe(33333);
    expect($fresh->condition)->toBe('new');
});

// ═══════════════════════════════════════════════════════════════════════════
// 7) Images — append new, delete current, simple ordering
// ═══════════════════════════════════════════════════════════════════════════

it('appends newly uploaded images to the listing', function () {
    Storage::fake('public');
    $this->mock(FileManipulator::class, function ($mock) {
        $mock->shouldReceive('createDerivedFiles')->andReturnNull();
    });

    $listing = makeEditListing($this->seller, $this->category);
    fakeImageMedia($listing, 1);

    $this->actingAs($this->seller)
        ->put(route('listings.update', $listing), editPayload([
            'images' => [UploadedFile::fake()->image('new.jpg', 600, 400)],
        ]), jsonHeaders())
        ->assertOk();

    expect($listing->fresh()->getMedia('images'))->toHaveCount(2);
});

it('deletes only the selected current images and keeps the rest', function () {
    Storage::fake('public');
    $this->mock(FileManipulator::class, function ($mock) {
        $mock->shouldReceive('createDerivedFiles')->andReturnNull();
    });

    $listing = makeEditListing($this->seller, $this->category);
    $m1 = fakeImageMedia($listing, 1);
    $m2 = fakeImageMedia($listing, 2);
    $m3 = fakeImageMedia($listing, 3);

    $this->actingAs($this->seller)
        ->put(route('listings.update', $listing), editPayload([
            'removed_image_ids' => [$m2->id],
        ]), jsonHeaders())
        ->assertOk();

    $ids = $listing->fresh()->getMedia('images')->pluck('id')->all();
    expect($ids)->toContain($m1->id);
    expect($ids)->toContain($m3->id);
    expect($ids)->not->toContain($m2->id);
});

it('keeps current images first then appends new ones (simple ordering)', function () {
    Storage::fake('public');
    $this->mock(FileManipulator::class, function ($mock) {
        $mock->shouldReceive('createDerivedFiles')->andReturnNull();
    });

    $listing = makeEditListing($this->seller, $this->category);
    $m1 = fakeImageMedia($listing, 1);
    $m2 = fakeImageMedia($listing, 2);
    $m3 = fakeImageMedia($listing, 3);

    // Remove the middle current image AND add a new one.
    $this->actingAs($this->seller)
        ->put(route('listings.update', $listing), editPayload([
            'removed_image_ids' => [$m2->id],
            'images'            => [UploadedFile::fake()->image('appended.jpg', 600, 400)],
        ]), jsonHeaders())
        ->assertOk();

    $media = $listing->fresh()->getMedia('images');
    expect($media)->toHaveCount(3);

    $ids = $media->pluck('id')->all();
    // Remaining current block first (m1, m3 in original order), new one last.
    expect($ids[0])->toBe($m1->id);
    expect($ids[1])->toBe($m3->id);
    expect($ids[2])->not->toBeIn([$m1->id, $m2->id, $m3->id]);
});

it('only deletes media that belongs to the listing being edited (no cross-listing IDOR)', function () {
    Storage::fake('public');
    $this->mock(FileManipulator::class, function ($mock) {
        $mock->shouldReceive('createDerivedFiles')->andReturnNull();
    });

    $listing       = makeEditListing($this->seller, $this->category);
    $otherListing  = makeEditListing($this->seller, $this->category);
    $mine          = fakeImageMedia($listing, 1);
    $foreign       = fakeImageMedia($otherListing, 1);

    // Try to delete the OTHER listing's media via this listing's update.
    $this->actingAs($this->seller)
        ->put(route('listings.update', $listing), editPayload([
            'removed_image_ids' => [$foreign->id],
        ]), jsonHeaders())
        ->assertOk();

    // The foreign media survives; mine is untouched.
    expect($otherListing->fresh()->getMedia('images')->pluck('id')->all())->toContain($foreign->id);
    expect($listing->fresh()->getMedia('images')->pluck('id')->all())->toContain($mine->id);
});

// ═══════════════════════════════════════════════════════════════════════════
// 8) Server-side image validation (mime + size)
// ═══════════════════════════════════════════════════════════════════════════

it('rejects a non-image upload server-side', function () {
    $listing = makeEditListing($this->seller, $this->category);

    $this->actingAs($this->seller)
        ->put(route('listings.update', $listing), editPayload([
            'images' => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
        ]), jsonHeaders())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['images.0']);

    // Listing untouched (still its original status/title).
    expect($listing->fresh()->status)->toBe(Listing::STATUS_PUBLISHED);
    expect($listing->fresh()->title)->toBe($listing->title);
});

it('rejects an oversized image upload server-side', function () {
    $listing = makeEditListing($this->seller, $this->category);

    $this->actingAs($this->seller)
        ->put(route('listings.update', $listing), editPayload([
            'images' => [UploadedFile::fake()->image('huge.jpg')->size(6000)], // 6 MB > 5 MB
        ]), jsonHeaders())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['images.0']);
});

// ═══════════════════════════════════════════════════════════════════════════
// 9) Ownership — 404 for non-owners on both edit and update
// ═══════════════════════════════════════════════════════════════════════════

it('returns 404 when a non-owner tries to open the edit page', function () {
    $listing  = makeEditListing($this->seller, $this->category);
    $intruder = User::factory()->create(['is_phone_verified' => true]);

    $this->actingAs($intruder)
        ->get(route('listings.edit', $listing))
        ->assertNotFound();
});

it('returns 404 when a non-owner tries to update the listing', function () {
    $listing  = makeEditListing($this->seller, $this->category);
    $intruder = User::factory()->create(['is_phone_verified' => true]);

    $this->actingAs($intruder)
        ->putJson(route('listings.update', $listing), editPayload())
        ->assertNotFound();

    expect($listing->fresh()->title)->toBe($listing->title);
});

// ═══════════════════════════════════════════════════════════════════════════
// 10) store() remains unaffected — still creates pending + grants +3 points
// ═══════════════════════════════════════════════════════════════════════════

it('rejects a non-image upload on store server-side', function () {
    $before = $this->seller->fresh()->points;

    $this->actingAs($this->seller)
        ->post(route('listings.store'), [
            'title'        => 'Should not be created',
            'description'  => 'A sufficiently long description for store.',
            'category_id'  => $this->category->id,
            'price'        => 5000,
            'condition'    => 'new',
            'price_type'   => 'fixed',
            'phone'        => '01000000000',
            'feature_days' => 0,
            'images'       => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
        ], jsonHeaders())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['images.0']);

    expect(Listing::where('title', 'Should not be created')->exists())->toBeFalse();
    expect($this->seller->fresh()->points)->toBe($before);
});

it('keeps the create flow working: store creates a pending listing and grants 3 points', function () {
    Storage::fake('public');

    $before = $this->seller->fresh()->points;

    $this->actingAs($this->seller)
        ->post(route('listings.store'), [
            'title'        => 'Created via store',
            'description'  => 'A sufficiently long description for store.',
            'category_id'  => $this->category->id,
            'price'        => 5000,
            'condition'    => 'new',
            'price_type'   => 'fixed',
            'phone'        => '01000000000',
            'feature_days' => 0,
        ], jsonHeaders())
        ->assertOk();

    $listing = Listing::where('title', 'Created via store')->first();
    expect($listing)->not->toBeNull();
    expect($listing->status)->toBe(Listing::STATUS_PENDING);
    expect($listing->slug)->not->toBeEmpty();

    // Create DOES grant +3 (the contrast with the no-points-on-edit test).
    expect($this->seller->fresh()->points)->toBe($before + 3);
});
