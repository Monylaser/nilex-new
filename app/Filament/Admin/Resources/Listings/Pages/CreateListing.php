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

    // ────────────────────────────────────────────────────────────────────────
    // الحل الجذري لمشكلة validation على custom_fields_values
    // بيتشغل تلقائياً قبل أي INSERT في قاعدة البيانات
    // ────────────────────────────────────────────────────────────────────────
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // تأكد إن custom_fields_values موجودة كـ array مش null
        if (! is_array($data['custom_fields_values'] ?? null)) {
            $data['custom_fields_values'] = [];
        }

        // تأكد إن year محفوظة كـ string مش null
        if (isset($data['custom_fields_values']['year'])) {
            $data['custom_fields_values']['year'] = (string) $data['custom_fields_values']['year'];
        }

        // نظّف القيم الفارغة من الـ JSON column
        $data['custom_fields_values'] = array_filter(
            $data['custom_fields_values'],
            fn ($value) => $value !== null && $value !== ''
        );

        return $data;
    }

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
            // فحص أمان (Defensive Programming)
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

            if (! empty($title)) {
                $contextText .= "عنوان الإعلان الحالي: {$title}\n";
            }
            if (! empty($description)) {
                $cleanDesc    = strip_tags(is_array($description) ? json_encode($description) : (string) $description);
                $contextText .= "الوصف الحالي: {$cleanDesc}\n";
            }
            if (! empty($price)) {
                $contextText .= "السعر الحالي: {$price} ج.م\n";
            }

            $contextText .= "\nبناءً على المعلومات والصور المرفقة، قم بتحسين وكتابة إعلان احترافي.\n";
            $contextText .= "أرجع JSON فقط بدون أي نص إضافي خارج الـ JSON:\n";
            $contextText .= '{"title":"عنوان محسن جذاب بحد أقصى 80 حرف","description":"وصف احترافي محسن باللغة العربية 200-300 كلمة","price":null}';

            $parts = [['text' => $contextText]];

            // ── إضافة الصور المرفوعة مؤقتاً في الفورم ────────────────────────
            // هنسحب الصور من الـ State الخاص بـ Filament/Livewire
            $uploadedImages = $formState['images'] ?? [];

            if (!empty($uploadedImages)) {
                // تقييد بـ 5 صور كحد أقصى عشان سرعة الـ API
                $imagesToProcess = array_slice(is_array($uploadedImages) ? $uploadedImages : [$uploadedImages], 0, 5);

                foreach ($imagesToProcess as $image) {
                    $imagePath = null;

                    // Filament 5/Livewire 3 بيتعامل مع الملفات كـ TemporaryUploadedFile Object
                    if (is_object($image) && method_exists($image, 'getRealPath')) {
                        $imagePath = $image->getRealPath();
                    } 
                    // كاحتياطي لو رجع كـ string (اسم الملف المؤقت)
                    elseif (is_string($image)) {
                        $tempPath = storage_path('app/livewire-tmp/' . $image);
                        if (file_exists($tempPath)) {
                            $imagePath = $tempPath;
                        }
                    }

                    if ($imagePath && file_exists($imagePath)) {
                        $parts[] = [
                            'inline_data' => [
                                'mime_type' => mime_content_type($imagePath),
                                'data'      => base64_encode(file_get_contents($imagePath)),
                            ]
                        ];
                    }
                }
            }

            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

            $response = Http::timeout(120)
                ->retry(2, 2000)
                ->post($url, [
                    'contents' => [['parts' => $parts]],
                    'generationConfig' => [
                        'temperature'     => 0.7,
                        'maxOutputTokens' => 2048,
                    ],
                ]);

            if (! $response->successful()) {
                throw new \Exception('خطأ من Gemini: ' . ($response->json('error.message') ?? $response->status()));
            }

            $responseText = $response->json('candidates.0.content.parts.0.text');

            if (empty($responseText)) {
                throw new \Exception('لم يتم استلام رد من الذكاء الاصطناعي');
            }

            // ── تحليل الـ JSON — محاولات متعددة ──────────────────────────
            $data = json_decode($responseText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                if (preg_match('/\{[\s\S]*\}/u', $responseText, $matches)) {
                    $data = json_decode($matches[0], true);
                }
            }

            if (empty($data) || json_last_error() !== JSON_ERROR_NONE) {
                $data = [
                    'title'       => $title ?: 'إعلان جديد',
                    'description' => $responseText,
                    'price'       => $price ?: null,
                ];
            }

            // ── ملء الفورم ───────────────────────────────────────────────
            $newTitle = ! empty($data['title'])
                ? mb_substr(strip_tags((string) $data['title']), 0, 100)
                : $title;

            $newDesc = ! empty($data['description'])
                ? $data['description']
                : $description;

            $newPrice = ! empty($data['price'])
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

        if (! empty($data['title'])) {
            $formData['title'] = $data['title'];
            $formData['slug']  = Str::slug($data['title'], '-', 'ar');
        }
        if (! empty($data['description'])) {
            $formData['description'] = $data['description'];
        }
        if (! empty($data['price'])) {
            $formData['price'] = (float) $data['price'];
        }

        if (! empty($formData)) {
            $this->form->fill([...$formState, ...$formData]);

            Notification::make()
                ->title('تم سحب بيانات الـ AI بنجاح! ✨')
                ->body('راجع البيانات ثم اضغط على حفظ الإعلان.')
                ->success()
                ->send();
        }
    }
}