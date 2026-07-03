<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Test 4: Listing Approval Workflow
 *   - New listing status = 'pending' by default.
 *   - Admin approves → status becomes 'published'.
 *   - Published listing appears in public frontend.
 *
 * Test 5: Boost System
 *   - User purchases boost (featureWithPoints) → is_featured = true.
 *   - featured_until date is set correctly.
 *   - Boosted listings appear in the featured section on the homepage.
 *
 * Test 6: Phone Reveal Endpoint
 *   - Guest → 401 Unauthenticated.
 *   - Authenticated user → 200 with phone number.
 *   - phone reveal creates ListingPhoneClick records (not whatsapp_clicks).
 *
 * Test 7: WhatsApp Click Tracking (TD-01 Phase 1)
 *   - First click creates listing_whatsapp_clicks row and increments whatsapp_clicks.
 *   - Deduped click within 1h does not create a second row or increment again.
 *   - revealPhone and view tracking remain unaffected.
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ═══════════════════════════════════════════════════════════════════════════
// Test 4 – Listing Approval Workflow
// ═══════════════════════════════════════════════════════════════════════════

describe('Listing Approval Workflow', function () {

    beforeEach(function () {
        $this->category = Category::create([
            'name_ar'   => 'إلكترونيات',
            'name_en'   => 'Electronics',
            'slug'      => 'electronics',
            'is_active' => true,
        ]);

        $this->seller = User::factory()->create(['is_phone_verified' => true]);
        $this->admin  = User::factory()->create(['is_phone_verified' => true]);
    });

    it('new listing has pending status by default', function () {
        $listing = Listing::create([
            'title'       => 'لابتوب ديل XPS',
            'slug'        => 'laptop-dell-xps',
            'description' => 'حالة ممتازة.',
            'price'       => 20_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->seller->id,
            'status'      => Listing::STATUS_PENDING,
        ]);

        expect($listing->status)->toBe(Listing::STATUS_PENDING);
    });

    it('admin approves a listing and status becomes published', function () {
        $listing = Listing::create([
            'title'       => 'iPhone 14 Pro',
            'slug'        => 'iphone-14-pro',
            'description' => 'جهاز جديد بالكرتونة.',
            'price'       => 35_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->seller->id,
            'status'      => Listing::STATUS_PENDING,
        ]);

        $listing->approve($this->admin->id);

        expect($listing->fresh()->status)->toBe(Listing::STATUS_PUBLISHED)
            ->and($listing->fresh()->moderated_by)->toBe($this->admin->id)
            ->and($listing->fresh()->moderated_at)->not->toBeNull();
    });

    it('pending listing does not appear in the active scope', function () {
        Listing::create([
            'title'       => 'إعلان معلق',
            'slug'        => 'pending-listing',
            'description' => 'في انتظار المراجعة.',
            'price'       => 5_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->seller->id,
            'status'      => Listing::STATUS_PENDING,
        ]);

        expect(Listing::active()->count())->toBe(0);
    });

    it('pending listing does not appear on the public homepage', function () {
        Listing::create([
            'title'       => 'إعلان في الانتظار',
            'slug'        => 'waiting-listing',
            'description' => 'لم يُعتمد بعد.',
            'price'       => 8_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->seller->id,
            'status'      => Listing::STATUS_PENDING,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('إعلان في الانتظار');
    });

    it('approved listing appears on the public homepage', function () {
        $listing = Listing::create([
            'title'       => 'سامسونج جالاكسي S24',
            'slug'        => 'samsung-galaxy-s24',
            'description' => 'جهاز جديد بالكرتونة.',
            'price'       => 30_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->seller->id,
            'status'      => Listing::STATUS_PENDING,
        ]);

        // Before approval: title must NOT appear in any listings section.
        $this->get('/')->assertOk()->assertDontSee('سامسونج جالاكسي S24');

        // Approve the listing.
        $listing->approve($this->admin->id);

        // After approval: title must appear in the latest listings section.
        $this->get('/')
            ->assertOk()
            ->assertSee('سامسونج جالاكسي S24');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// Test 5 – Boost (Feature) System
// ═══════════════════════════════════════════════════════════════════════════

describe('Boost System', function () {

    beforeEach(function () {
        $this->category = Category::create([
            'name_ar'   => 'سيارات',
            'name_en'   => 'Cars',
            'slug'      => 'cars',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'points'            => 500,
            'is_phone_verified' => true,
        ]);
    });

    it('purchasing a boost sets is_featured to true', function () {
        $listing = Listing::create([
            'title'       => 'تويوتا كورولا 2022',
            'slug'        => 'toyota-corolla-2022',
            'description' => 'سيارة ممتازة بحالة جيدة.',
            'price'       => 150_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->user->id,
            'status'      => Listing::STATUS_PUBLISHED,
        ]);

        $listing->featureWithPoints(7);

        expect($listing->fresh()->is_featured)->toBeTrue();
    });

    it('sets featured_until to the correct future date', function () {
        $listing = Listing::create([
            'title'       => 'هوندا سيفيك 2021',
            'slug'        => 'honda-civic-2021',
            'description' => 'سيارة نظيفة، مالك واحد.',
            'price'       => 120_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->user->id,
            'status'      => Listing::STATUS_PUBLISHED,
        ]);

        $days = 7;

        $listing->featureWithPoints($days);

        $fresh = $listing->fresh();

        // Timestamps are stored with second precision in SQLite, so we allow a
        // small window (≤ 5 s) when comparing against now() + $days.
        $expectedApprox = now()->addDays($days);

        expect($fresh->featured_until)->not->toBeNull()
            ->and($fresh->featured_until->isFuture())->toBeTrue()
            ->and(abs($fresh->featured_until->diffInSeconds($expectedApprox)))->toBeLessThanOrEqual(5);
    });

    it('deducts the correct points for the boost duration', function () {
        $listing = Listing::create([
            'title'       => 'نيسان التيما 2023',
            'slug'        => 'nissan-altima-2023',
            'description' => 'وصف الإعلان التجريبي.',
            'price'       => 200_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->user->id,
            'status'      => Listing::STATUS_PUBLISHED,
        ]);

        $days         = 3;
        $expectedCost = Listing::featureCost($days); // 90 pts for 3 days

        $listing->featureWithPoints($days);

        expect($this->user->fresh()->points)->toBe(500 - $expectedCost);
    });

    it('boosted listing appears in the featured section on the homepage', function () {
        // Create 4 non-featured published listings with older timestamps so that
        // any latest()-based ordering would prefer them over the featured one.
        foreach (range(1, 4) as $i) {
            Listing::create([
                'title'       => "إعلان عادي {$i}",
                'slug'        => "regular-listing-{$i}",
                'description' => 'وصف.',
                'price'       => 1_000,
                'category_id' => $this->category->id,
                'user_id'     => $this->user->id,
                'status'      => Listing::STATUS_PUBLISHED,
                'is_featured' => false,
                'created_at'  => now()->subDays(5 + $i),
                'updated_at'  => now()->subDays(5 + $i),
            ]);
        }

        // The boosted listing is even older (created 20 days ago).
        // Only the scopeFeatured() filter — not latest() — will surface it
        // in the dedicated featured section.
        $featured = Listing::create([
            'title'          => 'سيارة مميزة - إعلان مدفوع',
            'slug'           => 'featured-boosted-car',
            'description'    => 'إعلان مدفوع يجب أن يظهر في قسم المميزين.',
            'price'          => 300_000,
            'category_id'    => $this->category->id,
            'user_id'        => $this->user->id,
            'status'         => Listing::STATUS_PUBLISHED,
            'is_featured'    => true,
            'featured_until' => now()->addDays(7),
            'created_at'     => now()->subDays(20),
            'updated_at'     => now()->subDays(20),
        ]);

        $response = $this->get('/');

        $response->assertOk();

        // The featured listing must be present in the $featuredListings view variable
        // (the dedicated "featured / boosted" section at the top of the homepage).
        $response->assertViewHas('featuredListings', function ($featuredListings) use ($featured) {
            return $featuredListings->contains('id', $featured->id);
        });

        // The four regular listings must NOT appear in the $featuredListings variable.
        $response->assertViewHas('featuredListings', function ($featuredListings) {
            return $featuredListings->where('is_featured', false)->isEmpty();
        });
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// Test 6 – Phone Reveal Endpoint
// ═══════════════════════════════════════════════════════════════════════════

describe('Phone Reveal Endpoint', function () {

    beforeEach(function () {
        $this->category = Category::create([
            'name_ar'   => 'موبايلات',
            'name_en'   => 'Phones',
            'slug'      => 'phones',
            'is_active' => true,
        ]);

        $seller = User::factory()->create([
            'phone'             => '01001234567',
            'is_phone_verified' => true,
        ]);

        $this->listing = Listing::create([
            'title'       => 'أوبو فايند X6',
            'slug'        => 'oppo-find-x6',
            'description' => 'جهاز ممتاز بحالة ممتازة.',
            'price'       => 25_000,
            'phone'       => '01001234567',
            'category_id' => $this->category->id,
            'user_id'     => $seller->id,
            'status'      => Listing::STATUS_PUBLISHED,
            'whatsapp_clicks' => 0,
        ]);
    });

    it('returns 401 unauthenticated for a guest', function () {
        $this->postJson(route('listings.reveal-phone', $this->listing))
            ->assertStatus(401);
    });

    it('returns 200 with phone number for an authenticated user', function () {
        $buyer = User::factory()->create(['is_phone_verified' => true]);

        $this->actingAs($buyer)
            ->postJson(route('listings.reveal-phone', $this->listing))
            ->assertOk()
            ->assertJsonStructure(['phone', 'whatsapp_url'])
            ->assertJsonFragment(['phone' => '01001234567']);
    });

    it('records a phone click on reveal without incrementing whatsapp_clicks', function () {
        $buyer = User::factory()->create(['is_phone_verified' => true]);

        expect($this->listing->whatsapp_clicks)->toBe(0);

        $this->actingAs($buyer)
            ->postJson(route('listings.reveal-phone', $this->listing));

        expect($this->listing->fresh()->whatsapp_clicks)->toBe(0);
        $this->assertDatabaseCount('listing_phone_clicks', 1);

        // Deduped within 1 hour — second reveal does not create another row.
        $this->actingAs($buyer)
            ->postJson(route('listings.reveal-phone', $this->listing));

        expect($this->listing->fresh()->whatsapp_clicks)->toBe(0);
        $this->assertDatabaseCount('listing_phone_clicks', 1);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// Test 7 – WhatsApp Click Tracking (TD-01 Phase 1)
// ═══════════════════════════════════════════════════════════════════════════

describe('WhatsApp Click Tracking (TD-01 Phase 1)', function () {

    beforeEach(function () {
        $this->category = Category::create([
            'name_ar'   => 'موبايلات',
            'name_en'   => 'Phones',
            'slug'      => 'phones-whatsapp',
            'is_active' => true,
        ]);

        $seller = User::factory()->create([
            'phone'             => '01009876543',
            'is_phone_verified' => true,
        ]);

        $this->listing = Listing::create([
            'title'           => 'سامسونج جالاكسي S24',
            'slug'            => 'samsung-galaxy-s24',
            'description'     => 'جهاز بحالة ممتازة.',
            'price'           => 30_000,
            'phone'           => '01009876543',
            'category_id'     => $this->category->id,
            'user_id'         => $seller->id,
            'status'          => Listing::STATUS_PUBLISHED,
            'views_count'     => 0,
            'whatsapp_clicks' => 0,
        ]);
    });

    it('records first whatsapp click in event table and increments whatsapp_clicks', function () {
        $buyer = User::factory()->create(['is_phone_verified' => true]);

        expect($this->listing->whatsapp_clicks)->toBe(0);

        $this->actingAs($buyer)
            ->postJson(route('listings.whatsapp-click', $this->listing))
            ->assertOk()
            ->assertJson(['success' => true]);

        expect($this->listing->fresh()->whatsapp_clicks)->toBe(1);
        $this->assertDatabaseCount('listing_whatsapp_clicks', 1);
    });

    it('does not record duplicate whatsapp click or increment whatsapp_clicks within dedup window', function () {
        $buyer = User::factory()->create(['is_phone_verified' => true]);

        $this->actingAs($buyer)
            ->postJson(route('listings.whatsapp-click', $this->listing));

        expect($this->listing->fresh()->whatsapp_clicks)->toBe(1);
        $this->assertDatabaseCount('listing_whatsapp_clicks', 1);

        $this->actingAs($buyer)
            ->postJson(route('listings.whatsapp-click', $this->listing))
            ->assertOk()
            ->assertJson(['success' => true]);

        expect($this->listing->fresh()->whatsapp_clicks)->toBe(1);
        $this->assertDatabaseCount('listing_whatsapp_clicks', 1);
    });

    it('keeps revealPhone and view tracking behavior intact', function () {
        $buyer = User::factory()->create(['is_phone_verified' => true]);

        $this->actingAs($buyer)
            ->postJson(route('listings.reveal-phone', $this->listing))
            ->assertOk()
            ->assertJsonStructure(['phone', 'whatsapp_url']);

        expect($this->listing->fresh()->whatsapp_clicks)->toBe(0);
        $this->assertDatabaseCount('listing_phone_clicks', 1);
        $this->assertDatabaseCount('listing_whatsapp_clicks', 0);

        $this->get(route('listings.show', $this->listing))
            ->assertOk();

        expect($this->listing->fresh()->views_count)->toBe(1);
        $this->assertDatabaseCount('listing_views', 1);
        expect($this->listing->fresh()->whatsapp_clicks)->toBe(0);
    });
});
