<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Test 8: Advanced Search
 *   - Keyword search returns relevant listings.
 *   - Price range filter (min_price / max_price) excludes out-of-range results.
 *   - Category filter returns only listings in the requested category.
 *   - Governorate (province) filter returns only listings in the requested province.
 *   - Pending / rejected listings never appear in search results.
 *   - Multiple filters can be combined.
 *
 * Notes:
 *   SCOUT_DRIVER=collection (set in phpunit.xml) is used for all tests.
 *   The collection driver applies .query() Eloquent constraints, making
 *   price / category / province filters fully testable without Meilisearch.
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ═══════════════════════════════════════════════════════════════════════════
// Shared setup
// ═══════════════════════════════════════════════════════════════════════════

beforeEach(function () {
    $this->seller = User::factory()->create(['is_phone_verified' => true]);

    // Categories
    $this->catElectronics = Category::create([
        'name_ar' => 'إلكترونيات',
        'name_en' => 'Electronics',
        'slug' => 'electronics',
        'is_active' => true,
    ]);

    $this->catCars = Category::create([
        'name_ar' => 'سيارات',
        'name_en' => 'Cars',
        'slug' => 'cars',
        'is_active' => true,
    ]);

    // Governorates (provinces)
    $this->provinceCairo = Location::create([
        'name_ar' => 'القاهرة',
        'name_en' => 'Cairo',
        'slug' => 'cairo',
        'level' => 0,
        'is_active' => true,
    ]);

    $this->provinceAlex = Location::create([
        'name_ar' => 'الإسكندرية',
        'name_en' => 'Alexandria',
        'slug' => 'alexandria',
        'level' => 0,
        'is_active' => true,
    ]);
});

// Helper: create a published listing quickly
function makePublishedListing(array $attrs): Listing
{
    return Listing::create(array_merge([
        'slug' => 'listing-'.uniqid(),
        'description' => 'وصف تجريبي',
        'price' => 10_000,
        'status' => Listing::STATUS_PUBLISHED,
    ], $attrs));
}

// ═══════════════════════════════════════════════════════════════════════════
// Test 8 – Advanced Search
// ═══════════════════════════════════════════════════════════════════════════

