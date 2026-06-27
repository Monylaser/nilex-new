<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Real-estate listing wizard field changes (HomeController::store / update / edit):
 *
 *   1) Condition (new/used) is REMOVED for the real-estate category only:
 *      - store(): a real-estate listing WITHOUT `condition` is saved (condition null).
 *      - store(): a non-real-estate listing WITHOUT `condition` is rejected (422).
 *      - update(): a real-estate listing WITHOUT `condition` is saved (condition null).
 *      - update(): a non-real-estate listing WITHOUT `condition` is rejected (422).
 *
 *   2) Floor (الدور) supports a free-text "other" value:
 *      - The value stored in custom_fields_values['floor'] is the free text itself
 *        (no 'other' sentinel + separate key — server has no floor validation, it is
 *        a pure pass-through), in both store() and update().
 *      - edit() exposes the stored free-text floor back in the DTO so the wizard can
 *        reconstruct the "other" mode client-side.
 *
 * The wizard view is shared between create and edit; these tests prove the server
 * contract for BOTH flows. (The Alpine "other → text input" reveal is client-side.)
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create(['is_phone_verified' => true, 'points' => 100]);

    $this->realEstate = Category::create([
        'name_ar'   => 'عقارات',
        'name_en'   => 'Real Estate',
        'slug'      => 'real-estate',
        'is_active' => true,
    ]);

    // Any non-real-estate category (condition stays required here).
    $this->generic = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-re-' . uniqid(),
        'is_active' => true,
    ]);
});

function reJsonHeaders(): array
{
    return ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'];
}

function makeReListing(User $user, Category $category, array $overrides = []): Listing
{
    static $c = 0;
    $c++;

    return Listing::create(array_merge([
        'title'       => 'RE Listing ' . $c,
        'slug'        => 're-listing-' . $c . '-' . uniqid(),
        'description' => 'A valid original description long enough.',
        'price'       => 250000,
        'category_id' => $category->id,
        'user_id'     => $user->id,
        'status'      => Listing::STATUS_PUBLISHED,
        'condition'   => 'used',
        'price_type'  => 'fixed',
        'phone'       => '01000000000',
    ], $overrides));
}

// ═══════════════════════════════════════════════════════════════════════════
// 1) Condition removed for real estate — store()
// ═══════════════════════════════════════════════════════════════════════════

it('saves a real-estate listing WITHOUT condition (store)', function () {
    Storage::fake('public');

    $this->actingAs($this->seller)
        ->post(route('listings.store'), [
            'title'                => 'شقة للبيع بالتجمع',
            'description'          => 'وصف كافٍ وطويل بما يكفي للإعلان.',
            'category_id'          => $this->realEstate->id,
            'price'                => 1500000,
            // NO condition key sent (the field is hidden for real estate)
            'price_type'           => 'fixed',
            'phone'                => '01000000000',
            'feature_days'         => 0,
            'custom_fields_values' => [
                'property_type' => 'apartment',
                'listing_type'  => 'sale',
            ],
        ], reJsonHeaders())
        ->assertOk();

    $listing = Listing::where('title', 'شقة للبيع بالتجمع')->first();
    expect($listing)->not->toBeNull();
    expect($listing->condition)->toBeNull();
    expect($listing->status)->toBe(Listing::STATUS_PENDING);
});

it('rejects a non-real-estate listing WITHOUT condition (store)', function () {
    Storage::fake('public');

    $this->actingAs($this->seller)
        ->post(route('listings.store'), [
            'title'        => 'منتج بدون حالة',
            'description'  => 'وصف كافٍ وطويل بما يكفي للإعلان.',
            'category_id'  => $this->generic->id,
            'price'        => 5000,
            // NO condition — required for non-real-estate
            'price_type'   => 'fixed',
            'phone'        => '01000000000',
            'feature_days' => 0,
        ], reJsonHeaders())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['condition']);
});

// ═══════════════════════════════════════════════════════════════════════════
// 2) Condition removed for real estate — update()
// ═══════════════════════════════════════════════════════════════════════════

it('updates a real-estate listing WITHOUT condition, clearing it to null (update)', function () {
    $listing = makeReListing($this->seller, $this->realEstate, [
        'condition'            => 'used',
        'custom_fields_values' => ['property_type' => 'apartment', 'listing_type' => 'sale'],
    ]);

    $this->actingAs($this->seller)
        ->putJson(route('listings.update', $listing), [
            'title'                => 'فيلا للإيجار',
            'description'          => 'وصف محدّث وطويل بما يكفي.',
            'price'                => 90000,
            // NO condition
            'price_type'           => 'fixed',
            'phone'                => '01000000000',
            'custom_fields_values' => [
                'property_type' => 'villa',
                'listing_type'  => 'rent',
            ],
        ])
        ->assertOk();

    $fresh = $listing->fresh();
    expect($fresh->condition)->toBeNull();
    expect($fresh->status)->toBe(Listing::STATUS_PENDING);
    expect($fresh->title)->toBe('فيلا للإيجار');
});

