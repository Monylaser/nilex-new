<?php

/**
 * Paymob points webhook — HMAC, fulfillment, and idempotency coverage.
 * Audit item #8 (critical money path).
 */

use App\Models\PointTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaymobWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.paymob.hmac_secret' => 'test-hmac-secret']);
    Notification::fake();
});

function pointsWebhookPayload(Transaction $transaction, array $overrides = []): array
{
    $amountCents = (int) round(((float) $transaction->amount) * 100);

    $obj = array_merge([
        'amount_cents'           => $amountCents,
        'created_at'             => '2026-07-17T10:00:00',
        'currency'               => 'EGP',
        'error_occured'          => false,
        'has_parent_transaction' => false,
        'id'                     => 'paymob-txn-1001',
        'integration_id'         => 12345,
        'is_3d_secure'           => false,
        'is_auth'                => false,
        'is_capture'             => false,
        'is_refunded'            => false,
        'is_standalone_payment'  => true,
        'is_voided'              => false,
        'order'                  => [
            'id'                => $transaction->paymob_order_id ?? '9001',
            'merchant_order_id' => 'nilex:'.$transaction->id,
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

function signPointsWebhook(array $body, ?string $secret = null): string
{
    $secret ??= (string) config('services.paymob.hmac_secret');
    $service = app(PaymobWebhookService::class);
    $method = (new ReflectionClass($service))->getMethod('computeHmac');
    $method->setAccessible(true);

    return $method->invoke($service, $body, $secret);
}

function postPointsWebhook(array $body, ?string $hmac = null): TestResponse
{
    $hmac ??= signPointsWebhook($body);

    return test()->postJson('/payment/webhook?hmac='.urlencode($hmac), $body);
}

function makePendingPointsTransaction(User $user, array $overrides = []): Transaction
{
    $plan = createTierPlan(\App\Models\PlanEntitlement::TIER_GROWTH);

    return Transaction::query()->create(array_merge([
        'user_id'         => $user->id,
        'plan_id'         => $plan->id,
        'amount'          => $plan->price,
        'status'          => 'pending',
        'is_processed'    => false,
        'paymob_order_id' => '9001',
    ], $overrides));
}

it('credits plan points and records PointTransaction on valid HMAC webhook', function () {
    $user = User::factory()->create(['points' => 20, 'points_balance' => 20]);
    $transaction = makePendingPointsTransaction($user);
    $planPoints = (int) $transaction->plan->points;

    $body = pointsWebhookPayload($transaction);
    $response = postPointsWebhook($body);

    $response->assertOk()->assertJson(['status' => 'ok']);

    $user->refresh();
    $transaction->refresh();

    expect($user->points)->toBe(20 + $planPoints)
        ->and($user->points_balance)->toBe(20 + $planPoints)
        ->and($transaction->is_processed)->toBeTrue()
        ->and($transaction->status)->toBe('completed')
        ->and($transaction->gateway_reference)->toBe('paymob-txn-1001');

    $pt = PointTransaction::query()->where('user_id', $user->id)->latest('id')->first();

    expect($pt)->not->toBeNull()
        ->and($pt->amount)->toBe($planPoints)
        ->and($pt->description)->toContain('paymob:paymob-txn-1001');
});

it('rejects invalid HMAC without crediting points', function () {
    $user = User::factory()->create(['points' => 20, 'points_balance' => 20]);
    $transaction = makePendingPointsTransaction($user);
    $body = pointsWebhookPayload($transaction);

    $response = test()->postJson('/payment/webhook?hmac=totally-invalid', $body);

    $response->assertStatus(400)->assertJson(['error' => 'Invalid signature']);

    expect($user->fresh()->points)->toBe(20)
        ->and($transaction->fresh()->is_processed)->toBeFalse()
        ->and(PointTransaction::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('rejects missing HMAC without crediting points', function () {
    $user = User::factory()->create(['points' => 20, 'points_balance' => 20]);
    $transaction = makePendingPointsTransaction($user);
    $body = pointsWebhookPayload($transaction);

    $response = test()->postJson('/payment/webhook', $body);

    $response->assertStatus(400)->assertJson(['error' => 'Invalid signature']);

    expect($user->fresh()->points)->toBe(20)
        ->and(PointTransaction::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('credits points only once when the same webhook is delivered twice', function () {
    $user = User::factory()->create(['points' => 0, 'points_balance' => 0]);
    $transaction = makePendingPointsTransaction($user);
    $planPoints = (int) $transaction->plan->points;
    $body = pointsWebhookPayload($transaction);

    $first = postPointsWebhook($body);
    $second = postPointsWebhook($body);

    $first->assertOk()->assertJson(['status' => 'ok']);
    $second->assertOk()->assertJson(['status' => 'already_processed']);

    expect($user->fresh()->points)->toBe($planPoints)
        ->and(PointTransaction::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and($transaction->fresh()->is_processed)->toBeTrue();
});

it('ignores success=false webhooks without crediting points', function () {
    $user = User::factory()->create(['points' => 15, 'points_balance' => 15]);
    $transaction = makePendingPointsTransaction($user);
    $body = pointsWebhookPayload($transaction, [
        'success' => false,
        'pending' => false,
    ]);

    $response = postPointsWebhook($body);

    $response->assertOk()->assertJson(['status' => 'ignored']);

    expect($user->fresh()->points)->toBe(15)
        ->and($transaction->fresh()->is_processed)->toBeFalse()
        ->and(PointTransaction::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('rejects amount mismatch against the stored transaction amount (not plan catalog lookup)', function () {
    $user = User::factory()->create(['points' => 10, 'points_balance' => 10]);
    $transaction = makePendingPointsTransaction($user);
    $body = pointsWebhookPayload($transaction, [
        'amount_cents' => 1, // does not match transaction.amount * 100
    ]);

    $response = postPointsWebhook($body);

    $response->assertStatus(400)->assertJson(['error' => 'Amount mismatch']);

    expect($user->fresh()->points)->toBe(10)
        ->and($transaction->fresh()->is_processed)->toBeFalse()
        ->and(PointTransaction::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('does not crash when fulfilling for an anonymized (soft-deleted) user', function () {
    $user = User::factory()->create([
        'points'         => 5,
        'points_balance' => 5,
        'anonymized_at'  => now(),
    ]);
    $transaction = makePendingPointsTransaction($user);
    $planPoints = (int) $transaction->plan->points;
    $body = pointsWebhookPayload($transaction);

    $response = postPointsWebhook($body);

    $response->assertOk()->assertJson(['status' => 'ok']);

    expect($user->fresh()->points)->toBe(5 + $planPoints)
        ->and($transaction->fresh()->is_processed)->toBeTrue();
});

it('returns 404 when no local transaction matches the webhook order', function () {
    $body = [
        'obj' => [
            'amount_cents'           => 9900,
            'created_at'             => '2026-07-17T10:00:00',
            'currency'               => 'EGP',
            'error_occured'          => false,
            'has_parent_transaction' => false,
            'id'                     => 'orphan-txn',
            'integration_id'         => 1,
            'is_3d_secure'           => false,
            'is_auth'                => false,
            'is_capture'             => false,
            'is_refunded'            => false,
            'is_standalone_payment'  => true,
            'is_voided'              => false,
            'order'                  => [
                'id'                => 'missing-order',
                'merchant_order_id' => 'nilex:999999',
            ],
            'owner'                  => 1,
            'pending'                => false,
            'source_data'            => ['pan' => '1', 'sub_type' => 'Visa', 'type' => 'card'],
            'success'                => true,
        ],
    ];

    $response = postPointsWebhook($body);

    $response->assertNotFound()->assertJson(['error' => 'Transaction not found']);
});

it('treats status=completed as already fulfilled even if is_processed was false', function () {
    $user = User::factory()->create(['points' => 40, 'points_balance' => 40]);
    $transaction = makePendingPointsTransaction($user, [
        'status'       => 'completed',
        'is_processed' => false,
    ]);
    $body = pointsWebhookPayload($transaction);

    $response = postPointsWebhook($body);

    $response->assertOk()->assertJson(['status' => 'already_processed']);

    expect($user->fresh()->points)->toBe(40)
        ->and(PointTransaction::query()->where('user_id', $user->id)->count())->toBe(0);
});
