<?php

/**
 * Listing sort — category page + search page.
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Support\ListingSort;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create(['is_phone_verified' => true]);

    $this->category = Category::create([
        'name_ar' => 'إلكترونيات',
        'name_en' => 'Electronics',
        'slug' => 'electronics',
        'is_active' => true,
    ]);
});

function makeSortTestListing(User $seller, Category $category, array $overrides = []): Listing
{
    return Listing::create(array_merge([
        'user_id' => $seller->id,
        'category_id' => $category->id,
        'title' => 'Test listing',
        'slug' => 'test-'.uniqid(),
        'description' => 'وصف تجريبي',
        'price' => 1_000,
        'status' => Listing::STATUS_PUBLISHED,
    ], $overrides));
}

test('category page renders sort dropdown on mobile and desktop', function () {
    makeSortTestListing($this->seller, $this->category);

    $response = $this->get(route('category.show', $this->category));

    $response->assertOk();
    $response->assertSee('id="listing-sort"', false);
    $response->assertSee('name="sort"', false);
    $response->assertSee(__('ui.sort.latest'), false);
    $response->assertSee(__('ui.sort.price_asc'), false);
    $response->assertSee(__('ui.sort.price_desc'), false);
});

test('category page sorts listings by price ascending', function () {
    $cheap = makeSortTestListing($this->seller, $this->category, [
        'title' => 'Cheap phone',
        'slug' => 'cheap-phone',
        'price' => 500,
    ]);

    $expensive = makeSortTestListing($this->seller, $this->category, [
        'title' => 'Expensive phone',
        'slug' => 'expensive-phone',
        'price' => 5_000,
    ]);

    $response = $this->get(route('category.show', [
        'category' => $this->category,
        'sort' => 'price_asc',
    ]));

    $response->assertOk();
    $response->assertSeeInOrder(['Cheap phone', 'Expensive phone'], false);
});

test('category page sorts listings by oldest first', function () {
    $older = makeSortTestListing($this->seller, $this->category, [
        'title' => 'Older listing',
        'slug' => 'older-listing',
        'created_at' => now()->subDays(5),
    ]);

    $newer = makeSortTestListing($this->seller, $this->category, [
        'title' => 'Newer listing',
        'slug' => 'newer-listing',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get(route('category.show', [
        'category' => $this->category,
        'sort' => 'oldest',
    ]));

    $response->assertOk();
    $response->assertSeeInOrder(['Older listing', 'Newer listing'], false);
});

test('search page renders sort dropdown with translated options', function () {
    makeSortTestListing($this->seller, $this->category);

    $response = $this->get(route('listings.search'));

    $response->assertOk();
    $response->assertSee('name="sort"', false);
    $response->assertSee(__('ui.sort.latest'), false);
    $response->assertSee(__('ui.sort.price_desc'), false);
});

test('search page sorts listings by price descending', function () {
    makeSortTestListing($this->seller, $this->category, [
        'title' => 'Budget item',
        'slug' => 'budget-item',
        'price' => 100,
    ]);

    makeSortTestListing($this->seller, $this->category, [
        'title' => 'Premium item',
        'slug' => 'premium-item',
        'price' => 9_000,
    ]);

    $response = $this->get(route('listings.search', ['sort' => 'price_desc']));

    $response->assertOk();
    $response->assertSeeInOrder(['Premium item', 'Budget item'], false);
});

test('invalid sort parameter on category page returns 422', function () {
    makeSortTestListing($this->seller, $this->category);

    $this->getJson(route('category.show', [
        'category' => $this->category,
        'sort' => 'invalid',
    ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sort']);
});

test('invalid sort parameter on search page returns 422', function () {
    makeSortTestListing($this->seller, $this->category);

    $this->getJson(route('listings.search', ['sort' => 'invalid']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sort']);
});

test('invalid sort parameter on category page shows Nilex 422 page for web GET', function () {
    makeSortTestListing($this->seller, $this->category);

    $this->get(route('category.show', [
        'category' => $this->category,
        'sort' => 'invalid',
    ]))
        ->assertStatus(422)
        ->assertSee(__('ui.errors.422_title'), false);
});

test('invalid sort parameter falls back to latest for internal callers', function () {
    expect(ListingSort::fromRequest('invalid'))->toBe(ListingSort::DEFAULT);
    expect(ListingSort::isValid('invalid'))->toBeFalse();
    expect(ListingSort::isValid('latest'))->toBeTrue();
    expect(ListingSort::isValid(null))->toBeTrue();
});
