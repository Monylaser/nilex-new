<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class SmartAdCreator extends Component
{
    use WithFileUploads;

    // ── الصور الممررة من Filament (مسارات أو URLs) ───────────────────────────
    public array $existingImages = [];

    // ── رفع جديد داخل المودال ────────────────────────────────────────────────
    public array $photos = [];
    public $audio;
    public ?string $textNote = null;

    // ── حالة المعالجة ─────────────────────────────────────────────────────────
    public bool $isProcessing = false;
    public string $errorMessage = '';
    public int $progress = 0;
    public string $currentStep = '';

    public int $maxPhotos = 5;

    protected function rules(): array
    {
        return [
            'photos.*'  => 'image|max:5120|mimes:jpg,jpeg,png,webp,gif',
            'audio'     => 'nullable|file|mimes:mp3,wav,m4a,ogg,webm|max:10240',
            'textNote'  => 'nullable|string|max:500',
        ];
    }

    protected function messages(): array
    {
        return [
            'photos.*.max'   => __('server.ai.photo_max'),
            'photos.*.image' => __('server.ai.photo_image'),
            'photos.*.mimes' => __('server.ai.photo_mimes'),
            'audio.max'      => __('server.ai.audio_max'),
            'audio.mimes'    => __('server.ai.audio_mimes'),
            'textNote.max'   => __('server.ai.note_max'),
        ];
    }

    // ── mount: استقبال الصور من Filament ─────────────────────────────────────
    public function mount(array $existingImages = []): void
    {
        // تنظيف القيم الفارغة اللي بترجعها Filament قبل الحفظ
        $this->existingImages = array_values(array_filter($existingImages));
    }

    // ── إدارة الصور المرفوعة داخل المودال ────────────────────────────────────
    public function updatedPhotos(): void
    {
        if (count($this->photos) > $this->maxPhotos) {
            $this->errorMessage = __('server.ai.too_many_photos', ['max' => $this->maxPhotos]);
            $this->photos = array_slice($this->photos, 0, $this->maxPhotos);
        } else {
            $this->errorMessage = '';
        }
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function clearAllPhotos(): void
    {
        $this->photos = [];
    }

    public function clearAudio(): void
    {
        $this->audio = null;
    }

    // ── تحديث شريط التقدم ────────────────────────────────────────────────────
    protected function updateProgress(int $step, string $message = ''): void
    {
        $this->progress    = $step;
        $this->currentStep = $message;
        $this->dispatch('progress-updated', progress: $step, message: $message);
    }

    // ── المعالجة الرئيسية ────────────────────────────────────────────────────
    public function processWithAI(): void
    {
        $this->validate();

        $hasNewPhotos     = !empty($this->photos);
        $hasExisting      = !empty($this->existingImages);
        $hasAudio         = !empty($this->audio);
        $hasText          = !empty($this->textNote);

        if (!$hasNewPhotos && !$hasExisting && !$hasAudio && !$hasText) {
            $this->errorMessage = __('server.ai.need_input');
            return;
        }

        $this->isProcessing = true;
        $this->errorMessage = '';
        $this->progress     = 0;

        try {
            $this->updateProgress(5, __('server.ai.step_checking'));

            $apiKey = config('services.gemini.key') ?: env('GEMINI_API_KEY');
            if (empty($apiKey)) {
                throw new \Exception(__('server.ai.no_api_key'));
            }

            $this->updateProgress(10, __('server.ai.step_preparing'));

            $url   = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
            $parts = [];

            $parts[] = ['text' => $this->getSystemPrompt()];

            if ($this->textNote) {
                $parts[] = ['text' => "📝 ملاحظات إضافية: " . $this->textNote];
            }

            $this->updateProgress(20, __('server.ai.step_processing_images'));

            // ── 1. الصور الممررة من Filament (مسارات على الديسك) ─────────────
            foreach (array_slice($this->existingImages, 0, $this->maxPhotos) as $imagePath) {
                if (empty($imagePath)) continue;

                // imagePath ممكن يكون مسار نسبي أو URL مؤقت من Livewire
                $fullPath = storage_path('app/public/' . ltrim($imagePath, '/'));

                if (file_exists($fullPath)) {
                    $parts[] = [
                        'inline_data' => [
                            'mime_type' => mime_content_type($fullPath),
                            'data'      => base64_encode(file_get_contents($fullPath)),
                        ]
                    ];
                }
            }

            // ── 2. الصور المرفوعة حديثاً داخل المودال ────────────────────────
            $remaining = $this->maxPhotos - count($this->existingImages);
            foreach (array_slice($this->photos, 0, max(0, $remaining)) as $photo) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $photo->getMimeType(),
                        'data'      => base64_encode(file_get_contents($photo->getRealPath())),
                    ]
                ];
            }

            $this->updateProgress(40, __('server.ai.step_images_done'));

            // ── 3. الصوت ─────────────────────────────────────────────────────
            if ($this->audio) {
                $this->updateProgress(50, __('server.ai.step_processing_audio'));
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $this->audio->getMimeType(),
                        'data'      => base64_encode(file_get_contents($this->audio->getRealPath())),
                    ]
                ];
                $this->updateProgress(60, __('server.ai.step_audio_done'));
            }

            $this->updateProgress(65, __('server.ai.step_connecting'));

            $response = Http::timeout(120)
                ->retry(3, 1000)
                ->post($url, [
                    'contents'         => [['parts' => $parts]],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'temperature'        => 0.7,
                        'topP'               => 0.95,
                        'topK'               => 40,
                        'maxOutputTokens'    => 2048,
                    ],
                    'safetySettings' => [
                        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                    ],
                ]);

            $this->updateProgress(85, __('server.ai.step_analyzing'));

            if ($response->successful()) {
                $responseText = $response->json('candidates.0.content.parts.0.text');

                if (empty($responseText)) {
                    throw new \Exception(__('server.ai.no_response'));
                }

                $data = json_decode($responseText, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    if (preg_match('/\{[\s\S]*\}/', $responseText, $matches)) {
                        $data = json_decode($matches[0], true);
                    }
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $data = $this->extractDataFromText($responseText);
                    }
                }

                $data = $this->sanitizeData($data);

                $this->updateProgress(95, __('server.ai.step_sending'));

                // إرسال الحدث للـ Filament form لملء الحقول
                $this->dispatch('ai-ad-generated', data: $data);

                $this->updateProgress(100, __('server.ai.step_success'));

                $this->reset(['photos', 'audio', 'textNote', 'progress', 'currentStep']);
                $this->isProcessing = false;

                // إغلاق المودال
                $this->dispatch('close-modal', id: 'smart-ad-modal');

            } else {
                $errorMessage = $response->json('error.message') ?? __('server.ai.unknown_error');
                Log::error('Gemini API Error', ['status' => $response->status(), 'body' => $response->body()]);
                $this->errorMessage = __('server.ai.server_error', ['message' => $errorMessage]);
            }

        } catch (\Exception $e) {
            Log::error('AI Processing Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->errorMessage = __('server.ai.technical_error', ['message' => $e->getMessage()]);
        }

        $this->isProcessing = false;
    }

    protected function sanitizeData(array $data): array
    {
        $defaults = [
            'title'               => 'إعلان جديد',
            'description'         => 'وصف الإعلان غير متوفر',
            'price'               => null,
            'category_suggestion' => 'عام',
            'tags'                => [],
        ];

        $data            = array_merge($defaults, $data);
        $data['title']   = mb_substr(strip_tags(trim($data['title'])), 0, 100);
        $data['description'] = strip_tags(trim($data['description']), '<ul><li><p><br><strong><b><em><i>');

        if (is_string($data['price'])) {
            $cleaned       = preg_replace('/[^0-9]/', '', $data['price']);
            $data['price'] = !empty($cleaned) ? (float) $cleaned : null;
        }

        return $data;
    }

    private function getSystemPrompt(): string
    {
        return <<<PROMPT
أنت خبير تسويق عقاري ومنتجات في السوق المصري لمنصة 'Nilex'.

بناءً على الصور المرفقة (حتى 5 صور) والتسجيل الصوتي إن وجد (باللهجة المصرية)، قم بتحليل المحتوى بدقة واستخرج بيانات الإعلان.

قم بإرجاع JSON فقط (بدون أي نص إضافي خارج الـ JSON) بالمفاتيح التالية:
{
    "title": "عنوان جذاب ومحسن لمحركات البحث (بحد أقصى 80 حرف)",
    "description": "وصف احترافي باللغة العربية الفصحى الحديثة (200-300 كلمة)",
    "price": "السعر المقترح بالجنيه المصري كرقم فقط (لو مش واضح خليها null)",
    "category_suggestion": "القسم المناسب: عقارات، سيارات، إلكترونيات، خدمات، موضة، منزل، أخرى",
    "tags": ["كلمة", "مفتاحية", "ذات", "صلة"]
}

قواعد مهمة:
- استخدم فقط المعلومات الموجودة في الصور أو الصوت
- لو السعر غير واضح، لا تخترع رقماً
- اكتب الوصف بطريقة احترافية وجذابة
PROMPT;
    }

    private function extractDataFromText(string $text): array
    {
        $lines = explode("\n", $text);
        $title = (!empty($lines[0]) && strlen($lines[0]) < 100)
            ? trim(array_shift($lines))
            : 'إعلان جديد';

        $price = null;
        $patterns = [
            '/(\d[\d,]*(?:\.\d+)?)\s*(?:جنيه|EGP|ج\.م|LE)/i',
            '/سعر[\s:]*(\d[\d,]*)/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $price = (float) str_replace(',', '', $m[1]);
                break;
            }
        }

        return [
            'title'               => $title,
            'description'         => implode("\n", $lines),
            'price'               => $price,
            'category_suggestion' => 'عام',
            'tags'                => [],
        ];
    }

    #[On('retry-ai')]
    public function retry(): void
    {
        $this->processWithAI();
    }

    public function render()
    {
        return view('livewire.smart-ad-creator');
    }
}