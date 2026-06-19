<?php

namespace App\Services;

use App\Models\AdCampaign;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdCampaignPaymentService
{
    public function __construct(
        protected PaymobService $paymobService,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('features.self_service_ads', false);
    }

    public function ensureEnabled(): void
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Self-service advertising is disabled.');
        }
    }

    public function calculatePrice(string $placement, int $durationDays): float
    {
        $placements = config('ad_pricing.placements', []);
        $placementConfig = $placements[$placement] ?? null;

        if ($placementConfig === null) {
            throw new RuntimeException("Unknown ad placement: {$placement}");
        }

        $price = $placementConfig['prices'][$durationDays] ?? null;

        if ($price === null) {
            throw new RuntimeException("No price configured for placement [{$placement}] and duration [{$durationDays}] days.");
        }

        return (float) $price;
    }

    public function getAvailableDurations(): array
    {
        return array_keys(config('ad_pricing.durations', []));
    }

    public function getAvailablePlacements(): array
    {
        return array_keys(config('ad_pricing.placements', []));
    }

    public function verifyCampaignOwnership(AdCampaign $campaign, User $seller): void
    {
        if ($campaign->seller_id === null || (int) $campaign->seller_id !== (int) $seller->id) {
            throw new RuntimeException('Campaign does not belong to the authenticated seller.');
        }
    }

    public function initiatePayment(AdCampaign $campaign, User $seller, int $durationDays): string
    {
        $this->ensureEnabled();
        $this->verifyCampaignOwnership($campaign, $seller);

        if ($campaign->payment_status === 'paid') {
            throw new RuntimeException('Campaign is already paid.');
        }

        $amount = $this->calculatePrice($campaign->placement, $durationDays);

        return DB::transaction(function () use ($campaign, $seller, $amount, $durationDays) {
            $attempt = PaymentAttempt::create([
                'campaign_id' => $campaign->id,
                'amount'      => $amount,
                'status'      => 'pending',
            ]);

            $token = $this->paymobService->getAuthToken();

            if ($token === null || $token === '') {
                throw new RuntimeException('Failed to authenticate with Paymob.');
            }

            $orderId = $this->paymobService->createOrder(
                $token,
                $amount,
                merchantOrderId: $this->buildMerchantOrderId($campaign->id, $attempt->id),
            );

            if ($orderId === null || $orderId === '') {
                throw new RuntimeException('Failed to create Paymob order.');
            }

            $attempt->update([
                'paymob_order_id' => (string) $orderId,
            ]);

            $campaign->update([
                'payment_status'  => 'pending',
                'paymob_order_id' => (string) $orderId,
            ]);

            $paymentKey = $this->paymobService->getPaymentKey($token, $orderId, $amount, $seller);

            if ($paymentKey === null || $paymentKey === '') {
                throw new RuntimeException('Failed to obtain Paymob payment key.');
            }

            $iframeId = config('services.paymob.iframe_id', env('PAYMOB_IFRAME_ID'));

            return "https://egypt.paymob.com/api/acceptance/iframes/{$iframeId}?payment_token={$paymentKey}";
        });
    }

    public function buildMerchantOrderId(int $campaignId, int $attemptId): string
    {
        return "nilex-ad:{$campaignId}:{$attemptId}";
    }

    public function parseMerchantOrderId(string $merchantOrderId): ?array
    {
        if (! preg_match('/^nilex-ad:(\d+):(\d+)$/', $merchantOrderId, $matches)) {
            return null;
        }

        return [
            'campaign_id' => (int) $matches[1],
            'attempt_id'  => (int) $matches[2],
        ];
    }
}
