<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobService
{
    protected string $baseUrl = 'https://accept.paymob.com/api';

    // 1. الحصول على Token المصادقة
    public function getAuthToken()
    {
        $response = Http::post("{$this->baseUrl}/auth/tokens", [
            'api_key' => config('services.paymob.api_key'),
        ]);

        if ($this->failed($response, 'auth/tokens')) {
            return null;
        }

        return $response->json('token');
    }

    // 2. تسجيل الطلب في Paymob
    public function createOrder($token, $amount, $currency = 'EGP', ?string $merchantOrderId = null)
    {
        $response = Http::post("{$this->baseUrl}/ecommerce/orders", [
            'auth_token'         => $token,
            'delivery_needed'    => 'false',
            'amount_cents'       => $amount * 100,
            'currency'           => $currency,
            'merchant_order_id'  => $merchantOrderId,
            'items'              => [],
        ]);

        if ($this->failed($response, 'ecommerce/orders')) {
            return null;
        }

        return $response->json('id');
    }

    // 3. الحصول على مفتاح الدفع (Payment Key)
    public function getPaymentKey($token, $orderId, $amount, $user)
    {
        $response = Http::post("{$this->baseUrl}/acceptance/payment_keys", [
            'auth_token' => $token,
            'amount_cents' => $amount * 100,
            'expiration' => 3600,
            'order_id' => $orderId,
            'billing_data' => [
                'first_name' => explode(' ', $user->name)[0],
                'last_name'  => explode(' ', $user->name)[1] ?? 'User',
                'email'      => $user->email,
                'phone_number' => $user->phone ?? '01000000000',
                'apartment'  => 'NA', 'floor' => 'NA', 'street' => 'NA', 'building' => 'NA',
                'shipping_method' => 'NA', 'postal_code' => 'NA', 'city' => 'NA', 'country' => 'EG', 'state' => 'NA'
            ],
            'currency' => 'EGP',
            'integration_id' => config('services.paymob.integration_id'),
        ]);

        if ($this->failed($response, 'acceptance/payment_keys')) {
            return null;
        }

        return $response->json('token');
    }

    /**
     * Logs the full status code + response body when a Paymob call fails,
     * so the real cause is never hidden behind a generic message.
     */
    protected function failed(Response $response, string $endpoint): bool
    {
        if ($response->failed()) {
            Log::error('Paymob API call failed.', [
                'endpoint' => $endpoint,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            return true;
        }

        return false;
    }
}
