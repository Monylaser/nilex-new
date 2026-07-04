<?php

/**
 * Ad Popup component — server-side rendering tests.
 *
 * Verifies the popup renders (or not) correctly and that the HTML structure
 * matches the full-screen design spec.
 */

use App\Models\AdCampaign;
use App\Models\User;
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
    $campaign->addMedia(UploadedFile::fake()->image('popup.jpg', 1080, 1920))
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

    it('uses a full-screen fixed overlay (bg-black/70 backdrop-blur-sm)', function () {
        $campaign = makePopupCampaign();
        attachPopupImage($campaign);
        Cache::flush();

        $html = $this->get(route('home'))->content();

        expect($html)
            ->toContain('bg-black/70')
            ->toContain('backdrop-blur-sm')
            ->toContain('fixed inset-0');
    });

    it('applies w-full h-full object-cover to the campaign image', function () {
        $campaign = makePopupCampaign();
        attachPopupImage($campaign);
        Cache::flush();

        $html = $this->get(route('home'))->content();

        expect($html)
            ->toContain('w-full h-full object-cover');
    });

    it('positions skip controls at bottom centre with absolute positioning', function () {
        $campaign = makePopupCampaign();
        attachPopupImage($campaign);
        Cache::flush();

        $html = $this->get(route('home'))->content();

        expect($html)->toContain('absolute bottom-8');
    });

    it('renders the countdown label separately from the skip button', function () {
        $campaign = makePopupCampaign();
        attachPopupImage($campaign);
        Cache::flush();

        $html = $this->get(route('home'))->content();

        // Countdown text lives in a <p>, not the button
        expect($html)
            ->toContain('تخطي بعد')
            ->toContain('ثوانٍ')
            ->toContain('x-text="countdown"');
    });

    it('skip button has @click="close()" and no x-show on itself', function () {
        $campaign = makePopupCampaign();
        attachPopupImage($campaign);
        Cache::flush();

        $html = $this->get(route('home'))->content();

        // The button must have @click but the button tag itself must not carry x-show
        expect($html)
            ->toContain('@click="close()"')
            ->toContain(':disabled="!skipEnabled"');

        // x-show appears on the <span> children, not on the <button> line
        preg_match_all('/<button[^>]*>/s', $html, $buttons);
        foreach ($buttons[0] as $tag) {
            expect($tag)->not->toContain('x-show');
        }
    });

    it('includes fade x-transition directives for smooth open/close', function () {
        $campaign = makePopupCampaign();
        attachPopupImage($campaign);
        Cache::flush();

        $html = $this->get(route('home'))->content();

        expect($html)
            ->toContain('x-transition:enter')
            ->toContain('x-transition:leave');
    });

    it('renders a clickable full-screen link when target_url is set', function () {
        $campaign = makePopupCampaign(['target_url' => 'https://example.com/promo']);
        attachPopupImage($campaign);
        Cache::flush();

        $html = $this->get(route('home'))->content();

        expect($html)
            ->toContain(route('ads.click', $campaign))
            ->toContain('absolute inset-0 block');
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
