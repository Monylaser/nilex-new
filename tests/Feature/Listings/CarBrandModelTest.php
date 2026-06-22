<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Car Brand/Model/Fuel/Transmission system for the listing wizard.
 *   - Brand & Model are stored as FK columns (car_brand_id / car_model_id).
 *   - Fuel & Transmission are stored in custom_fields_values (fuel, transmission).
 *   - Selecting the "أخرى/Other" brand requires a manual brand name.
 */

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['is_phone_verified' => true]);

    $this->carsCategory = Category::create([
        'name_ar'   => 'سيارات',
        'name_en'   => 'Cars',
        'slug'      => 'cars',
        'is_active' => true,
    ]);

    $this->toyota = CarBrand::create([
        'name_ar' => 'تويوتا', 'name_en' => 'Toyota', 'slug' => 'toyota', 'is_active' => true,
    ]);
    $this->corolla = CarModel::create([
        'car_brand_id' => $this->toyota->id, 'name_ar' => 'كورولا', 'name_en' => 'Corolla', 'is_active' => true,
    ]);

    $this->honda = CarBrand::create([
        'name_ar' => 'هوندا', 'name_en' => 'Honda', 'slug' => 'honda', 'is_active' => true,
    ]);
    $this->civic = CarModel::create([
        'car_brand_id' => $this->honda->id, 'name_ar' => 'سيفيك', 'name_en' => 'Civic', 'is_active' => true,
    ]);

    $this->otherBrand = CarBrand::create([
        'name_ar' => 'أخرى', 'name_en' => 'Other', 'slug' => 'other', 'is_active' => true,
    ]);
    $this->otherModel = CarModel::create([
        'car_brand_id' => $this->otherBrand->id, 'name_ar' => 'أخرى', 'name_en' => 'Other', 'is_active' => true,
    ]);
});

it('creates a car listing storing brand & model in FK columns', function () {
    $this->actingAs($this->user)
        ->post(route('listings.store'), [
            'title'        => 'تويوتا كورولا 2023',
            'description'  => 'سيارة بحالة ممتازة، مالك واحد.',
            'category_id'  => $this->carsCategory->id,
            'price'        => 250_000,
            'condition'    => 'used',
            'price_type'   => 'fixed',
            'phone'        => '01000000000',
            'car_brand_id' => $this->toyota->id,
            'car_model_id' => $this->corolla->id,
            'custom_fields_values' => [
                'fuel'         => 'petrol',
                'transmission' => 'automatic',
            ],
        ])
        ->assertRedirect(route('dashboard'));

    $listing = Listing::where('title', 'تويوتا كورولا 2023')->first();

    expect($listing)->not->toBeNull()
        ->and($listing->car_brand_id)->toBe($this->toyota->id)
        ->and($listing->car_model_id)->toBe($this->corolla->id)
        ->and($listing->custom_fields_values['fuel'])->toBe('petrol')
        ->and($listing->custom_fields_values['transmission'])->toBe('automatic');
});

it('fails when required car fields are missing', function () {
    $this->actingAs($this->user)
        ->post(route('listings.store'), [
            'title'       => 'سيارة بدون تفاصيل',
            'description' => 'وصف مختصر.',
            'category_id' => $this->carsCategory->id,
            'price'       => 100_000,
            'condition'   => 'used',
            'price_type'  => 'fixed',
            'phone'       => '01000000000',
        ])
        ->assertSessionHasErrors([
            'car_brand_id',
            'car_model_id',
            'custom_fields_values.fuel',
            'custom_fields_values.transmission',
        ]);

    $this->assertDatabaseEmpty('listings');
});

it('fails when the chosen model does not belong to the chosen brand', function () {
    $this->actingAs($this->user)
        ->post(route('listings.store'), [
            'title'        => 'موديل لا يتبع الماركة',
            'description'  => 'وصف الإعلان التجريبي.',
            'category_id'  => $this->carsCategory->id,
            'price'        => 200_000,
            'condition'    => 'used',
            'price_type'   => 'fixed',
            'phone'        => '01000000000',
            'car_brand_id' => $this->toyota->id,
            'car_model_id' => $this->civic->id, // Civic belongs to Honda, not Toyota
            'custom_fields_values' => [
                'fuel'         => 'petrol',
                'transmission' => 'manual',
            ],
        ])
        ->assertSessionHasErrors(['car_model_id']);

    $this->assertDatabaseEmpty('listings');
});

it('requires a manual brand name when the Other brand is selected', function () {
    $this->actingAs($this->user)
        ->post(route('listings.store'), [
            'title'        => 'سيارة ماركة غير مدرجة',
            'description'  => 'وصف الإعلان التجريبي.',
            'category_id'  => $this->carsCategory->id,
            'price'        => 150_000,
            'condition'    => 'used',
            'price_type'   => 'fixed',
            'phone'        => '01000000000',
            'car_brand_id' => $this->otherBrand->id,
            'car_model_id' => $this->otherModel->id,
            'custom_fields_values' => [
                'fuel'         => 'diesel',
                'transmission' => 'manual',
                // car_brand_other intentionally missing
            ],
        ])
        ->assertSessionHasErrors(['custom_fields_values.car_brand_other']);

    $this->assertDatabaseEmpty('listings');
});

it('stores the manual brand name when the Other brand is selected', function () {
    $this->actingAs($this->user)
        ->post(route('listings.store'), [
            'title'        => 'MG 5 موديل 2024',
            'description'  => 'سيارة جديدة بالكرتونة.',
            'category_id'  => $this->carsCategory->id,
            'price'        => 600_000,
            'condition'    => 'new',
            'price_type'   => 'fixed',
            'phone'        => '01000000000',
            'car_brand_id' => $this->otherBrand->id,
            'car_model_id' => $this->otherModel->id,
            'custom_fields_values' => [
                'fuel'            => 'petrol',
                'transmission'    => 'automatic',
                'car_brand_other' => 'MG',
            ],
        ])
        ->assertRedirect(route('dashboard'));

    $listing = Listing::where('title', 'MG 5 موديل 2024')->first();

    expect($listing)->not->toBeNull()
        ->and($listing->car_brand_id)->toBe($this->otherBrand->id)
        ->and($listing->custom_fields_values['car_brand_other'])->toBe('MG');
});

it('does not require car fields for non-car categories', function () {
    $services = Category::create([
        'name_ar' => 'خدمات', 'name_en' => 'Services', 'slug' => 'services', 'is_active' => true,
    ]);

    $this->actingAs($this->user)
        ->post(route('listings.store'), [
            'title'       => 'خدمة تصميم',
            'description' => 'تصميم احترافي بأسعار مناسبة.',
            'category_id' => $services->id,
            'price'       => 500,
            'condition'   => 'new',
            'price_type'  => 'fixed',
            'phone'       => '01000000000',
        ])
        ->assertRedirect(route('dashboard'));
});
