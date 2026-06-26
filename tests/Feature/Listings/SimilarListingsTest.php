<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Similar Listings section (listing detail page, additive).
 *   - Section shows only PUBLISHED listings from the SAME category.
 *   - The current listing is never included in its own "similar" results.
 *   - Listings from other categories / non-published are excluded.
 *   - Section is hidden entirely when no similar listings exist (no empty state).
 *   - Same-province listings are ordered before others.
 *   - Result set is capped at 6.
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Similar Listings section', function () {

    beforeEach(function () {
        $this->category = Category::create([
            'name_ar'   => 'إلكترونيات',
            'name_en'   => 'Electronics',
            'slug'      => 'electronics',
            'is_active' => true,
        ]);

        $this->otherCategory = Category::create([
            'name_ar'   => 'أثاث',
            'name_en'   => 'Furniture',
            'slug'      => 'furniture',
            'is_active' => true,
        ]);

        $this->seller = User::factory()->create(['is_phone_verified' => true]);

        $this->current = Listing::create([
            'title'       => 'الإعلان الحالي',
            'slug'        => 'current-listing',
            'description' => 'وصف الإعلان الحالي.',
            'price'       => 10_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->seller->id,
            'status'      => Listing::STATUS_PUBLISHED,
        ]);
    });

    it('shows published listings from the same category and excludes the current listing', function () {
        $similar = Listing::create([
            'title'       => 'إعلان مشابه منشور',
            'slug'        => 'similar-published',
            'description' => 'وصف.',
            'price'       => 12_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->seller->id,
            'status'      => Listing::STATUS_PUBLISHED,
        ]);

        $response = $this->get(route('listings.show', $this->current));

        $response->assertOk()
            ->assertSee(__('listing.detail.similar_heading'))
            ->assertSee('إعلان مشابه منشور');

        $response->assertViewHas('similarListings', function ($similarListings) use ($similar) {
            return $similarListings->contains('id', $similar->id)
                && ! $similarListings->contains('id', $this->current->id);
        });
    });

    it('hides the section entirely when there are no similar listings', function () {
        $response = $this->get(route('listings.show', $this->current));

        $response->assertOk()
            ->assertDontSee(__('listing.detail.similar_heading'));

        $response->assertViewHas('similarListings', function ($similarListings) {
            return $similarListings->isEmpty();
        });
    });

    it('excludes listings from other categories and non-published listings', function () {
        $otherCat = Listing::create([
            'title'       => 'إعلان قسم آخر',
            'slug'        => 'other-category',
            'description' => 'وصف.',
            'price'       => 9_000,
            'category_id' => $this->otherCategory->id,
            'user_id'     => $this->seller->id,
            'status'      => Listing::STATUS_PUBLISHED,
        ]);

        $pending = Listing::create([
            'title'       => 'إعلان معلق نفس القسم',
            'slug'        => 'pending-same-category',
            'description' => 'وصف.',
            'price'       => 11_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->seller->id,
            'status'      => Listing::STATUS_PENDING,
        ]);

        $response = $this->get(route('listings.show', $this->current));

        $response->assertOk()
            ->assertDontSee('إعلان قسم آخر')
            ->assertDontSee('إعلان معلق نفس القسم');

        $response->assertViewHas('similarListings', function ($similarListings) use ($otherCat, $pending) {
            return ! $similarListings->contains('id', $otherCat->id)
                && ! $similarListings->contains('id', $pending->id);
        });
    });

    it('orders same-province listings before others', function () {
        $province = Location::create([
            'name_ar'   => 'القاهرة',
            'name_en'   => 'Cairo',
            'slug'      => 'cairo-province',
            'parent_id' => null,
        ]);

        // Re-point the current listing to a province.
        $this->current->update(['province_id' => $province->id]);

        // Older listing but in the SAME province → should rank first.
        $sameProvince = Listing::create([
            'title'       => 'إعلان نفس المحافظة',
            'slug'        => 'same-province',
            'description' => 'وصف.',
            'price'       => 13_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->seller->id,
            'province_id' => $province->id,
            'status'      => Listing::STATUS_PUBLISHED,
            'created_at'  => now()->subDays(10),
            'updated_at'  => now()->subDays(10),
        ]);

        // Newer listing but a DIFFERENT (null) province → should rank after.
        $otherProvince = Listing::create([
            'title'       => 'إعلان محافظة أخرى',
            'slug'        => 'other-province',
            'description' => 'وصف.',
            'price'       => 14_000,
            'category_id' => $this->category->id,
            'user_id'     => $this->seller->id,
            'province_id' => null,
            'status'      => Listing::STATUS_PUBLISHED,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->get(route('listings.show', $this->current->fresh()));

        $response->assertOk();
        $response->assertViewHas('similarListings', function ($similarListings) use ($sameProvince, $otherProvince) {
            $ids = $similarListings->pluck('id')->values();

            return $ids->search($sameProvince->id) < $ids->search($otherProvince->id);
        });
    });

    it('caps the similar listings at 6', function () {
        foreach (range(1, 8) as $i) {
            Listing::create([
                'title'       => "إعلان مشابه {$i}",
                'slug'        => "similar-{$i}",
                'description' => 'وصف.',
                'price'       => 1_000 + $i,
                'category_id' => $this->category->id,
                'user_id'     => $this->seller->id,
                'status'      => Listing::STATUS_PUBLISHED,
            ]);
        }

        $response = $this->get(route('listings.show', $this->current));

        $response->assertOk();
        $response->assertViewHas('similarListings', function ($similarListings) {
            return $similarListings->count() === 6;
        });
    });
});
