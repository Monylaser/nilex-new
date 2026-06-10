<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Test 11: Dynamic Category Forms
 *   - Selecting "سيارات" category shows car fields.
 *   - Selecting "عقارات" shows real estate fields.
 *   - Validation works correctly for each category type.
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ═══════════════════════════════════════════════════════════════════════════
// Shared Category Schemas
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Schema for سيارات (Cars):
 *   - car_brand   (text,   required)
 *   - model_year  (number, required)
 *   - mileage     (number, optional)
 *   - fuel_type   (select, required)
 */
function carSchema(): array
{
    return [
        [
            'name'     => 'car_brand',
            'label_ar' => 'ماركة السيارة',
            'type'     => 'text',
            'required' => true,
        ],
        [
            'name'     => 'model_year',
            'label_ar' => 'سنة الصنع',
            'type'     => 'number',
            'required' => true,
        ],
        [
            'name'     => 'mileage',
            'label_ar' => 'عدد الكيلومترات',
            'type'     => 'number',
            'required' => false,
        ],
        [
            'name'     => 'fuel_type',
            'label_ar' => 'نوع الوقود',
            'type'     => 'select',
            'required' => true,
            'options'  => [
                ['value' => 'petrol',  'label' => 'بنزين'],
                ['value' => 'diesel',  'label' => 'ديزل'],
                ['value' => 'electric', 'label' => 'كهرباء'],
            ],
        ],
    ];
}

/**
 * Schema for عقارات (Real Estate):
 *   - property_type  (select, required)
 *   - area_sqm       (number, required)
 *   - bedrooms       (number, required)
 *   - floor          (number, optional)
 */
function realEstateSchema(): array
{
    return [
        [
            'name'     => 'property_type',
            'label_ar' => 'نوع العقار',
            'type'     => 'select',
            'required' => true,
            'options'  => [
                ['value' => 'apartment', 'label' => 'شقة'],
                ['value' => 'villa',     'label' => 'فيلا'],
                ['value' => 'office',    'label' => 'مكتب'],
            ],
        ],
        [
            'name'     => 'area_sqm',
            'label_ar' => 'المساحة بالمتر المربع',
            'type'     => 'number',
            'required' => true,
        ],
        [
            'name'     => 'bedrooms',
            'label_ar' => 'عدد الغرف',
            'type'     => 'number',
            'required' => true,
        ],
        [
            'name'     => 'floor',
            'label_ar' => 'الدور',
            'type'     => 'number',
            'required' => false,
        ],
    ];
}

// ═══════════════════════════════════════════════════════════════════════════
// Test 11 – Dynamic Category Forms
// ═══════════════════════════════════════════════════════════════════════════

