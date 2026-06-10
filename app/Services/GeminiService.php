<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;

    protected string $model = 'gemini-1.5-flash';

    protected string $baseEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
    }

    // ── Internal Helpers ──────────────────────────────────────────────────────

    protected function endpoint(): string
    {
        return "{$this->baseEndpoint}/{$this->model}:generateContent";
    }

    /**
     * Send a prompt to Gemini and return the raw text response.
     * Returns null on any failure (error logged with full details).
     */
    protected function callGemini(string $prompt): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('GeminiService: GEMINI_API_KEY is missing.', [
                'hint' => 'Set GEMINI_API_KEY in your .env file and in config/services.php.',
            ]);
            return null;
        }

        try {
            $response = Http::timeout(30)->post(
                $this->endpoint() . '?key=' . $this->apiKey,
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature'     => 0.7,
                        'maxOutputTokens' => 1024,
                    ],
                ]
            );

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text');

                if ($text) {
                    Log::info('GeminiService: Prompt succeeded.', [
                        'prompt_preview' => mb_substr($prompt, 0, 80),
                    ]);
                    return $text;
                }

                Log::error('GeminiService: Successful HTTP status but empty response text.', [
                    'response_body' => $response->body(),
                ]);
                return null;
            }

            Log::error('GeminiService: API request failed.', [
                'http_status'   => $response->status(),
                'error_message' => $response->json('error.message'),
                'error_code'    => $response->json('error.code'),
                'error_status'  => $response->json('error.status'),
                'response_body' => $response->body(),
            ]);
            return null;

        } catch (\Throwable $e) {
            Log::error('GeminiService: Exception during API call.', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    // ── Public Methods ────────────────────────────────────────────────────────

    /**
     * Generate a professional Arabic listing description.
     */
    public function generateDescription(
        string $title,
        string $category,
        string $price,
        string $location
    ): string {
        $prompt = <<<PROMPT
أنت مساعد ذكي لمنصة إعلانات مبوبة مصرية اسمها (Nilex).
اكتب وصفاً إعلانياً احترافياً وجذاباً للإعلان التالي:
العنوان: {$title}
القسم: {$category}
السعر: {$price} جنيه مصري
الموقع: {$location}

متطلبات الوصف:
- ابدأ بجملة تخطف الأنظار.
- استخدم نقاط HTML (<ul><li>) لعرض المميزات.
- أضف لمسة تسويقية مصرية جذابة.
- الطول: 100-200 كلمة.
PROMPT;

        return $this->callGemini($prompt)
            ?? 'تعذّر توليد الوصف. يرجى المحاولة مرة أخرى.';
    }

    /**
     * Suggest a fair Egyptian market price for a listing (returns integer EGP).
     */
    public function suggestPrice(
        string $title,
        string $category,
        string $condition = 'مستعمل'
    ): int {
        $prompt = <<<PROMPT
أنت خبير تسعير في السوق المصري.
بناءً على المعلومات التالية، اقترح سعراً عادلاً بالجنيه المصري:
المنتج: {$title}
القسم: {$category}
الحالة: {$condition}

أجب برقم فقط (بدون أي نص إضافي أو رموز). مثال: 15000
PROMPT;

        $result = $this->callGemini($prompt);

        if ($result === null) {
            return 0;
        }

        $cleaned = preg_replace('/[^0-9]/', '', trim($result));

        return (int) $cleaned ?: 0;
    }

    /**
     * Auto-categorize a listing description into one of Nilex's categories.
     */
    public function autoCategorize(string $description): string
    {
        $prompt = <<<PROMPT
أنت نظام تصنيف ذكي لمنصة إعلانات مبوبة مصرية.
بناءً على الوصف التالي، حدد القسم الأنسب من القائمة:
[عقارات، سيارات، إلكترونيات، موبايلات، أثاث، ملابس، خدمات، حيوانات، رياضة، أخرى]

الوصف: {$description}

أجب باسم القسم فقط (كلمة أو اثنتان كحد أقصى). مثال: إلكترونيات
PROMPT;

        return trim($this->callGemini($prompt) ?? 'أخرى');
    }

    /**
     * Generate full ad data from a short Arabic user input.
     *
     * Returns an array with keys: title, description, suggested_price, category.
     */
    public function generateFromInput(string $shortDescription): array
    {
        $empty = [
            'title'           => '',
            'description'     => '',
            'suggested_price' => 0,
            'category'        => 'أخرى',
        ];

        $prompt = <<<PROMPT
أنت مساعد ذكي لمنصة إعلانات مبوبة مصرية اسمها (Nilex).
المستخدم كتب وصفاً مختصراً: "{$shortDescription}"

أرجع JSON فقط (بدون أي نص خارجه) بالمفاتيح التالية:
{
    "title": "عنوان إعلان جذاب (حد أقصى 80 حرف)",
    "description": "وصف احترافي باللغة العربية (100-200 كلمة)",
    "suggested_price": "السعر المقترح بالجنيه المصري كرقم فقط",
    "category": "القسم الأنسب: عقارات، سيارات، إلكترونيات، موبايلات، أثاث، ملابس، خدمات، أخرى"
}
PROMPT;

        $result = $this->callGemini($prompt);

        if ($result === null) {
            return $empty;
        }

        // Extract JSON block even if the model wraps it in markdown
        if (preg_match('/\{[\s\S]*\}/u', $result, $matches)) {
            $data = json_decode($matches[0], true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'title'           => mb_substr(strip_tags(trim($data['title'] ?? '')), 0, 80),
                    'description'     => trim($data['description'] ?? ''),
                    'suggested_price' => (int) preg_replace('/[^0-9]/', '', (string) ($data['suggested_price'] ?? '0')),
                    'category'        => trim($data['category'] ?? 'أخرى'),
                ];
            }
        }

        Log::error('GeminiService: Failed to parse JSON from generateFromInput response.', [
            'raw_response' => $result,
        ]);

        return $empty;
    }

    /**
     * Legacy alias kept for backward compatibility with SmartAdCreator.
     */
    public function generateListingDescription(
        string $title,
        string $category,
        string $price,
        string $location
    ): string {
        return $this->generateDescription($title, $category, $price, $location);
    }
}
