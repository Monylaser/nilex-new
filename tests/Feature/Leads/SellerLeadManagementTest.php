<?php

/**
 * Nilex Platform — Seller Lead Management
 * Lead creation from tracked events (observers).
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingPhoneClick;
use App\Models\ListingWhatsappClick;
use App\Models\Offer;
use App\Models\SellerLead;
use App\Models\User;
use App\Services\ListingLeadTrackingService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeLeadListing(User $seller, Category $category, array $overrides = []): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create(array_merge([
        'title'           => fake()->sentence(3),
        'slug'            => 'lead-listing-' . $counter . '-' . uniqid(),
        'description'     => fake()->paragraph(),
        'price'           => fake()->randomNumber(5),
        'category_id'     => $category->id,
        'user_id'         => $seller->id,
        'status'          => Listing::STATUS_PUBLISHED,
        'views_count'     => 0,
        'whatsapp_clicks' => 0,
        'phone'           => '01012345678',
    ], $overrides));
}

describe('Seller lead creation from tracked events', function () {

    beforeEach(function () {
        $this->seller = User::factory()->create(['is_phone_verified' => true]);
        $this->buyer  = User::factory()->create(['is_phone_verified' => true]);

        $this->category = Category::create([
            'name_ar'   => 'إلكترونيات',
            'name_en'   => 'Electronics',
            'slug'      => 'electronics-leads',
            'is_active' => true,
        ]);

        $this->listing = makeLeadListing($this->seller, $this->category);
        $this->tracking = app(ListingLeadTrackingService::class);
    });

    it('creates a lead when a phone click is recorded', function () {
        $this->tracking->recordPhoneClick($this->listing, $this->buyer);

        $lead = SellerLead::query()->first();

        expect($lead)->not->toBeNull()
            ->and($lead->seller_id)->toBe($this->seller->id)
            ->and($lead->listing_id)->toBe($this->listing->id)
            ->and($lead->buyer_id)->toBe($this->buyer->id)
            ->and($lead->source_type)->toBe(SellerLead::SOURCE_PHONE_REVEAL)
            ->and($lead->status)->toBe(SellerLead::STATUS_NEW)
            ->and($lead->activities)->toHaveCount(1);
    });

    it('creates a lead when a whatsapp click is recorded', function () {
        $this->tracking->recordWhatsappClick($this->listing, null);

        $lead = SellerLead::query()->first();

        expect($lead)->not->toBeNull()
            ->and($lead->source_type)->toBe(SellerLead::SOURCE_WHATSAPP_CLICK)
            ->and($lead->buyer_id)->toBeNull();
    });

    it('creates a lead when an offer is submitted', function () {
        Offer::create([
            'listing_id'  => $this->listing->id,
            'sender_id'   => $this->buyer->id,
            'receiver_id' => $this->seller->id,
            'amount'      => 15000,
            'message'     => 'Interested in buying',
            'status'      => 'pending',
        ]);

        $lead = SellerLead::query()->first();

        expect($lead)->not->toBeNull()
            ->and($lead->source_type)->toBe(SellerLead::SOURCE_OFFER)
            ->and($lead->buyer_id)->toBe($this->buyer->id);
    });

    it('does not duplicate leads for the same source event', function () {
        $click = ListingPhoneClick::query()->create([
            'listing_id' => $this->listing->id,
            'user_id'    => $this->buyer->id,
        ]);

        expect(SellerLead::query()->count())->toBe(1);

        app(\App\Services\SellerLeadService::class)->createFromPhoneClick($click);

        expect(SellerLead::query()->count())->toBe(1);
    });
});

describe('Seller lead visibility and filters', function () {

    beforeEach(function () {
        $this->seller = User::factory()->create(['is_phone_verified' => true]);
        $this->buyer  = User::factory()->create(['is_phone_verified' => true]);

        $this->category = Category::create([
            'name_ar'   => 'سيارات',
            'name_en'   => 'Cars',
            'slug'      => 'cars-leads',
            'is_active' => true,
        ]);

        $this->listing = makeLeadListing($this->seller, $this->category);
    });

    it('shows leads only for the authenticated seller', function () {
        ListingPhoneClick::query()->create([
            'listing_id' => $this->listing->id,
            'user_id'    => $this->buyer->id,
        ]);

        $otherSeller = User::factory()->create(['is_phone_verified' => true]);

        $this->actingAs($this->seller)
            ->get(route('dashboard.leads'))
            ->assertOk()
            ->assertSee($this->listing->title);

        $this->actingAs($otherSeller)
            ->get(route('dashboard.leads'))
            ->assertOk()
            ->assertDontSee($this->listing->title);
    });

    it('filters leads by period', function () {
        $oldListing = makeLeadListing($this->seller, $this->category, [
            'title' => 'Old Period Lead Listing XYZ',
            'slug'  => 'old-period-lead-' . uniqid(),
        ]);

        $oldLead = SellerLead::query()->create([
            'seller_id'   => $this->seller->id,
            'listing_id'  => $oldListing->id,
            'buyer_id'    => $this->buyer->id,
            'source_type' => SellerLead::SOURCE_PHONE_REVEAL,
            'source_id'   => 99901,
            'status'      => SellerLead::STATUS_NEW,
        ]);
        $oldLead->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();

        $freshListing = makeLeadListing($this->seller, $this->category, [
            'title' => 'Fresh Today Lead Listing ABC',
            'slug'  => 'fresh-today-lead-' . uniqid(),
        ]);

        ListingPhoneClick::query()->create([
            'listing_id' => $freshListing->id,
            'user_id'    => $this->buyer->id,
            'created_at' => now(),
        ]);

        Livewire\Livewire::actingAs($this->seller)
            ->test(\App\Livewire\Frontend\SellerLeads::class)
            ->set('period', '7days')
            ->assertSee('Fresh Today Lead Listing ABC')
            ->assertDontSee('Old Period Lead Listing XYZ');

        Livewire\Livewire::actingAs($this->seller)
            ->test(\App\Livewire\Frontend\SellerLeads::class)
            ->set('period', 'today')
            ->assertSee('Fresh Today Lead Listing ABC')
            ->assertDontSee('Old Period Lead Listing XYZ');
    });
});

describe('Seller lead ownership checks', function () {

    beforeEach(function () {
        $this->seller = User::factory()->create(['is_phone_verified' => true]);
        $this->other   = User::factory()->create(['is_phone_verified' => true]);
        $this->buyer   = User::factory()->create(['is_phone_verified' => true]);

        $this->category = Category::create([
            'name_ar'   => 'عقارات',
            'name_en'   => 'Real Estate',
            'slug'      => 're-leads',
            'is_active' => true,
        ]);

        $this->listing = makeLeadListing($this->seller, $this->category);

        $this->lead = SellerLead::query()->create([
            'seller_id'   => $this->seller->id,
            'listing_id'  => $this->listing->id,
            'buyer_id'    => $this->buyer->id,
            'source_type' => SellerLead::SOURCE_WHATSAPP_CLICK,
            'source_id'   => 88801,
            'status'      => SellerLead::STATUS_NEW,
        ]);

        \App\Models\SellerLeadActivity::query()->create([
            'seller_lead_id' => $this->lead->id,
            'type'           => 'created',
            'metadata'       => [],
        ]);
    });

    it('allows the seller to view lead detail', function () {
        $this->actingAs($this->seller)
            ->get(route('dashboard.leads.show', $this->lead))
            ->assertOk()
            ->assertSee($this->listing->title);
    });

    it('forbids other sellers from viewing lead detail', function () {
        $this->actingAs($this->other)
            ->get(route('dashboard.leads.show', $this->lead))
            ->assertForbidden();
    });

    it('allows seller to update lead status', function () {
        Livewire\Livewire::actingAs($this->seller)
            ->test(\App\Livewire\Frontend\SellerLeadDetail::class, ['lead' => $this->lead])
            ->set('status', SellerLead::STATUS_CONTACTED)
            ->call('updateStatus')
            ->assertHasNoErrors();

        expect($this->lead->fresh()->status)->toBe(SellerLead::STATUS_CONTACTED)
            ->and($this->lead->activities()->count())->toBe(2);
    });
});

describe('Seller leads dashboard access', function () {

    it('redirects guests to login', function () {
        $this->get(route('dashboard.leads'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated verified sellers to access leads index', function () {
        $seller = User::factory()->create(['is_phone_verified' => true]);

        $this->actingAs($seller)
            ->get(route('dashboard.leads'))
            ->assertOk();
    });
});
