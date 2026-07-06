<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * إرسال رسالة SMS لأي رقم
     */
    public function sendOtp(string $phone, string $otpCode): bool
    {
        $message = "أهلاً بك في نايلكس. كود التفعيل الخاص بك هو: {$otpCode}";
        
        // 🟢 لو إحنا على الـ Localhost (التطوير)، منسجّلش الكود نفسه في اللوج
        //    (أمان: كود الـ OTP يجب ألا يظهر أبداً في السجلات).
        if (app()->environment('local')) {
            Log::info("OTP SMS dispatched (local simulation) to phone {$phone}.");
            return true;
        }

        // 🟢 كود الربط الحقيقي مع شركة الـ SMS (كمثال: SMS Misr أو أي شركة API)
        try {
            $response = Http::post('https://api.smsprovider.com/v1/send', [
                'username' => env('SMS_USERNAME'),
                'password' => env('SMS_PASSWORD'),
                'sender'   => env('SMS_SENDER_NAME', 'Nilex'),
                'mobile'   => $phone,
                'message'  => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("فشل إرسال SMS للرقم {$phone}: " . $e->getMessage());
            return false;
        }
    }
}