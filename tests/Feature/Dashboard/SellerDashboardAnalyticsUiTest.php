<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * TD-01 Phase 2B: Seller Dashboard Analytics UI
 *   - Event metrics render in Blade when values exist
 *   - Legacy metrics remain visible
 *   - Both legacy and event sections coexist
 *   - Empty-state (zero) rendering for event metrics
 */

use App\Livewire\Frontend\UserDashboard;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingPhoneClick;
use App\Models\ListingView;
use App\Models\ListingWhatsappClick;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeUiTestListing(User $seller, Category $category, array $overrides = []): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create(array_merge([
        'title'           => fake()->sentence(3),
        'slug'            => 'ui-analytics-listing-' . $counter . '-' . uniqid(),
        'description'     => fake()->paragraph(),
        'price'           => fake()->randomNumber(5),
        'category_id'     => $category->id,
        'user_id'         => $seller->id,
        'status'          => Listing::STATUS_PUBLISHED,
        'views_count'     => 0,
        'whatsapp_clicks' => 0,
    ], $overrides));
}

describe('Seller Dashboard Analytics UI (TD-01 Phase 2B)', function () {

    beforeEach(function () {
        $this->seller = User::factory()->create(['is_phone_verified' => true]);
        $this->buyer  = User::factory()->create(['is_phone_verified' => true]);

        $this->category = Category::create([
            'name_ar'   => 'إلكترونيات',
            'name_en'   => 'Electronics',
            'slug'      => 'electronics-ui-analytics',
            'is_active' => true,
        ]);
    });

    it('renders event metrics when values exist', function () {
        $listing = makeUiTestListing($this->seller, $this->category, [
            'views_count'     => 10,
            'whatsapp_clicks' => 5,
        ]);

        ListingView::query()->create([
            'listing_id' => $listing->id,
            'user_id'    => $this->buyer->id,
        ]);
        ListingView::query()->create([
            'listing_id' => $listing->id,
            'user_id'    => User::factory()->create()->id,
        ]);
        ListingPhoneClick::query()->create([
            'listing_id' => $listing->id,
            'user_id'    => $this->buyer->id,
        ]);
        ListingWhatsappClick::query()->create([
            'listing_id' => $listing->id,
            'user_id'    => $this->buyer->id,
        ]);
        ListingWhatsappClick::query()->create([
            'listing_id' => $listing->id,
            'user_id'    => User::factory()->create()->id,
        ]);

        Livewire::actingAs($this->seller)
            ->test(UserDashboard::class)
            ->assertSee('Verified Analytics')
            ->assertSee('Event-Based Analytics')
            ->assertSee('Event Views')
            ->assertSee('Phone Clicks')
            ->assertSee('WhatsApp Clicks')
            ->assertSee('2')
            ->assertSee('1');
    });

    it('still renders legacy metrics alongside event metrics', function () {
        makeUiTestListing($this->seller, $this->category, [
            'views_count'     => 500,
            'whatsapp_clicks' => 40,
        ]);

        Livewire::actingAs($this->seller)
            ->test(UserDashboard::class)
            ->assertSee('مشاهدات')
            ->assertSee('واتساب')
            ->assertSee('500')
            ->assertSee('40');
    });

    it('contains both legacy stats section and verified analytics section', function () {
        makeUiTestListing($this->seller, $this->category, [
            'views_count'     => 100,
            'whatsapp_clicks' => 25,
        ]);

        $listing = Listing::where('user_id', $this->seller->id)->first();
        ListingView::query()->create([
            'listing_id' => $listing->id,
            'user_id'    => $this->buyer->id,
        ]);

        Livewire::actingAs($this->seller)
            ->test(UserDashboard::class)
            ->assertSee('مشاهدات')
            ->assertSee('100')
            ->assertSee('Verified Analytics')
            ->assertSee('Event Views')
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['views'] === 100
                    && $stats['clicks'] === 25
                    && $stats['views_events'] === 1
                    && $stats['phone_clicks'] === 0
                    && $stats['whatsapp_clicks_events'] === 0;
            });
    });

    it('renders zero event metrics in empty state', function () {
        Livewire::actingAs($this->seller)
            ->test(UserDashboard::class)
            ->assertSee('Verified Analytics')
            ->assertSee('Event Views')
            ->assertSee('Phone Clicks')
            ->assertSee('WhatsApp Clicks')
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['views_events'] === 0
                    && $stats['phone_clicks'] === 0
                    && $stats['whatsapp_clicks_events'] === 0;
            });
    });
});
