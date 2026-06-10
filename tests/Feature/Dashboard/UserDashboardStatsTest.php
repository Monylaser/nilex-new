<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Test 10: User Dashboard Statistics
 *   - Dashboard shows correct total listings count.
 *   - Dashboard shows correct total views_count.
 *   - Dashboard shows correct total whatsapp_clicks.
 */

use App\Livewire\Frontend\UserDashboard;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ═══════════════════════════════════════════════════════════════════════════
// Helpers
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Create a listing directly without going through the HTTP layer, which
 * avoids the need for Scout indexing or Spatie Media Library setup.
 */
function makeListing(User $user, Category $category, array $overrides = []): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create(array_merge([
        'title'           => fake()->sentence(3),
        'slug'            => 'test-listing-' . $counter . '-' . uniqid(),
        'description'     => fake()->paragraph(),
        'price'           => fake()->randomNumber(5),
        'category_id'     => $category->id,
        'user_id'         => $user->id,
        'status'          => Listing::STATUS_PUBLISHED,
        'views_count'     => 0,
        'whatsapp_clicks' => 0,
    ], $overrides));
}

// ═══════════════════════════════════════════════════════════════════════════
// Test 10 – User Dashboard Statistics
// ═══════════════════════════════════════════════════════════════════════════

describe('User Dashboard Statistics', function () {

    beforeEach(function () {
        $this->user = User::factory()->create(['is_phone_verified' => true]);

        $this->category = Category::create([
            'name_ar'   => 'إلكترونيات',
            'name_en'   => 'Electronics',
            'slug'      => 'electronics-dashboard',
            'is_active' => true,
        ]);
    });

    // ── Total listings count ─────────────────────────────────────────────────

    it('dashboard shows correct total listings count', function () {
        makeListing($this->user, $this->category);
        makeListing($this->user, $this->category);
        makeListing($this->user, $this->category);

        // A listing from another user must NOT be counted.
        $other = User::factory()->create();
        makeListing($other, $this->category);

        Livewire::actingAs($this->user)
            ->test(UserDashboard::class)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['total'] === 3);
    });

    it('dashboard shows zero total when user has no listings', function () {
        Livewire::actingAs($this->user)
            ->test(UserDashboard::class)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['total'] === 0);
    });

    it('dashboard counts all statuses (pending, published, rejected) in total', function () {
        makeListing($this->user, $this->category, ['status' => Listing::STATUS_PENDING]);
        makeListing($this->user, $this->category, ['status' => Listing::STATUS_PUBLISHED]);
        makeListing($this->user, $this->category, ['status' => Listing::STATUS_REJECTED]);

        Livewire::actingAs($this->user)
            ->test(UserDashboard::class)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['total'] === 3);
    });

    // ── Views count ─────────────────────────────────────────────────────────

    it('dashboard shows correct total views_count', function () {
        makeListing($this->user, $this->category, ['views_count' => 100]);
        makeListing($this->user, $this->category, ['views_count' => 250]);
        makeListing($this->user, $this->category, ['views_count' => 75]);

        // Another user's listing views should not be included.
        $other = User::factory()->create();
        makeListing($other, $this->category, ['views_count' => 9999]);

        Livewire::actingAs($this->user)
            ->test(UserDashboard::class)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['views'] === 425);
    });

    it('dashboard shows zero views when user has no listings', function () {
        Livewire::actingAs($this->user)
            ->test(UserDashboard::class)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['views'] === 0);
    });

    // ── WhatsApp clicks ──────────────────────────────────────────────────────

    it('dashboard shows correct total whatsapp_clicks', function () {
        makeListing($this->user, $this->category, ['whatsapp_clicks' => 30]);
        makeListing($this->user, $this->category, ['whatsapp_clicks' => 20]);
        makeListing($this->user, $this->category, ['whatsapp_clicks' => 10]);

        // Another user's clicks should not be counted.
        $other = User::factory()->create();
        makeListing($other, $this->category, ['whatsapp_clicks' => 9999]);

        Livewire::actingAs($this->user)
            ->test(UserDashboard::class)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['clicks'] === 60);
    });

    it('dashboard shows zero clicks when user has no listings', function () {
        Livewire::actingAs($this->user)
            ->test(UserDashboard::class)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['clicks'] === 0);
    });

    // ── Combined stats snapshot ──────────────────────────────────────────────

    it('all three stats are correct simultaneously', function () {
        makeListing($this->user, $this->category, [
            'status'          => Listing::STATUS_PUBLISHED,
            'views_count'     => 500,
            'whatsapp_clicks' => 40,
        ]);
        makeListing($this->user, $this->category, [
            'status'          => Listing::STATUS_PENDING,
            'views_count'     => 300,
            'whatsapp_clicks' => 25,
        ]);

        Livewire::actingAs($this->user)
            ->test(UserDashboard::class)
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['total']  === 2
                    && $stats['views']  === 800
                    && $stats['clicks'] === 65;
            });
    });

    // ── Status breakdown ─────────────────────────────────────────────────────

    it('dashboard correctly counts active (published) listings', function () {
        makeListing($this->user, $this->category, ['status' => Listing::STATUS_PUBLISHED]);
        makeListing($this->user, $this->category, ['status' => Listing::STATUS_PUBLISHED]);
        makeListing($this->user, $this->category, ['status' => Listing::STATUS_PENDING]);

        Livewire::actingAs($this->user)
            ->test(UserDashboard::class)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['active'] === 2);
    });

    it('dashboard correctly counts pending listings', function () {
        makeListing($this->user, $this->category, ['status' => Listing::STATUS_PENDING]);
        makeListing($this->user, $this->category, ['status' => Listing::STATUS_PUBLISHED]);

        Livewire::actingAs($this->user)
            ->test(UserDashboard::class)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['pending'] === 1);
    });

    it('dashboard correctly counts rejected listings', function () {
        makeListing($this->user, $this->category, ['status' => Listing::STATUS_REJECTED]);
        makeListing($this->user, $this->category, ['status' => Listing::STATUS_REJECTED]);
        makeListing($this->user, $this->category, ['status' => Listing::STATUS_PUBLISHED]);

        Livewire::actingAs($this->user)
            ->test(UserDashboard::class)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['rejected'] === 2);
    });
});
