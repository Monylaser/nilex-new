<?php

namespace App\Services;

use App\Events\PointsPurchased;
use App\Models\Transaction;
use App\Notifications\PointsPurchasedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymobWebhookService
{
    public function __construct(
        protected PointService $pointService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $hmacSecret = (string) config('services.paymob.hmac_secret', '');

        if ($hmacSecret === '') {
            Log::error('Paymob callback: HMAC secret is not configured');
            return response()->json(['error' => 'Payment gateway misconfigured'], 500);
        }

        $receivedHmac = (string) $request->query('hmac', '');

        if (! $this->verifyHmac($request->all(), $receivedHmac, $hmacSecret)) {
            Log::warning('Paymob callback: invalid HMAC signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $payload = $request->input('obj', []);

        if ($payload === [] || ! is_array($payload)) {
            Log::warning('Paymob callback: missing transaction payload', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $transaction = $this->findTransaction($payload);

        if (! $transaction) {
            Log::warning('Paymob callback: transaction not found', [
                'order_id'           => data_get($payload, 'order.id'),
                'merchant_order_id'  => data_get($payload, 'order.merchant_order_id'),
                'gateway_reference'  => data_get($payload, 'id'),
            ]);

            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $success = filter_var($payload['success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $pending = filter_var($payload['pending'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if (! $success || $pending) {
            Log::info('Paymob callback: non-successful payment ignored', [
                'transaction_id' => $transaction->id,
                'success'        => $success,
                'pending'        => $pending,
            ]);

            return response()->json(['status' => 'ignored']);
        }

        if ($transaction->is_processed) {
            Log::info('Paymob callback: duplicate callback for already processed transaction', [
                'transaction_id' => $transaction->id,
                'user_id'        => $transaction->user_id,
            ]);

            return response()->json(['status' => 'already_processed']);
        }

        $amountCents = (int) ($payload['amount_cents'] ?? 0);
        $expectedCents = (int) round(((float) $transaction->amount) * 100);

        if ($amountCents !== $expectedCents) {
            Log::error('Paymob callback: amount mismatch', [
                'transaction_id' => $transaction->id,
                'expected_cents' => $expectedCents,
                'received_cents' => $amountCents,
            ]);

            return response()->json(['error' => 'Amount mismatch'], 400);
        }

        try {
            $fulfilled = $this->fulfill($transaction, $payload);

            if (! $fulfilled) {
                Log::info('Paymob callback: duplicate callback detected during fulfillment lock', [
                    'transaction_id' => $transaction->id,
                ]);

                return response()->json(['status' => 'already_processed']);
            }

            Log::info('Paymob callback: payment fulfilled successfully', [
                'transaction_id'     => $transaction->id,
                'user_id'            => $transaction->user_id,
                'gateway_reference'  => data_get($payload, 'id'),
            ]);

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $exception) {
            Log::error('Paymob callback: fulfillment failed', [
                'transaction_id' => $transaction->id,
                'message'        => $exception->getMessage(),
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

    public function findTransaction(array $payload): ?Transaction
    {
        $merchantOrderId = (string) data_get($payload, 'order.merchant_order_id', '');
        $paymobOrderId   = data_get($payload, 'order.id');
        $gatewayReference = data_get($payload, 'id');

        if ($merchantOrderId !== '' && preg_match('/^nilex:(\d+)$/', $merchantOrderId, $matches)) {
            $transaction = Transaction::query()
                ->with(['user', 'plan'])
                ->find((int) $matches[1]);

            if ($transaction) {
                return $transaction;
            }
        }

        if ($paymobOrderId !== null && $paymobOrderId !== '') {
            $transaction = Transaction::query()
                ->with(['user', 'plan'])
                ->where('paymob_order_id', (string) $paymobOrderId)
                ->first();

            if ($transaction) {
                return $transaction;
            }
        }

        if ($gatewayReference !== null && $gatewayReference !== '') {
            return Transaction::query()
                ->with(['user', 'plan'])
                ->where('gateway_reference', (string) $gatewayReference)
                ->first();
        }

        return null;
    }

    protected function fulfill(Transaction $transaction, array $payload): bool
    {
        return DB::transaction(function () use ($transaction, $payload) {
            /** @var Transaction|null $lockedTransaction */
            $lockedTransaction = Transaction::query()
                ->lockForUpdate()
                ->with(['user', 'plan'])
                ->find($transaction->id);

            if (! $lockedTransaction) {
                throw new \RuntimeException('Transaction record disappeared during fulfillment.');
            }

            if ($lockedTransaction->is_processed) {
                return false;
            }

            $plan = $lockedTransaction->plan;

            if (! $plan) {
                throw new \RuntimeException('Point plan not found for transaction.');
            }

            $user = $lockedTransaction->user;

            if (! $user) {
                throw new \RuntimeException('User not found for transaction.');
            }

            $points = (int) $plan->points;
            $paymobReference = (string) data_get($payload, 'id', '');

            $this->pointService->credit(
                $user,
                $points,
                "شراء {$points} نقطة — خطة {$plan->name_ar} — paymob:{$paymobReference}",
                $lockedTransaction,
            );

            $lockedTransaction->update([
                'status'             => 'completed',
                'is_processed'       => true,
                'processed_at'       => now(),
                'gateway_reference'  => $paymobReference !== '' ? $paymobReference : $lockedTransaction->gateway_reference,
            ]);

            DB::afterCommit(function () use ($user, $plan, $lockedTransaction) {
                PointsPurchased::dispatch($user, $plan, $lockedTransaction);
                $user->notify(new PointsPurchasedNotification($plan, $lockedTransaction));
            });

            return true;
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