describe('Dynamic Category Forms', function () {

    beforeEach(function () {
        $this->user = User::factory()->create(['is_phone_verified' => true]);

        $this->carCategory = Category::create([
            'name_ar'              => 'سيارات',
            'name_en'              => 'Cars',
            'slug'                 => 'cars-dynamic',
            'is_active'            => true,
            'custom_fields_schema' => carSchema(),
        ]);

        $this->realEstateCategory = Category::create([
            'name_ar'              => 'عقارات',
            'name_en'              => 'Real Estate',
            'slug'                 => 'real-estate-dynamic',
            'is_active'            => true,
            'custom_fields_schema' => realEstateSchema(),
        ]);
    });

    // ── سيارات: schema structure ─────────────────────────────────────────────

    it('سيارات category schema contains car-specific fields', function () {
        $schema     = $this->carCategory->custom_fields_schema;
        $fieldNames = collect($schema)->pluck('name')->all();

        expect($fieldNames)
            ->toContain('car_brand')
            ->toContain('model_year')
            ->toContain('fuel_type');
    });

    it('سيارات schema marks car_brand and model_year as required', function () {
        $schema    = collect($this->carCategory->custom_fields_schema)->keyBy('name');

        expect($schema->get('car_brand')['required'])->toBeTrue()
            ->and($schema->get('model_year')['required'])->toBeTrue()
            ->and($schema->get('mileage')['required'])->toBeFalse();
    });

    it('سيارات fuel_type field has the correct select options', function () {
        $schema  = collect($this->carCategory->custom_fields_schema)->keyBy('name');
        $options = collect($schema->get('fuel_type')['options'])->pluck('value')->all();

        expect($options)->toContain('petrol')
            ->and($options)->toContain('diesel')
            ->and($options)->toContain('electric');
    });

    // ── عقارات: schema structure ─────────────────────────────────────────────

    it('عقارات category schema contains real estate specific fields', function () {
        $schema     = $this->realEstateCategory->custom_fields_schema;
        $fieldNames = collect($schema)->pluck('name')->all();

        expect($fieldNames)
            ->toContain('property_type')
            ->toContain('area_sqm')
            ->toContain('bedrooms');
    });

    it('عقارات schema marks property_type, area_sqm, and bedrooms as required', function () {
        $schema = collect($this->realEstateCategory->custom_fields_schema)->keyBy('name');

        expect($schema->get('property_type')['required'])->toBeTrue()
            ->and($schema->get('area_sqm')['required'])->toBeTrue()
            ->and($schema->get('bedrooms')['required'])->toBeTrue()
            ->and($schema->get('floor')['required'])->toBeFalse();
    });

    it('عقارات property_type field has apartment, villa and office options', function () {
        $schema  = collect($this->realEstateCategory->custom_fields_schema)->keyBy('name');
        $options = collect($schema->get('property_type')['options'])->pluck('value')->all();

        expect($options)->toContain('apartment')
            ->and($options)->toContain('villa')
            ->and($options)->toContain('office');
    });

    // ── سيارات: validation via listing store ─────────────────────────────────

    it('listing creation with all valid car fields succeeds', function () {
        $this->actingAs($this->user)
            ->post(route('listings.store'), [
                'title'       => 'تويوتا كورولا 2023',
                'description' => 'سيارة بحالة ممتازة، مالك واحد.',
                'category_id' => $this->carCategory->id,
                'price'       => 250_000,
                'custom_fields_values' => [
                    'car_brand'  => 'تويوتا',
                    'model_year' => 2023,
                    'mileage'    => 30_000,
                    'fuel_type'  => 'petrol',
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('listings', [
            'title'       => 'تويوتا كورولا 2023',
            'category_id' => $this->carCategory->id,
            'user_id'     => $this->user->id,
        ]);
    });

    it('listing creation fails when required car fields are missing', function () {
        $this->actingAs($this->user)
            ->post(route('listings.store'), [
                'title'       => 'سيارة بدون تفاصيل',
                'description' => 'وصف مختصر.',
                'category_id' => $this->carCategory->id,
                'price'       => 100_000,
                // car_brand, model_year, fuel_type are required — all missing
            ])
            ->assertSessionHasErrors([
                'custom_fields_values.car_brand',
                'custom_fields_values.model_year',
                'custom_fields_values.fuel_type',
            ]);

        $this->assertDatabaseEmpty('listings');
    });

    it('optional car field mileage can be omitted without error', function () {
        $this->actingAs($this->user)
            ->post(route('listings.store'), [
                'title'       => 'هوندا سيفيك 2022',
                'description' => 'وصف السيارة.',
                'category_id' => $this->carCategory->id,
                'price'       => 180_000,
                'custom_fields_values' => [
                    'car_brand'  => 'هوندا',
                    'model_year' => 2022,
                    'fuel_type'  => 'petrol',
                    // mileage intentionally absent (optional)
                ],
            ])
            ->assertRedirect(route('dashboard'));
    });

    // ── عقارات: validation via listing store ─────────────────────────────────

    it('listing creation with all valid real estate fields succeeds', function () {
        $this->actingAs($this->user)
            ->post(route('listings.store'), [
                'title'       => 'شقة للبيع في القاهرة الجديدة',
                'description' => 'شقة ممتازة في موقع متميز.',
                'category_id' => $this->realEstateCategory->id,
                'price'       => 2_500_000,
                'custom_fields_values' => [
                    'property_type' => 'apartment',
                    'area_sqm'      => 150,
                    'bedrooms'      => 3,
                    'floor'         => 7,
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('listings', [
            'title'       => 'شقة للبيع في القاهرة الجديدة',
            'category_id' => $this->realEstateCategory->id,
        ]);
    });

    it('listing creation fails when required real estate fields are missing', function () {
        $this->actingAs($this->user)
            ->post(route('listings.store'), [
                'title'       => 'عقار بدون تفاصيل',
                'description' => 'وصف.',
                'category_id' => $this->realEstateCategory->id,
                'price'       => 500_000,
                // property_type, area_sqm, bedrooms all missing
            ])
            ->assertSessionHasErrors([
                'custom_fields_values.property_type',
                'custom_fields_values.area_sqm',
                'custom_fields_values.bedrooms',
            ]);

        $this->assertDatabaseEmpty('listings');
    });

    it('optional real estate field floor can be omitted without error', function () {
        $this->actingAs($this->user)
            ->post(route('listings.store'), [
                'title'       => 'فيلا للبيع',
                'description' => 'فيلا بمواصفات ممتازة.',
                'category_id' => $this->realEstateCategory->id,
                'price'       => 5_000_000,
                'custom_fields_values' => [
                    'property_type' => 'villa',
                    'area_sqm'      => 400,
                    'bedrooms'      => 5,
                    // floor intentionally absent (optional)
                ],
            ])
            ->assertRedirect(route('dashboard'));
    });

    // ── custom_fields_values persisted correctly ──────────────────────────────

    it('custom_fields_values are persisted correctly in the database', function () {
        $this->actingAs($this->user)
            ->post(route('listings.store'), [
                'title'       => 'نيسان التيما 2024',
                'description' => 'سيارة جديدة بالكرتونة.',
                'category_id' => $this->carCategory->id,
                'price'       => 400_000,
                'custom_fields_values' => [
                    'car_brand'  => 'نيسان',
                    'model_year' => 2024,
                    'mileage'    => 0,
                    'fuel_type'  => 'petrol',
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $listing = Listing::where('title', 'نيسان التيما 2024')->first();

        expect($listing)->not->toBeNull()
            ->and($listing->custom_fields_values['car_brand'])->toBe('نيسان')
            ->and($listing->custom_fields_values['model_year'])->toBe(2024)
            ->and($listing->custom_fields_values['fuel_type'])->toBe('petrol');
    });

    // ── category without a schema (no custom fields required) ────────────────

    it('listing creation succeeds for a category with no custom fields schema', function () {
        $simpleCategory = Category::create([
            'name_ar'   => 'خدمات',
            'name_en'   => 'Services',
            'slug'      => 'services-test',
            'is_active' => true,
            // custom_fields_schema is null — no extra validation
        ]);

        $this->actingAs($this->user)
            ->post(route('listings.store'), [
                'title'       => 'خدمة تصميم جرافيك',
                'description' => 'تصميم احترافي بأسعار مناسبة.',
                'category_id' => $simpleCategory->id,
                'price'       => 500,
            ])
            ->assertRedirect(route('dashboard'));
    });
});