it('rejects a non-real-estate listing update WITHOUT condition (update)', function () {
    $listing = makeReListing($this->seller, $this->generic);

    $this->actingAs($this->seller)
        ->putJson(route('listings.update', $listing), [
            'title'       => 'تحديث بدون حالة',
            'description' => 'وصف محدّث وطويل بما يكفي.',
            'price'       => 7000,
            // NO condition — still required here
            'price_type'  => 'fixed',
            'phone'       => '01000000000',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['condition']);

    // Untouched.
    expect($listing->fresh()->title)->toBe($listing->title);
});

// ═══════════════════════════════════════════════════════════════════════════
// 3) Floor free-text ("other") — store()
// ═══════════════════════════════════════════════════════════════════════════

it('stores a free-text floor value verbatim in custom_fields_values (store)', function () {
    Storage::fake('public');

    $this->actingAs($this->seller)
        ->post(route('listings.store'), [
            'title'                => 'شقة بدور غير اعتيادي',
            'description'          => 'وصف كافٍ وطويل بما يكفي للإعلان.',
            'category_id'          => $this->realEstate->id,
            'price'                => 800000,
            'price_type'           => 'fixed',
            'phone'                => '01000000000',
            'feature_days'         => 0,
            'custom_fields_values' => [
                'property_type' => 'apartment',
                'listing_type'  => 'sale',
                // free text (not one of the predefined floor codes)
                'floor'         => 'بدروم تحت الأرض',
            ],
        ], reJsonHeaders())
        ->assertOk();

    $listing = Listing::where('title', 'شقة بدور غير اعتيادي')->first();
    expect($listing->custom_fields_values['floor'])->toBe('بدروم تحت الأرض');
});

it('still stores a predefined floor code unchanged (store)', function () {
    Storage::fake('public');

    $this->actingAs($this->seller)
        ->post(route('listings.store'), [
            'title'                => 'شقة بالدور الأرضي',
            'description'          => 'وصف كافٍ وطويل بما يكفي للإعلان.',
            'category_id'          => $this->realEstate->id,
            'price'                => 800000,
            'price_type'           => 'fixed',
            'phone'                => '01000000000',
            'feature_days'         => 0,
            'custom_fields_values' => [
                'property_type' => 'apartment',
                'listing_type'  => 'sale',
                'floor'         => 'ground',
            ],
        ], reJsonHeaders())
        ->assertOk();

    $listing = Listing::where('title', 'شقة بالدور الأرضي')->first();
    expect($listing->custom_fields_values['floor'])->toBe('ground');
});

// ═══════════════════════════════════════════════════════════════════════════
// 4) Floor free-text ("other") — update() + edit() round-trip
// ═══════════════════════════════════════════════════════════════════════════

it('persists a free-text floor value verbatim on update', function () {
    $listing = makeReListing($this->seller, $this->realEstate, [
        'condition'            => null,
        'custom_fields_values' => ['property_type' => 'apartment', 'listing_type' => 'sale', 'floor' => 'ground'],
    ]);

    $this->actingAs($this->seller)
        ->putJson(route('listings.update', $listing), [
            'title'                => 'تحديث الدور لنص حر',
            'description'          => 'وصف محدّث وطويل بما يكفي.',
            'price'                => 95000,
            'price_type'           => 'fixed',
            'phone'                => '01000000000',
            'custom_fields_values' => [
                'property_type' => 'apartment',
                'listing_type'  => 'sale',
                'floor'         => 'ميزانين',
            ],
        ])
        ->assertOk();

    expect($listing->fresh()->custom_fields_values['floor'])->toBe('ميزانين');
});

it('exposes the stored free-text floor in the edit DTO for client reconstruction', function () {
    $listing = makeReListing($this->seller, $this->realEstate, [
        'condition'            => null,
        'custom_fields_values' => ['property_type' => 'apartment', 'listing_type' => 'sale', 'floor' => 'الدور العاشر'],
    ]);

    $response = $this->actingAs($this->seller)->get(route('listings.edit', $listing));

    $response->assertOk();
    $dto = $response->viewData('editData');
    // custom_fields is exposed as the raw stored array so Alpine can re-derive the
    // floor dropdown ("other" + free text) via syncFloorFromFormData().
    expect($dto['custom_fields']['floor'])->toBe('الدور العاشر');
});
