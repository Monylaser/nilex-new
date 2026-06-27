<?php

namespace App\Filament\Admin\Resources\Listings\Pages;

use App\Filament\Admin\Resources\Listings\ListingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class EditListing extends EditRecord
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    // ── توليد الإعلان بالـ AI ────────────────────────────────────────────────
    public function generateWithAI(): void
    {
        try {
            Log::info('AI generateWithAI (EditListing): started');

            $apiKey = config('services.gemini.key');
            if (empty($apiKey)) {
                throw new \Exception('مفتاح Gemini API غير موجود — يرجى إضافة GEMINI_API_KEY في ملف .env');
            }

            $formState   = $this->form->getState();
            $title       = $formState['title']       ?? '';
            $description = $formState['description'] ?? '';
            $price       = $formState['price']       ?? '';

            $contextText  = "أنت خبير تسويق في السوق المصري لمنصة Nilex.\n\n";
            if (! empty($title))       $contextText .= "عنوان الإعلان الحالي: {$title}\n";
            if (! empty($description)) $contextText .= "الوصف الحالي: " . strip_tags(is_array($description) ? json_encode($description) : (string) $description) . "\n";
            if (! empty($price))       $contextText .= "السعر الحالي: {$price} ج.م\n";

            $contextText .= "\nبناءً على المعلومات أدناه، قم بتحسين وكتابة إعلان احترافي.\n";
            $contextText .= "أرجع JSON فقط بدون أي نص إضافي خارج الـ JSON:\n";
            $contextText .= '{"title":"عنوان محسن جذاب بحد أقصى 80 حرف","description":"وصف احترافي محسن باللغة العربية 200-300 كلمة","price":null}';

            $parts = [['text' => $contextText]];

            // إضافة صور الإعلان المحفوظة من الـ Media Library
            $record = $this->getRecord();
            if ($record && method_exists($record, 'getMedia')) {
                foreach ($record->getMedia('images')->take(5) as $media) {
                    $path = $media->getPath();
                    if (file_exists($path)) {
                        $parts[] = [
                            'inline_data' => [
                                'mime_type' => $media->mime_type ?? mime_content_type($path),
                                'data'      => base64_encode(file_get_contents($path)),
                            ],
                        ];
                    }
                }
            }

            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

            Log::info('AI generateWithAI (EditListing): sending request', ['parts_count' => count($parts)]);

            $response = Http::timeout(120)
                ->retry(2, 2000, fn (\Throwable $e) => ! ($e instanceof \Illuminate\Http\Client\RequestException && $e->response?->status() === 429))
                ->post($url, [
                    'contents'         => [['parts' => $parts]],
                    'generationConfig' => [
                        'temperature'     => 0.7,
                        'maxOutputTokens' => 2048,
                    ],
                ]);

            if ($response->status() === 429) {
                throw new \Exception('تجاوزت الحد المسموح من Gemini API. يرجى الانتظار قليلاً.');
            }

            if (! $response->successful()) {
                throw new \Exception('خطأ من Gemini (' . $response->status() . '): ' . ($response->json('error.message') ?? 'خطأ غير معروف'));
            }

            Log::info('AI generateWithAI (EditListing): Gemini responded', ['status' => $response->status()]);

            $responseText = $response->json('candidates.0.content.parts.0.text');
            if (empty($responseText)) {
                throw new \Exception('لم يتم استلام رد من الذكاء الاصطناعي');
            }

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

            $newTitle = ! empty($data['title'])
                ? mb_substr(strip_tags((string) $data['title']), 0, 100)
                : $title;
            $newDesc  = ! empty($data['description']) ? $data['description'] : $description;
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
                ->title('تم تحديث الإعلان بالـ AI ✨')
                ->body('راجع البيانات واضغط حفظ.')
                ->success()
                ->send();

        } catch (\Illuminate\Http\Client\RequestException $e) {
            $status  = $e->response?->status();
            Log::error('AI HTTP Error in EditListing', ['status' => $status, 'body' => $e->response?->body()]);

            $message = match (true) {
                $status === 429 => 'تجاوزت الحد المسموح من Gemini API.',
                $status === 401 => 'مفتاح Gemini API غير صالح.',
                $status === 503 => 'خدمة Gemini غير متاحة حالياً.',
                default         => 'فشل الاتصال بـ Gemini API (كود: ' . $status . ')',
            };

            Notification::make()->title('تعذّر توليد الإعلان')->body($message)->danger()->duration(8000)->send();

        } catch (\Exception $e) {
            Log::error('AI Error in EditListing: ' . $e->getMessage());
            Notification::make()->title('تعذّر توليد الإعلان')->body($e->getMessage())->danger()->duration(8000)->send();
        }
    }

    #[On('ai-ad-generated')]
    public function fillFromAI(array $data): void
    {
        $formData = [];

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
            $this->form->fill([
                ...$this->form->getState(),
                ...$formData,
            ]);

            Notification::make()
                ->title('تم تحديث البيانات بواسطة الـ AI ✨')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('فشل سحب البيانات')
                ->body('البيانات المستلمة من الـ AI غير مكتملة.')
                ->danger()
                ->send();
        }
    }
}