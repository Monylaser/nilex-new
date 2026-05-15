<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaymobService
{
    protected string $baseUrl = 'https://egypt.paymob.com/api';

    // 1. الحصول على Token المصادقة
    public function getAuthToken()
    {
        $response = Http::post("{$this->baseUrl}/auth/login", [
            'api_key' => env('PAYMOB_API_KEY'),
        ]);

        return $response->json('token');
    }

    // 2. تسجيل الطلب في Paymob
    public function createOrder($token, $amount, $currency = 'EGP')
    {
        $response = Http::post("{$this->baseUrl}/ecommerce/orders", [
            'auth_token' => $token,
            'delivery_needed' => 'false',
            'amount_cents' => $amount * 100, // تحويل القرش لجنيه
            'currency' => $currency,
            'items' => [],
        ]);

        return $response->json('id');
    }

    // 3. الحصول على مفتاح الدفع (Payment Key)
    public function getPaymentKey($token, $orderId, $amount, $user)
    {
        $response = Http::post("{$this->baseUrl}/ecommerce/payment_links/payment_keys", [
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
            'integration_id' => env('PAYMOB_INTEGRATION_ID'),
        ]);

        return $response->json('token');
    }
}