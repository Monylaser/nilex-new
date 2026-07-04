<?php

/**
 * Ad Popup component — server-side rendering tests.
 *
 * Verifies the popup renders (or not) correctly based on campaign state,
 * and that the HTML structure matches the expected design spec.
 */

use App\Models\AdCampaign;
use App\Models\User;
use App\Services\AdCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makePopupCampaign(array $overrides = []): AdCampaign
{
    $admin = User::factory()->create();

    return AdCampaign::create(array_merge([
        'title'           => 'إعلان popup اختبار',
        'placement'       => 'popup',
        'target_url'      => 'https://example.com/offer',
        'duration_days'   => 7,
        'status'          => 'active',
        'approval_status' => 'approved',
        'payment_status'  => null,
        'seller_id'       => null,
        'created_by'      => $admin->id,
        'starts_at'       => now()->subDay(),
        'ends_at'         => now()->addDays(6),
    ], $overrides));
}

function attachPopupImage(AdCampaign $campaign): void
{
    $campaign->addMedia(UploadedFile::fake()->image('popup.jpg', 800, 600))
        ->toMediaCollection('ad_image');
}

describe('Ad popup component', function () {

    beforeEach(function () {
        Storage::fake('public');
        Cache::flush();
    });

    it('renders nothing when no active popup campaign exists', function () {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-ad-id', false);
    });

    it('renders nothing when campaign exists but has no image', function () {
        makePopupCampaign();
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-ad-id', false);
    });

    it('renders the popup when an active popup campaign with image exists', function () {
        $campaign = makePopupCampaign();
        attachPopupImage($campaign);
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-ad-id="' . $campaign->id . '"', false);
    });

    it('uses max-w-lg for the popup card (512 px desktop width)', function () {
        $campaign = makePopupCampaign();
        attachPopupImage($campaign);
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('max-w-lg', false);
    });

    it('uses bg-black/60 backdrop-blur-sm for the overlay', function () {
        $campaign = makePopupCampaign();
        attachPopupImage($campaign);
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('bg-black/60 backdrop-blur-sm', false);
    });

    it('applies aspect-[4/3] and object-cover to the campaign image', function () {
        $campaign = makePopupCampaign();
        attachPopupImage($campaign);
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('aspect-[4/3]', false)
            ->assertSee('object-cover', false);
    });

    it('renders the skip button with countdown text', function () {
        $campaign = makePopupCampaign();
        attachPopupImage($campaign);
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('يمكنك التخطي بعد', false)
            ->assertSee('تخطي', false);
    });

    it('includes fade-out x-transition directives for smooth close animation', function () {
        $campaign = makePopupCampaign();
        attachPopupImage($campaign);
        Cache::flush();

        $html = $this->get(route('home'))->content();

        expect($html)
            ->toContain('x-transition:leave')
            ->toContain('x-transition:enter');
    });

    it('renders a clickable link when target_url is set', function () {
        $campaign = makePopupCampaign(['target_url' => 'https://example.com/promo']);
        attachPopupImage($campaign);
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('ads.click', $campaign), false);
    });

    it('renders the image without a link when target_url is null', function () {
        $campaign = makePopupCampaign(['target_url' => null]);
        attachPopupImage($campaign);
        Cache::flush();

        $html = $this->get(route('home'))->content();

        expect($html)
            ->toContain('data-ad-id="' . $campaign->id . '"')
            ->not->toContain(route('ads.click', $campaign));
    });

    it('does not render when campaign is inactive', function () {
        $campaign = makePopupCampaign(['status' => 'expired']);
        attachPopupImage($campaign);
        Cache::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-ad-id', false);
    });
});
