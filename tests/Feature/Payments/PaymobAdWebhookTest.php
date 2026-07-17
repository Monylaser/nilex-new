<?php

/**
 * Paymob ad-campaign webhook — HMAC, mark-paid, and idempotency coverage.
 * Audit item #8 (critical money path, ads parallel).
 */

use App\Models\AdCampaign;
use App\Models\PaymentAttempt;
use App\Models\User;
use App\Services\PaymobAdWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.paymob.hmac_secret' => 'test-hmac-secret',
        'features.self_service_ads'   => true,
    ]);
    Notification::fake();
});

function adWebhookPayload(PaymentAttempt $attempt, AdCampaign $campaign, array $overrides = []): array
{
    $amountCents = (int) round(((float) $attempt->amount) * 100);

    $obj = array_merge([
        'amount_cents'           => $amountCents,
        'created_at'             => '2026-07-17T10:00:00',
        'currency'               => 'EGP',
        'error_occured'          => false,
        'has_parent_transaction' => false,
        'id'                     => 'ad-paymob-txn-2001',
        'integration_id'         => 12345,
        'is_3d_secure'           => false,
        'is_auth'                => false,
        'is_capture'             => false,
        'is_refunded'            => false,
        'is_standalone_payment'  => true,
        'is_voided'              => false,
        'order'                  => [
            'id'                => $attempt->paymob_order_id ?? '8001',
            'merchant_order_id' => "nilex-ad:{$campaign->id}:{$attempt->id}",
        ],
        'owner'                  => 1,
        'pending'                => false,
        'source_data'            => [
            'pan'      => '1234',
            'sub_type' => 'MasterCard',
            'type'     => 'card',
        ],
        'success'                => true,
    ], $overrides);

    return ['obj' => $obj];
}

function signAdWebhook(array $body, ?string $secret = null): string
{
    $secret ??= (string) config('services.paymob.hmac_secret');
    $service = app(PaymobAdWebhookService::class);
    $method = (new ReflectionClass($service))->getMethod('computeHmac');
    $method->setAccessible(true);

    return $method->invoke($service, $body, $secret);
}

function postAdWebhook(array $body, ?string $hmac = null): TestResponse
{
    $hmac ??= signAdWebhook($body);

    return test()->postJson('/webhooks/paymob/ads?hmac='.urlencode($hmac), $body);
}

function makePendingAdPayment(User $seller, array $campaignOverrides = [], array $attemptOverrides = []): array
{
    $campaign = AdCampaign::create(array_merge([
        'title'           => 'حملة اختبار دفع',
        'placement'       => 'hero_top',
        'target_url'      => 'https://example.com',
        'duration_days'   => 7,
        'status'          => 'draft',
        'approval_status' => 'pending',
        'payment_status'  => 'pending',
        'seller_id'       => $seller->id,
        'created_by'      => $seller->id,
    ], $campaignOverrides));

    $attempt = PaymentAttempt::create(array_merge([
        'campaign_id'     => $campaign->id,
        'amount'          => 500.00,
        'paymob_order_id' => '8001',
        'status'          => 'pending',
    ], $attemptOverrides));

    return [$campaign, $attempt];
}

it('marks campaign paid once on valid HMAC webhook', function () {
    $seller = User::factory()->create();
    [$campaign, $attempt] = makePendingAdPayment($seller);
    $body = adWebhookPayload($attempt, $campaign);

    $response = postAdWebhook($body);

    $response->assertOk()->assertJson(['status' => 'ok']);

    expect($attempt->fresh()->status)->toBe('success')
        ->and($attempt->fresh()->paymob_transaction_id)->toBe('ad-paymob-txn-2001')
        ->and($campaign->fresh()->payment_status)->toBe('paid')
        ->and((float) $campaign->fresh()->amount_paid)->toBe(500.00)
        ->and($campaign->fresh()->paid_at)->not->toBeNull();
});

