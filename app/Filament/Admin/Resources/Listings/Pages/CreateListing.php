<?php

namespace App\Filament\Admin\Resources\Listings\Pages;

use App\Filament\Admin\Resources\Listings\ListingResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class CreateListing extends CreateRecord
{
    protected static string $resource = ListingResource::class;

    // ── زرار الـ AI في الـ header ─────────────────────────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateWithAI')
                ->label('ولّد الإعلان بالـ AI ✨')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->action(fn () => $this->generateWithAI()),
        ];
    }

    // ── المعالجة الرئيسية ─────────────────────────────────────────────────────
    public function generateWithAI(): void
    {
        try {
            $apiKey = env('GEMINI_API_KEY');
            if (empty($apiKey)) {
                throw new \Exception('مفتاح Gemini API غير موجود في ملف .env');
            }

            // ── جلب state الفورم الحالي ──────────────────────────────────
            $formState   = $this->form->getState();
            $title       = $formState['title']       ?? '';
            $description = $formState['description'] ?? '';
            $price       = $formState['price']       ?? '';

            // ── بناء البرومبت ─────────────────────────────────────────────
            $contextText = "أنت خبير تسويق في السوق المصري لمنصة Nilex.\n\n";

            if (!empty($title)) {
                $contextText .= "عنوان الإعلان الحالي: {$title}\n";
            }
            if (!empty($description)) {
                $cleanDesc    = strip_tags(is_array($description) ? json_encode($description) : (string) $description);
                $contextText .= "الوصف الحالي: {$cleanDesc}\n";
            }
            if (!empty($price)) {
                $contextText .= "السعر الحالي: {$price} ج.م\n";
            }

            $contextText .= "\nبناءً على المعلومات والصور المرفقة، قم بتحسين وكتابة إعلان احترافي.\n";
            $contextText .= "أرجع JSON فقط بدون أي نص إضافي خارج الـ JSON:\n";
            $contextText .= '{"title":"عنوان محسن جذاب بحد أقصى 80 حرف","description":"وصف احترافي محسن باللغة العربية 200-300 كلمة","price":null}';

            $parts = [['text' => $contextText]];

            // ── إضافة الصور من الـ storage ───────────────────────────────
            $imagesDir  = storage_path('app/public/listings/');
            $imageFiles = glob($imagesDir . '*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP}', GLOB_BRACE);

            if (!empty($imageFiles)) {
                usort($imageFiles, fn ($a, $b) => filemtime($b) - filemtime($a));

                foreach (array_slice($imageFiles, 0, 5) as $imagePath) {
                    if (file_exists($imagePath)) {
                        $parts[] = [
                            'inline_data' => [
                                'mime_type' => mime_content_type($imagePath),
                                'data'      => base64_encode(file_get_contents($imagePath)),
                            ]
                        ];
                    }
                }
            }

            // ── FIX 1: الموديل الصح ───────────────────────────────────────
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

            $response = Http::timeout(120)
                ->retry(2, 2000)
                ->post($url, [
                    'contents' => [['parts' => $parts]],
                    // ── FIX 2: شيلنا response_mime_type ─────────────────
                    'generationConfig' => [
                        'temperature'     => 0.7,
                        'maxOutputTokens' => 2048,
                    ],
                ]);

            if (!$response->successful()) {
                throw new \Exception('خطأ من Gemini: ' . ($response->json('error.message') ?? $response->status()));
            }

            $responseText = $response->json('candidates.0.content.parts.0.text');

            if (empty($responseText)) {
                throw new \Exception('لم يتم استلام رد من الذكاء الاصطناعي');
            }

            // ── تحليل الـ JSON — محاولات متعددة ──────────────────────────
            $data = json_decode($responseText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // محاولة استخراج JSON من وسط النص
                if (preg_match('/\{[\s\S]*\}/u', $responseText, $matches)) {
                    $data = json_decode($matches[0], true);
                }
            }

            // لو فشل JSON خالص — نستخدم النص كوصف
            if (empty($data) || json_last_error() !== JSON_ERROR_NONE) {
                $data = [
                    'title'       => $title ?: 'إعلان جديد',
                    'description' => $responseText,
                    'price'       => $price ?: null,
                ];
            }

            // ── ملء الفورم ───────────────────────────────────────────────
            $newTitle = !empty($data['title'])
                ? mb_substr(strip_tags((string) $data['title']), 0, 100)
                : $title;

            $newDesc = !empty($data['description'])
                ? $data['description']
                : $description;

            $newPrice = !empty($data['price'])
                ? (float) preg_replace('/[^0-9.]/', '', (string) $data['price'])
                : $price;

            $this->form->fill([
                ...$formState,
                'title'       => $newTitle,
                'slug'        => Str::slug($newTitle, '-', 'ar'),
                'description' => $newDesc,
                'price'       => $newPrice ?: $price,
            ]);

            Notification::make()
                ->title('تم إنشاء الإعلان بالـ AI ✨')
                ->body('راجع البيانات واضغط حفظ.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('AI Error in CreateListing: ' . $e->getMessage());

            Notification::make()
                ->title('خطأ في الـ AI')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // ── listener احتياطي ─────────────────────────────────────────────────────
    #[On('ai-ad-generated')]
    public function fillFromAI(array $data): void
    {
        $formState = $this->form->getState();
        $formData  = [];

        if (!empty($data['title'])) {
            $formData['title'] = $data['title'];
            $formData['slug']  = Str::slug($data['title'], '-', 'ar');
        }
        if (!empty($data['description'])) {
            $formData['description'] = $data['description'];
        }
        if (!empty($data['price'])) {
            $formData['price'] = (float) $data['price'];
        }

        if (!empty($formData)) {
            $this->form->fill([...$formState, ...$formData]);

            Notification::make()
                ->title('تم سحب بيانات الـ AI بنجاح! ✨')
                ->body('راجع البيانات ثم اضغط على حفظ الإعلان.')
                ->success()
                ->send();
        }
    }
}