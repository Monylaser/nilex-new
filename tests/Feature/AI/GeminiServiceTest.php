<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Test 7: AI Ad Generation
 *   - GeminiService reads API key from config (not env directly).
 *   - generateFromInput() returns title, description, suggested_price, category.
 *   - generateDescription() builds a listing description.
 *   - suggestPrice() returns an integer EGP price.
 *   - autoCategorize() returns the correct Arabic category string.
 *   - Missing API key is handled gracefully with a Log::error call.
 *   - Gemini 4xx errors are logged with full details and return fallback.
 */

use App\Services\GeminiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ═══════════════════════════════════════════════════════════════════════════
// Helpers
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Build a fake Gemini JSON response wrapping the given text.
 */
function geminiResponse(string $text, int $status = 200): array
{
    return [
        'candidates' => [
            [
                'content' => [
                    'parts' => [
                        ['text' => $text],
                    ],
                ],
            ],
        ],
    ];
}

// ═══════════════════════════════════════════════════════════════════════════
// Test 7 – AI Ad Generation
// ═══════════════════════════════════════════════════════════════════════════

describe('AI Ad Generation — GeminiService', function () {

    beforeEach(function () {
        // Ensure the service reads a fake key during every test
        config(['services.gemini.key' => 'AIza-test-fake-key-for-mocking']);
    });

    // ── generateFromInput ───────────────────────────────────────────────────

    it('returns title, description, suggested_price, and category from short Arabic input', function () {
        $fakeJson = json_encode([
            'title'           => 'آيفون 14 برو ماكس — حالة ممتازة',
            'description'     => 'جهاز آيفون 14 برو ماكس بحالة ممتازة، استعمال خفيف جداً، كامل الملحقات والكرتونة الأصلية.',
            'suggested_price' => '35000',
            'category'        => 'موبايلات',
        ], JSON_UNESCAPED_UNICODE);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                geminiResponse($fakeJson),
                200
            ),
        ]);

        $service = new GeminiService();
        $result  = $service->generateFromInput('عندي آيفون 14 برو ماكس هابيه استعملته بس شوية');

        expect($result['title'])->toContain('آيفون')
            ->and($result['description'])->not->toBeEmpty()
            ->and($result['suggested_price'])->toBe(35000)
            ->and($result['category'])->toBe('موبايلات');
    });

    it('sends the user input inside the Gemini prompt', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                geminiResponse(json_encode([
                    'title'           => 'شقة للإيجار في المعادي',
                    'description'     => 'شقة ممتازة في المعادي.',
                    'suggested_price' => '8000',
                    'category'        => 'عقارات',
                ], JSON_UNESCAPED_UNICODE)),
                200
            ),
        ]);

        $service = new GeminiService();
        $service->generateFromInput('شقة للإيجار في المعادي 3 غرف');

        // JSON body uses Unicode escape sequences; decode first so Arabic text is readable
        Http::assertSent(function ($request) {
            $data = json_decode($request->body(), true);
            $text = $data['contents'][0]['parts'][0]['text'] ?? '';
            return str_contains($text, 'شقة للإيجار في المعادي');
        });
    });

    it('strips HTML tags from the title and truncates to 80 chars', function () {
        $longTitle = str_repeat('أ', 90); // 90 Arabic chars
        $fakeJson  = json_encode([
            'title'           => '<b>' . $longTitle . '</b>',
            'description'     => 'وصف.',
            'suggested_price' => '1000',
            'category'        => 'أخرى',
        ], JSON_UNESCAPED_UNICODE);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(geminiResponse($fakeJson), 200),
        ]);

        $service = new GeminiService();
        $result  = $service->generateFromInput('شيء ما');

        expect(mb_strlen($result['title']))->toBeLessThanOrEqual(80);
    });

    // ── generateDescription ─────────────────────────────────────────────────

    it('generates a description containing the listing title', function () {
        $descText = 'سيارة تويوتا كامري 2020 بحالة ممتازة. <ul><li>محرك 2500cc</li><li>مالك واحد</li></ul>';

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(geminiResponse($descText), 200),
        ]);

        $service = new GeminiService();
        $result  = $service->generateDescription('تويوتا كامري 2020', 'سيارات', '250000', 'القاهرة');

        expect($result)->toContain('تويوتا');
    });

    it('returns a fallback string when Gemini returns empty text', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => '']]]]],
            ], 200),
        ]);

        $service = new GeminiService();
        $result  = $service->generateDescription('منتج', 'أخرى', '100', 'الإسكندرية');

        expect($result)->toContain('تعذّر');
    });

    // ── suggestPrice ────────────────────────────────────────────────────────

    it('returns the suggested integer price from Gemini response', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(geminiResponse('12000'), 200),
        ]);

        $service = new GeminiService();
        $price   = $service->suggestPrice('لابتوب ديل إنسبايرون', 'إلكترونيات', 'مستعمل');

        expect($price)->toBe(12000);
    });

    it('strips non-numeric characters from the price response', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(geminiResponse('السعر: 8,500 جنيه'), 200),
        ]);

        $service = new GeminiService();
        $price   = $service->suggestPrice('تابلت سامسونج', 'إلكترونيات', 'مستعمل');

        expect($price)->toBe(8500);
    });

    it('returns 0 when Gemini fails to suggest a price', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota exceeded']], 429),
        ]);

        Log::spy();

        $service = new GeminiService();
        $price   = $service->suggestPrice('شيء', 'أخرى');

        expect($price)->toBe(0);
    });

    // ── autoCategorize ──────────────────────────────────────────────────────

    it('returns the correct Arabic category for an electronics description', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(geminiResponse('إلكترونيات'), 200),
        ]);

        $service = new GeminiService();
        $cat     = $service->autoCategorize('لابتوب ماك بوك برو M3 للبيع، حالة ممتازة، معه الشاحن الأصلي.');

        expect($cat)->toBe('إلكترونيات');
    });

    it('returns أخرى when categorization fails', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'API key not valid']], 403),
        ]);

        Log::spy();

        $service = new GeminiService();
        $cat     = $service->autoCategorize('منتج غامض');

        expect($cat)->toBe('أخرى');
    });

    // ── Error handling & logging ────────────────────────────────────────────

    it('logs an error and returns empty result when API key is missing', function () {
        config(['services.gemini.key' => '']);

        Log::spy();

        $service = new GeminiService();
        $result  = $service->generateFromInput('بيع سيارة');

        Log::shouldHaveReceived('error')->once();

        expect($result['title'])->toBe('')
            ->and($result['suggested_price'])->toBe(0)
            ->and($result['category'])->toBe('أخرى');
    });

    it('logs error details when Gemini returns 403 PERMISSION_DENIED', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'code'    => 403,
                    'message' => 'API key not valid. Please pass a valid API key.',
                    'status'  => 'PERMISSION_DENIED',
                ],
            ], 403),
        ]);

        Log::spy();

        $service = new GeminiService();
        $result  = $service->generateFromInput('شقة للإيجار في القاهرة');

        Log::shouldHaveReceived('error')->once();

        expect($result)->toBe([
            'title'           => '',
            'description'     => '',
            'suggested_price' => 0,
            'category'        => 'أخرى',
        ]);
    });

    it('logs exception details when an HTTP connection error occurs', function () {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('cURL error 6: Could not resolve host');
        });

        Log::spy();

        $service = new GeminiService();
        $result  = $service->generateFromInput('موبايل للبيع');

        Log::shouldHaveReceived('error')->once();

        expect($result['title'])->toBe('');
    });
});
