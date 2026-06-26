<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Favorites system (additive feature).
 *   - Authenticated users can add a listing to favorites (toggle on).
 *   - Toggling again removes it (toggle off).
 *   - The unique (user_id, listing_id) constraint prevents duplicates.
 *   - Guests are rejected with 401 (the front-end then redirects to /login).
 *   - The "My Favorites" dashboard page lists the user's saved listings only.
 */

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Listing;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics',
        'is_active' => true,
    ]);

    $this->seller = User::factory()->create(['is_phone_verified' => true]);

    $this->listing = Listing::create([
        'title'       => 'إعلان للاختبار',
        'slug'        => 'test-listing',
        'description' => 'وصف.',
        'price'       => 10_000,
        'category_id' => $this->category->id,
        'user_id'     => $this->seller->id,
        'status'      => Listing::STATUS_PUBLISHED,
    ]);
});

it('lets an authenticated user add a listing to favorites', function () {
    $user = User::factory()->create(['is_phone_verified' => true]);

    $response = $this->actingAs($user)
        ->postJson(route('listings.favorite', $this->listing));

    $response->assertOk()->assertJson(['favorited' => true]);

    $this->assertDatabaseHas('favorites', [
        'user_id'    => $user->id,
        'listing_id' => $this->listing->id,
    ]);
});

it('removes the listing when toggled a second time', function () {
    $user = User::factory()->create(['is_phone_verified' => true]);

    $this->actingAs($user)->postJson(route('listings.favorite', $this->listing))
        ->assertJson(['favorited' => true]);

    $this->actingAs($user)->postJson(route('listings.favorite', $this->listing))
        ->assertJson(['favorited' => false]);

    $this->assertDatabaseMissing('favorites', [
        'user_id'    => $user->id,
        'listing_id' => $this->listing->id,
    ]);
});

it('prevents duplicate favorites via the unique constraint', function () {
    $user = User::factory()->create(['is_phone_verified' => true]);

    Favorite::create(['user_id' => $user->id, 'listing_id' => $this->listing->id]);

    expect(fn () => Favorite::create([
        'user_id'    => $user->id,
        'listing_id' => $this->listing->id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);

    expect(Favorite::where('user_id', $user->id)->where('listing_id', $this->listing->id)->count())
        ->toBe(1);
});

it('rejects guests with 401', function () {
    $response = $this->postJson(route('listings.favorite', $this->listing));

    $response->assertStatus(401);

    $this->assertDatabaseCount('favorites', 0);
});

it('shows only the user saved listings on the favorites page', function () {
    $user = User::factory()->create(['is_phone_verified' => true]);

    $otherListing = Listing::create([
        'title'       => 'إعلان غير محفوظ',
        'slug'        => 'not-saved-listing',
        'description' => 'وصف.',
        'price'       => 5_000,
        'category_id' => $this->category->id,
        'user_id'     => $this->seller->id,
        'status'      => Listing::STATUS_PUBLISHED,
    ]);

    Favorite::create(['user_id' => $user->id, 'listing_id' => $this->listing->id]);

    $response = $this->actingAs($user)->get(route('dashboard.favorites'));

    $response->assertOk()
        ->assertSee('إعلان للاختبار')
        ->assertDontSee('إعلان غير محفوظ');

    $response->assertViewHas('listings', function ($listings) use ($otherListing) {
        return $listings->contains('id', $this->listing->id)
            && ! $listings->contains('id', $otherListing->id);
    });
});

it('reflects favorite state through the User helper', function () {
    $user = User::factory()->create(['is_phone_verified' => true]);

    expect($user->isFavorited($this->listing->id))->toBeFalse();

    Favorite::create(['user_id' => $user->id, 'listing_id' => $this->listing->id]);

    expect($user->fresh()->isFavorited($this->listing))->toBeTrue();
});
