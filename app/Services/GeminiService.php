<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // السطر ده هينور دلوقتي لأننا استخدمناه تحت

class GeminiService
{
    protected string $apiKey;

    // استخدمنا v1beta/gemini-pro لأنه "الجوكر" المضمون في منطقتنا حالياً
    protected string $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    public function __construct()
    {
        /**
         * ⚠️ تنبيه سنيور:
         * أنا سحبت المفتاح هنا بطريقة مباشرة جداً.
         * تأكد إن GEMINI_API_KEY موجود في الـ .env وقيمته هي اللي بتبدأ بـ AIza وآخره z2kl
         */
        $this->apiKey = env('GEMINI_API_KEY') ?? '';
    }

    public function generateListingDescription($title, $category, $price, $location)
    {
        // لو لارفيل مش شايف المفتاح من الـ .env، هنستخدم المفتاح اللي آخره z2kl يدويًا هنا كخطة بديلة
        $finalKey = !empty($this->apiKey) ? $this->apiKey : 'AIzaSyAU57Twvzk_qEz4p5h71QDhSMfCpzFz2kI';

        if (empty($finalKey)) {
            return "❌ خطأ: مفتاح الـ API غير موجود. تأكد من ملف .env";
        }

        // تحسين البرومبت ليعطي أفضل نتيجة في Nilex Platform
        $prompt = "أنت مساعد ذكي لمنصة إعلانات مبوبة اسمها (Nilex Platform).
        اكتب وصفاً إعلانياً احترافياً وجذاباً جداً للإعلان التالي:
        العنوان: {$title}
        القسم: {$category}
        السعر: {$price}
        الموقع: {$location}

        متطلبات الوصف:
        - ابدأ بجملة تخطف الأنظار.
        - استخدم نقاط HTML (<ul><li>) للمميزات.
        - أضف لمسة تسويقية مصرية جذابة.";

        try {
            // إرسال الطلب
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->endpoint}?key={$finalKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            // لو النجاح حليفنا
            if ($response->successful()) {
                $result = $response->json('candidates.0.content.parts.0.text');
                if ($result) {
                    Log::info("Gemini Success: Description generated for {$title}");
                    return $result;
                }
            }

            // هنا الـ Log اللي كان باهت هينور وهيسجل الكارثة بالظبط
            Log::error("Gemini API Error Detail: " . $response->body());

            // استخراج رسالة الخطأ من جوجل بشكل احترافي
            $googleError = $response->json('error.message') ?? 'Internal Server Error';
            return "❌ جوجل رد بـ: " . $googleError . " (تأكد من صلاحية المفتاح والموديل)";

        } catch (\Exception $e) {
            Log::error("Gemini Critical Exception: " . $e->getMessage());
            return "❌ خطأ تقني في الاتصال: " . $e->getMessage();
        }
    }
}