describe('Advanced Search', function () {

    // ── Keyword Search ──────────────────────────────────────────────────────

    it('search with no query returns all published listings', function () {
        makePublishedListing([
            'title' => 'سامسونج جالاكسي S24',
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        makePublishedListing([
            'title' => 'تويوتا كورولا 2023',
            'category_id' => $this->catCars->id,
            'user_id' => $this->seller->id,
        ]);

        $response = $this->get(route('listings.search'));

        $response->assertOk();
        $response->assertViewHas('listings', fn ($listings) => $listings->total() === 2);
    });

    it('search by keyword returns matching listings', function () {
        makePublishedListing([
            'title' => 'آيفون 15 برو ماكس',
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        makePublishedListing([
            'title' => 'تويوتا كورولا 2023',
            'category_id' => $this->catCars->id,
            'user_id' => $this->seller->id,
        ]);

        $response = $this->get(route('listings.search', ['q' => 'آيفون']));

        $response->assertOk();
        $response->assertViewHas('listings', function ($listings) {
            return $listings->contains(fn ($l) => str_contains($l->title, 'آيفون'))
                && ! $listings->contains(fn ($l) => str_contains($l->title, 'تويوتا'));
        });
    });

    it('does not return pending listings in search results', function () {
        makePublishedListing([
            'title' => 'إعلان منشور',
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        Listing::create([
            'title' => 'إعلان قيد المراجعة',
            'slug' => 'pending-ad',
            'description' => 'وصف.',
            'price' => 5_000,
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
            'status' => Listing::STATUS_PENDING,
        ]);

        $response = $this->get(route('listings.search'));

        $response->assertOk();
        $response->assertViewHas('listings', function ($listings) {
            return $listings->every(fn ($l) => $l->status === Listing::STATUS_PUBLISHED);
        });
        $response->assertDontSee('إعلان قيد المراجعة');
    });

    // ── Price Range Filter ──────────────────────────────────────────────────

    it('min_price filter excludes listings below the threshold', function () {
        makePublishedListing([
            'title' => 'منتج رخيص',
            'price' => 500,
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        makePublishedListing([
            'title' => 'منتج متوسط',
            'price' => 5_000,
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        makePublishedListing([
            'title' => 'منتج غالي',
            'price' => 50_000,
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        $response = $this->get(route('listings.search', ['min_price' => 3_000]));

        $response->assertOk();
        $response->assertViewHas('listings', function ($listings) {
            return $listings->every(fn ($l) => $l->price >= 3000)
                && ! $listings->contains(fn ($l) => str_contains($l->title, 'منتج رخيص'));
        });
    });

    it('max_price filter excludes listings above the threshold', function () {
        makePublishedListing([
            'title' => 'لابتوب اقتصادي',
            'price' => 8_000,
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        makePublishedListing([
            'title' => 'لابتوب فاخر',
            'price' => 80_000,
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        $response = $this->get(route('listings.search', ['max_price' => 20_000]));

        $response->assertOk();
        $response->assertViewHas('listings', function ($listings) {
            return $listings->every(fn ($l) => $l->price <= 20000)
                && ! $listings->contains(fn ($l) => str_contains($l->title, 'لابتوب فاخر'));
        });
    });

    it('price range filter returns only listings within the band', function () {
        makePublishedListing([
            'title' => 'في النطاق',
            'price' => 15_000,
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        makePublishedListing([
            'title' => 'أرخص من النطاق',
            'price' => 1_000,
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        makePublishedListing([
            'title' => 'أغلى من النطاق',
            'price' => 100_000,
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        $response = $this->get(route('listings.search', [
            'min_price' => 10_000,
            'max_price' => 30_000,
        ]));

        $response->assertOk();
        $response->assertViewHas('listings', function ($listings) {
            return $listings->count() === 1
                && $listings->first()->title === 'في النطاق';
        });
    });

    // ── Category Filter ─────────────────────────────────────────────────────

    it('category_id filter returns only listings in that category', function () {
        makePublishedListing([
            'title' => 'سامسونج A54',
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        makePublishedListing([
            'title' => 'هوندا سيفيك 2022',
            'category_id' => $this->catCars->id,
            'user_id' => $this->seller->id,
        ]);

        $response = $this->get(route('listings.search', ['category_id' => $this->catElectronics->id]));

        $response->assertOk();
        $response->assertViewHas('listings', function ($listings) {
            return $listings->every(fn ($l) => $l->category_id === $this->catElectronics->id)
                && ! $listings->contains(fn ($l) => $l->category_id === $this->catCars->id);
        });
    });

    it('category filter returns zero results for a category with no listings', function () {
        makePublishedListing([
            'title' => 'إعلان إلكترونيات',
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        $response = $this->get(route('listings.search', ['category_id' => $this->catCars->id]));

        $response->assertOk();
        $response->assertViewHas('listings', fn ($listings) => $listings->isEmpty());
    });

    // ── Governorate (Province) Filter ───────────────────────────────────────

    it('province_id filter returns only listings from the selected governorate', function () {
        makePublishedListing([
            'title' => 'إعلان القاهرة',
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
            'province_id' => $this->provinceCairo->id,
        ]);

        makePublishedListing([
            'title' => 'إعلان الإسكندرية',
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
            'province_id' => $this->provinceAlex->id,
        ]);

        $response = $this->get(route('listings.search', ['province_id' => $this->provinceCairo->id]));

        $response->assertOk();
        $response->assertViewHas('listings', function ($listings) {
            return $listings->every(fn ($l) => $l->province_id === $this->provinceCairo->id)
                && ! $listings->contains(fn ($l) => $l->province_id === $this->provinceAlex->id);
        });
        $response->assertSee('إعلان القاهرة');
        $response->assertDontSee('إعلان الإسكندرية');
    });

    // ── Combined Filters ────────────────────────────────────────────────────

    it('combining category and price filters narrows results correctly', function () {
        makePublishedListing([
            'title' => 'إلكترونيات رخيصة',
            'price' => 2_000,
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        makePublishedListing([
            'title' => 'إلكترونيات غالية',
            'price' => 50_000,
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
        ]);

        makePublishedListing([
            'title' => 'سيارة ضمن النطاق',
            'price' => 10_000,
            'category_id' => $this->catCars->id,
            'user_id' => $this->seller->id,
        ]);

        $response = $this->get(route('listings.search', [
            'category_id' => $this->catElectronics->id,
            'min_price' => 1_000,
            'max_price' => 30_000,
        ]));

        $response->assertOk();
        $response->assertViewHas('listings', function ($listings) {
            return $listings->count() === 1
                && $listings->first()->title === 'إلكترونيات رخيصة';
        });
    });

    it('combining governorate and category filters returns only matching listings', function () {
        makePublishedListing([
            'title' => 'موبايل في القاهرة',
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
            'province_id' => $this->provinceCairo->id,
        ]);

        makePublishedListing([
            'title' => 'موبايل في الإسكندرية',
            'category_id' => $this->catElectronics->id,
            'user_id' => $this->seller->id,
            'province_id' => $this->provinceAlex->id,
        ]);

        makePublishedListing([
            'title' => 'سيارة في القاهرة',
            'category_id' => $this->catCars->id,
            'user_id' => $this->seller->id,
            'province_id' => $this->provinceCairo->id,
        ]);

        $response = $this->get(route('listings.search', [
            'category_id' => $this->catElectronics->id,
            'province_id' => $this->provinceCairo->id,
        ]));

        $response->assertOk();
        $response->assertViewHas('listings', function ($listings) {
            return $listings->count() === 1
                && $listings->first()->title === 'موبايل في القاهرة';
        });
    });

    // ── View Structure ──────────────────────────────────────────────────────

    it('search endpoint returns the correct view with required variables', function () {
        $response = $this->get(route('listings.search', ['q' => 'اختبار']));

        $response->assertOk()
            ->assertViewIs('frontend.search-results')
            ->assertViewHasAll(['listings', 'query', 'lat', 'lng', 'radius', 'sort']);
    });
});
