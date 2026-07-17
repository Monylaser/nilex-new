<?php

namespace App\Services;

use App\Models\AdCampaign;
use App\Models\PaymentAttempt;
use App\Notifications\AdCampaignPaymentReceivedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymobAdWebhookService
{
    public function __construct(
        protected AdCampaignPaymentService $paymentService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        if (! config('features.self_service_ads', false)) {
            Log::info('Paymob ad webhook: feature disabled', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Feature disabled'], 404);
        }

        $hmacSecret = (string) config('services.paymob.hmac_secret', '');

        if ($hmacSecret === '') {
            Log::error('Paymob ad webhook: HMAC secret is not configured', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Payment gateway misconfigured'], 500);
        }

        $receivedHmac = (string) $request->query('hmac', '');

        if (! $this->verifyHmac($request->all(), $receivedHmac, $hmacSecret)) {
            Log::warning('Paymob ad webhook: invalid HMAC signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $payload = $request->input('obj', []);

        if ($payload === [] || ! is_array($payload)) {
            Log::warning('Paymob ad webhook: missing transaction payload', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $transactionId = (string) data_get($payload, 'id', '');

        if ($transactionId !== '' && $this->isTransactionAlreadyProcessed($transactionId)) {
            Log::info('Paymob ad webhook: duplicate transaction ignored', [
                'paymob_transaction_id' => $transactionId,
                'ip'                    => $request->ip(),
            ]);

            return response()->json(['status' => 'already_processed']);
        }

        $attempt = $this->findPaymentAttempt($payload);

        if (! $attempt) {
            Log::warning('Paymob ad webhook: payment attempt not found', [
                'order_id'          => data_get($payload, 'order.id'),
                'merchant_order_id' => data_get($payload, 'order.merchant_order_id'),
                'transaction_id'    => $transactionId,
                'ip'                => $request->ip(),
            ]);

            return response()->json(['error' => 'Payment attempt not found'], 404);
        }

        $campaign = $attempt->campaign;

        if (! $campaign) {
            Log::warning('Paymob ad webhook: campaign not found for attempt', [
                'attempt_id' => $attempt->id,
                'ip'         => $request->ip(),
            ]);

            return response()->json(['error' => 'Campaign not found'], 404);
        }

        if (! $this->verifyCampaignOwnership($payload, $campaign, $attempt)) {
            Log::warning('Paymob ad webhook: campaign ownership verification failed', [
                'attempt_id'  => $attempt->id,
                'campaign_id' => $campaign->id,
                'ip'          => $request->ip(),
            ]);

            return response()->json(['error' => 'Campaign ownership mismatch'], 400);
        }

        $success = filter_var($payload['success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $pending = filter_var($payload['pending'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if (! $success || $pending) {
            if (! $success && ! $pending) {
                $this->markFailed($attempt, $campaign, $payload);
            }

            Log::info('Paymob ad webhook: non-successful payment ignored', [
                'attempt_id'  => $attempt->id,
                'campaign_id' => $campaign->id,
                'success'     => $success,
                'pending'     => $pending,
                'ip'          => $request->ip(),
            ]);

            return response()->json(['status' => 'ignored']);
        }

        $amountCents = (int) ($payload['amount_cents'] ?? 0);
        $expectedCents = (int) round(((float) $attempt->amount) * 100);

        if ($amountCents !== $expectedCents) {
            Log::error('Paymob ad webhook: amount mismatch', [
                'attempt_id'     => $attempt->id,
                'campaign_id'    => $campaign->id,
                'expected_cents' => $expectedCents,
                'received_cents' => $amountCents,
                'ip'             => $request->ip(),
            ]);

            return response()->json(['error' => 'Amount mismatch'], 400);
        }

        try {
            $fulfilled = $this->fulfill($attempt, $campaign, $payload);

            if (! $fulfilled) {
                Log::info('Paymob ad webhook: duplicate callback detected during fulfillment lock', [
                    'attempt_id'  => $attempt->id,
                    'campaign_id' => $campaign->id,
                    'ip'          => $request->ip(),
                ]);

                return response()->json(['status' => 'already_processed']);
            }

            Log::info('Paymob ad webhook: payment fulfilled successfully', [
                'attempt_id'            => $attempt->id,
                'campaign_id'           => $campaign->id,
                'paymob_transaction_id' => $transactionId,
            ]);

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $exception) {
            Log::error('Paymob ad webhook: fulfillment failed', [
                'attempt_id'  => $attempt->id,
                'campaign_id' => $campaign->id,
                'message'     => $exception->getMessage(),
                'ip'          => $request->ip(),
            ]);

            return response()->json(['error' => 'Fulfillment failed'], 500);
        }
    }

    public function verifyHmac(array $data, string $receivedHmac, string $secret): bool
    {
        if ($receivedHmac === '') {
            return false;
        }

        $computed = $this->computeHmac($data, $secret);

        return hash_equals($computed, $receivedHmac);
    }

    protected function findPaymentAttempt(array $payload): ?PaymentAttempt
    {
        $merchantOrderId = (string) data_get($payload, 'order.merchant_order_id', '');
        $paymobOrderId   = data_get($payload, 'order.id');
        $transactionId   = (string) data_get($payload, 'id', '');

        if ($merchantOrderId !== '') {
            $parsed = $this->paymentService->parseMerchantOrderId($merchantOrderId);

            if ($parsed !== null) {
                $attempt = PaymentAttempt::query()
                    ->with('campaign')
                    ->find($parsed['attempt_id']);

                if ($attempt && (int) $attempt->campaign_id === $parsed['campaign_id']) {
                    return $attempt;
                }
            }
        }

        if ($paymobOrderId !== null && $paymobOrderId !== '') {
            $attempt = PaymentAttempt::query()
                ->with('campaign')
                ->where('paymob_order_id', (string) $paymobOrderId)
                ->latest('id')
                ->first();

            if ($attempt) {
                return $attempt;
            }
        }

        if ($transactionId !== '') {
            return PaymentAttempt::query()
                ->with('campaign')
                ->where('paymob_transaction_id', $transactionId)
                ->first();
        }

        return null;
    }

    protected function verifyCampaignOwnership(array $payload, AdCampaign $campaign, PaymentAttempt $attempt): bool
    {
        $merchantOrderId = (string) data_get($payload, 'order.merchant_order_id', '');

        if ($merchantOrderId !== '') {
            $parsed = $this->paymentService->parseMerchantOrderId($merchantOrderId);

            if ($parsed === null) {
                return false;
            }

            if ((int) $parsed['campaign_id'] !== (int) $campaign->id) {
                return false;
            }

            if ((int) $parsed['attempt_id'] !== (int) $attempt->id) {
                return false;
            }
        }

        if ((int) $attempt->campaign_id !== (int) $campaign->id) {
            return false;
        }

        if ($campaign->seller_id === null) {
            return false;
        }

        return true;
    }

    protected function isTransactionAlreadyProcessed(string $transactionId): bool
    {
        if (PaymentAttempt::query()->where('paymob_transaction_id', $transactionId)->where('status', 'success')->exists()) {
            return true;
        }

        return AdCampaign::query()
            ->where('paymob_transaction_id', $transactionId)
            ->where('payment_status', 'paid')
            ->exists();
    }

    protected function fulfill(PaymentAttempt $attempt, AdCampaign $campaign, array $payload): bool
    {
        $transactionId = (string) data_get($payload, 'id', '');

        return DB::transaction(function () use ($attempt, $campaign, $payload, $transactionId) {
            /** @var PaymentAttempt|null $lockedAttempt */
            $lockedAttempt = PaymentAttempt::query()
                ->lockForUpdate()
                ->with('campaign')
                ->find($attempt->id);

            if (! $lockedAttempt) {
                throw new \RuntimeException('Payment attempt record disappeared during fulfillment.');
            }

            if ($lockedAttempt->isSuccessful()) {
                return false;
            }

            if ($transactionId !== '' && $this->isTransactionAlreadyProcessed($transactionId)) {
                return false;
            }

            /** @var AdCampaign|null $lockedCampaign */
            $lockedCampaign = AdCampaign::query()
                ->lockForUpdate()
                ->find($campaign->id);

            if (! $lockedCampaign) {
                throw new \RuntimeException('Campaign record disappeared during fulfillment.');
            }

            if ($lockedCampaign->payment_status === 'paid') {
                return false;
            }

            $amountPaid = round(((int) ($payload['amount_cents'] ?? 0)) / 100, 2);

            $lockedAttempt->update([
                'status'                  => 'success',
                'paymob_transaction_id'   => $transactionId !== '' ? $transactionId : $lockedAttempt->paymob_transaction_id,
                'paymob_order_id'         => (string) data_get($payload, 'order.id', $lockedAttempt->paymob_order_id),
                'payload'                 => $payload,
            ]);

            $lockedCampaign->update([
                'payment_status'          => 'paid',
                'paymob_order_id'         => (string) data_get($payload, 'order.id', $lockedCampaign->paymob_order_id),
                'paymob_transaction_id'   => $transactionId !== '' ? $transactionId : $lockedCampaign->paymob_transaction_id,
                'amount_paid'             => $amountPaid,
                'paid_at'                 => now(),
            ]);

            $seller = $lockedCampaign->seller;

            DB::afterCommit(function () use ($seller, $lockedCampaign) {
                if ($seller !== null) {
                    $seller->notify(new AdCampaignPaymentReceivedNotification($lockedCampaign->fresh()));
                }
            });

            return true;
        });
    }

    protected function markFailed(PaymentAttempt $attempt, AdCampaign $campaign, array $payload): void
    {
        DB::transaction(function () use ($attempt, $campaign, $payload) {
            $lockedAttempt = PaymentAttempt::query()
                ->lockForUpdate()
                ->find($attempt->id);

            if (! $lockedAttempt || $lockedAttempt->isSuccessful()) {
                return;
            }

            $lockedAttempt->update([
                'status'  => 'failed',
                'payload' => $payload,
            ]);

            $lockedCampaign = AdCampaign::query()
                ->lockForUpdate()
                ->find($campaign->id);

            if ($lockedCampaign && $lockedCampaign->payment_status !== 'paid') {
                $lockedCampaign->update(['payment_status' => 'failed']);
            }
        });
    }

    /**
     * @see https://developers.paymob.com/egypt/docs/webhook-notification
     */
    protected function computeHmac(array $data, string $secret): string
    {
        $obj = $data['obj'] ?? [];

        $fields = [
            'amount_cents'             => $obj['amount_cents']                          ?? '',
            'created_at'               => $obj['created_at']                            ?? '',
            'currency'                 => $obj['currency']                              ?? '',
            'error_occured'            => $this->boolStr($obj['error_occured']          ?? false),
            'has_parent_transaction'   => $this->boolStr($obj['has_parent_transaction'] ?? false),
            'id'                       => $obj['id']                                    ?? '',
            'integration_id'           => $obj['integration_id']                        ?? '',
            'is_3d_secure'             => $this->boolStr($obj['is_3d_secure']           ?? false),
            'is_auth'                  => $this->boolStr($obj['is_auth']                ?? false),
            'is_capture'               => $this->boolStr($obj['is_capture']             ?? false),
            'is_refunded'              => $this->boolStr($obj['is_refunded']            ?? false),
            'is_standalone_payment'    => $this->boolStr($obj['is_standalone_payment']  ?? false),
            'is_voided'                => $this->boolStr($obj['is_voided']              ?? false),
            'order_id'                 => $obj['order']['id']                           ?? '',
            'owner'                    => $obj['owner']                                 ?? '',
            'pending'                  => $this->boolStr($obj['pending']                ?? false),
            'source_data_pan'          => $obj['source_data']['pan']                    ?? '',
            'source_data_sub_type'     => $obj['source_data']['sub_type']               ?? '',
            'source_data_type'         => $obj['source_data']['type']                   ?? '',
            'success'                  => $this->boolStr($obj['success']                ?? false),
        ];

        return hash_hmac('sha512', implode('', $fields), $secret);
    }

    protected function boolStr(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }
}
