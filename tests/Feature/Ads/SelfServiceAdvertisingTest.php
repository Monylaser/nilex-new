<?php

/**
 * Self-Service Advertising Platform — core behaviour tests.
 */

use App\Models\AdCampaign;
use App\Models\PaymentAttempt;
use App\Models\User;
use App\Http\Requests\Dashboard\StoreSellerAdCampaignRequest;
use App\Services\AdCampaignPaymentService;
use App\Services\AdCampaignService;
use App\Services\PaymobAdWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

function makeSelfServiceCampaign(User $seller, array $overrides = []): AdCampaign
{
    return AdCampaign::create(array_merge([
        'title'           => 'حملة اختبار',
        'placement'       => 'hero_top',
        'target_url'      => 'https://example.com',
        'duration_days'   => 7,
        'status'          => 'draft',
        'approval_status' => 'pending',
        'payment_status'  => 'pending',
        'seller_id'       => $seller->id,
        'created_by'      => $seller->id,
    ], $overrides));
}

describe('Self-service feature flag', function () {

    it('blocks seller dashboard when disabled', function () {
        config(['features.self_service_ads' => false]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.ads.index'))
            ->assertNotFound();
    });

    it('allows seller dashboard when enabled', function () {
        config(['features.self_service_ads' => true]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.ads.index'))
            ->assertOk()
            ->assertSee('حملاتي الإعلانية');
    });

    it('blocks ads webhook when disabled', function () {
        config(['features.self_service_ads' => false]);

        $this->post(route('webhooks.paymob.ads'))
            ->assertNotFound();
    });
});

describe('Ad pricing configuration', function () {

    it('calculates price from config only', function () {
        $service = app(AdCampaignPaymentService::class);

        expect($service->calculatePrice('hero_top', 7))->toBe(500.00)
            ->and($service->calculatePrice('login_page', 30))->toBe(750.00);
    });

    it('rejects unknown placement or duration', function () {
        $service = app(AdCampaignPaymentService::class);

        expect(fn () => $service->calculatePrice('invalid', 7))
            ->toThrow(RuntimeException::class);

        expect(fn () => $service->calculatePrice('hero_top', 99))
            ->toThrow(RuntimeException::class);
    });
});

describe('Campaign display rules', function () {

    it('shows paid approved campaigns within date window', function () {
        config(['features.self_service_ads' => true]);

        $seller = User::factory()->create();
        $campaign = makeSelfServiceCampaign($seller, [
            'payment_status'  => 'paid',
            'approval_status' => 'approved',
            'status'          => 'active',
            'starts_at'       => now()->subDay(),
            'ends_at'         => now()->addDays(6),
        ]);

        expect($campaign->isDisplayable())->toBeTrue();

        $resolved = app(AdCampaignService::class)->getForPlacement('hero_top');

        expect($resolved?->id)->toBe($campaign->id);
    });

    it('never shows unpaid self-service campaigns', function () {
        config(['features.self_service_ads' => true]);

        $seller = User::factory()->create();
        makeSelfServiceCampaign($seller, [
            'payment_status'  => 'pending',
            'approval_status' => 'approved',
            'status'          => 'active',
            'starts_at'       => now()->subDay(),
            'ends_at'         => now()->addDays(6),
        ]);

        expect(app(AdCampaignService::class)->getForPlacement('hero_top'))->toBeNull();
    });

    it('never shows paid campaigns before manual approval', function () {
        config(['features.self_service_ads' => true]);

        $seller = User::factory()->create();
        makeSelfServiceCampaign($seller, [
            'payment_status'  => 'paid',
            'approval_status' => 'pending',
            'status'          => 'draft',
            'starts_at'       => now()->subDay(),
            'ends_at'         => now()->addDays(6),
        ]);

        expect(app(AdCampaignService::class)->getForPlacement('hero_top'))->toBeNull();
    });

    it('still shows legacy admin campaigns without payment_status', function () {
        config(['features.self_service_ads' => true]);

        $admin = User::factory()->create();
        AdCampaign::create([
            'title'           => 'حملة إدارية',
            'placement'       => 'hero_top',
            'target_url'      => 'https://example.com',
            'status'          => 'active',
            'approval_status' => 'approved',
            'payment_status'  => null,
            'seller_id'       => null,
            'created_by'      => $admin->id,
            'starts_at'       => now()->subDay(),
            'ends_at'         => now()->addDays(6),
        ]);

        expect(app(AdCampaignService::class)->getForPlacement('hero_top'))->not->toBeNull();
    });

    it('renders admin hero banner on homepage when self-service ads are disabled', function () {
        Storage::fake('public');
        config(['features.self_service_ads' => false]);

        $admin = User::factory()->create();
        $campaign = AdCampaign::create([
            'title'           => 'حملة إدارية',
            'placement'       => 'hero_top',
            'target_url'      => null,
            'status'          => 'active',
            'approval_status' => 'approved',
            'payment_status'  => null,
            'seller_id'       => null,
            'created_by'      => $admin->id,
            'starts_at'       => now()->subDay(),
            'ends_at'         => now()->addDays(6),
        ]);

        $campaign->addMedia(UploadedFile::fake()->image('banner.jpg', 1200, 400))
            ->toMediaCollection('ad_image');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('حملة إدارية', false);
    });
});

describe('Seller campaign authorization', function () {

    it('accepts optional target_url when creating seller campaigns', function () {
        $rules = (new StoreSellerAdCampaignRequest())->rules();

        $validator = Validator::make(
            ['target_url' => null],
            ['target_url' => $rules['target_url']],
        );

        expect($validator->passes())->toBeTrue();
    });

    it('prevents sellers from viewing other sellers campaigns', function () {
        config(['features.self_service_ads' => true]);

        $owner  = User::factory()->create();
        $other  = User::factory()->create();
        $campaign = makeSelfServiceCampaign($owner);

        $this->actingAs($other)
            ->get(route('dashboard.ads.show', $campaign))
            ->assertForbidden();
    });

    it('allows owners to view their own campaigns', function () {
        config(['features.self_service_ads' => true]);

        $seller   = User::factory()->create();
        $campaign = makeSelfServiceCampaign($seller);

        $this->actingAs($seller)
            ->get(route('dashboard.ads.show', $campaign))
            ->assertOk()
            ->assertSee('حملة اختبار');
    });
});

describe('Paymob ads webhook idempotency', function () {

    it('rejects invalid hmac signatures', function () {
        config(['features.self_service_ads' => true]);

        $response = app(PaymobAdWebhookService::class)->handle(
            request()->merge(['obj' => ['success' => true]])->duplicate(['hmac' => 'invalid'])
        );

        expect($response->getStatusCode())->toBe(400);
    });

    it('detects already processed transaction ids', function () {
        config(['features.self_service_ads' => true]);

        $seller = User::factory()->create();
        $campaign = makeSelfServiceCampaign($seller);

        PaymentAttempt::create([
            'campaign_id'           => $campaign->id,
            'amount'                => 500.00,
            'paymob_transaction_id' => 'txn-12345',
            'status'                => 'success',
        ]);

        $service = app(PaymobAdWebhookService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('isTransactionAlreadyProcessed');
        $method->setAccessible(true);

        expect($method->invoke($service, 'txn-12345'))->toBeTrue()
            ->and($method->invoke($service, 'txn-new'))->toBeFalse();
    });
});

describe('Ad tracking security', function () {

    it('returns 404 for impression on non-trackable campaigns', function () {
        $seller = User::factory()->create();
        $campaign = makeSelfServiceCampaign($seller, [
            'payment_status'  => 'pending',
            'approval_status' => 'pending',
            'status'          => 'draft',
        ]);

        $this->get(route('ads.impression', $campaign))
            ->assertNotFound();
    });

    it('returns 404 for click on non-trackable campaigns', function () {
        $seller = User::factory()->create();
        $campaign = makeSelfServiceCampaign($seller, [
            'payment_status'  => 'pending',
            'approval_status' => 'pending',
            'status'          => 'draft',
        ]);

        $this->get(route('ads.click', $campaign))
            ->assertNotFound();
    });

    it('accepts impression for active approved paid campaigns', function () {
        $seller = User::factory()->create();
        $campaign = makeSelfServiceCampaign($seller, [
            'payment_status'  => 'paid',
            'approval_status' => 'approved',
            'status'          => 'active',
            'starts_at'       => now()->subDay(),
            'ends_at'         => now()->addDays(6),
        ]);

        expect($campaign->isTrackable())->toBeTrue();

        $this->get(route('ads.impression', $campaign))
            ->assertOk()
            ->assertJson(['ok' => true]);
    });

    it('returns only one popup campaign from placement service', function () {
        config(['features.self_service_ads' => true]);

        $seller = User::factory()->create();

        foreach ([100, 50] as $priority) {
            makeSelfServiceCampaign($seller, [
                'placement'       => 'popup',
                'priority'        => $priority,
                'payment_status'  => 'paid',
                'approval_status' => 'approved',
                'status'          => 'active',
                'starts_at'       => now()->subDay(),
                'ends_at'         => now()->addDays(6),
            ]);
        }

        $resolved = app(AdCampaignService::class)->getForPlacement('popup');

        expect($resolved)->not->toBeNull();
    });
});