it('rejects invalid HMAC without marking campaign paid', function () {
    $seller = User::factory()->create();
    [$campaign, $attempt] = makePendingAdPayment($seller);
    $body = adWebhookPayload($attempt, $campaign);

    $response = test()->postJson('/webhooks/paymob/ads?hmac=bad-sig', $body);

    $response->assertStatus(400)->assertJson(['error' => 'Invalid signature']);

    expect($attempt->fresh()->status)->toBe('pending')
        ->and($campaign->fresh()->payment_status)->toBe('pending');
});

it('rejects missing HMAC without marking campaign paid', function () {
    $seller = User::factory()->create();
    [$campaign, $attempt] = makePendingAdPayment($seller);
    $body = adWebhookPayload($attempt, $campaign);

    $response = test()->postJson('/webhooks/paymob/ads', $body);

    $response->assertStatus(400)->assertJson(['error' => 'Invalid signature']);

    expect($campaign->fresh()->payment_status)->toBe('pending');
});

it('marks campaign paid only once when the same webhook is delivered twice', function () {
    $seller = User::factory()->create();
    [$campaign, $attempt] = makePendingAdPayment($seller);
    $body = adWebhookPayload($attempt, $campaign);

    $first = postAdWebhook($body);
    $second = postAdWebhook($body);

    $first->assertOk()->assertJson(['status' => 'ok']);
    $second->assertOk()->assertJson(['status' => 'already_processed']);

    expect($attempt->fresh()->status)->toBe('success')
        ->and($campaign->fresh()->payment_status)->toBe('paid')
        ->and(PaymentAttempt::query()->where('campaign_id', $campaign->id)->where('status', 'success')->count())->toBe(1);
});

it('ignores success=false and marks attempt failed without setting paid', function () {
    $seller = User::factory()->create();
    [$campaign, $attempt] = makePendingAdPayment($seller);
    $body = adWebhookPayload($attempt, $campaign, [
        'success' => false,
        'pending' => false,
    ]);

    $response = postAdWebhook($body);

    $response->assertOk()->assertJson(['status' => 'ignored']);

    expect($attempt->fresh()->status)->toBe('failed')
        ->and($campaign->fresh()->payment_status)->toBe('failed');
});

it('rejects amount mismatch against the stored payment attempt amount', function () {
    $seller = User::factory()->create();
    [$campaign, $attempt] = makePendingAdPayment($seller);
    $body = adWebhookPayload($attempt, $campaign, [
        'amount_cents' => 1,
    ]);

    $response = postAdWebhook($body);

    $response->assertStatus(400)->assertJson(['error' => 'Amount mismatch']);

    expect($attempt->fresh()->status)->toBe('pending')
        ->and($campaign->fresh()->payment_status)->toBe('pending');
});

it('returns 404 when payment attempt cannot be resolved', function () {
    $body = [
        'obj' => [
            'amount_cents'           => 50000,
            'created_at'             => '2026-07-17T10:00:00',
            'currency'               => 'EGP',
            'error_occured'          => false,
            'has_parent_transaction' => false,
            'id'                     => 'orphan-ad-txn',
            'integration_id'         => 1,
            'is_3d_secure'           => false,
            'is_auth'                => false,
            'is_capture'             => false,
            'is_refunded'            => false,
            'is_standalone_payment'  => true,
            'is_voided'              => false,
            'order'                  => [
                'id'                => 'missing',
                'merchant_order_id' => 'nilex-ad:999999:888888',
            ],
            'owner'                  => 1,
            'pending'                => false,
            'source_data'            => ['pan' => '1', 'sub_type' => 'Visa', 'type' => 'card'],
            'success'                => true,
        ],
    ];

    $response = postAdWebhook($body);

    $response->assertNotFound()->assertJson(['error' => 'Payment attempt not found']);
});

it('does not crash when seller account is anonymized', function () {
    $seller = User::factory()->create(['anonymized_at' => now()]);
    [$campaign, $attempt] = makePendingAdPayment($seller);
    $body = adWebhookPayload($attempt, $campaign);

    $response = postAdWebhook($body);

    $response->assertOk()->assertJson(['status' => 'ok']);

    expect($campaign->fresh()->payment_status)->toBe('paid');
});
