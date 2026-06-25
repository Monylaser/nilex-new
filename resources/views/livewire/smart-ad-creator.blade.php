{{-- resources/views/livewire/smart-ad-creator.blade.php --}}
<div class="p-4 sm:p-6"
     x-data="{ progress: 0, currentStep: '' }"
     x-on:progress-updated.window="progress = $event.detail.progress; currentStep = $event.detail.message">

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col items-center text-center mb-6">
        <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl shadow-lg mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        </div>

        <h2 class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-amber-600 to-amber-500 bg-clip-text text-transparent">
            {{ __('ui.ad_creator.title') }} ✨
        </h2>

        <p class="text-gray-500 text-sm mt-1">
            @if(!empty($existingImages))
                <span class="text-green-600 font-semibold">
                    ✅ {{ __('ui.ad_creator.images_found', ['count' => count($existingImages)]) }}
                </span>
            @else
                {{ __('ui.ad_creator.upload_prompt') }}
            @endif
        </p>
    </div>

    {{-- ── شريط التقدم (يظهر فقط أثناء المعالجة) ─────────────────────────── --}}
    @if($isProcessing)
        <div class="mb-6">
            <div class="flex justify-between text-xs text-gray-600 mb-1">
                <span>🧠 <span x-text="currentStep || '{{ __('ui.ad_creator.processing') }}'"></span></span>
                <span x-text="progress + '%'"></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-500 to-amber-600 h-2 rounded-full transition-all duration-300"
                     :style="{ width: progress + '%' }"></div>
            </div>
        </div>
    @endif

    {{-- ── رسالة الخطأ ─────────────────────────────────────────────────────── --}}
    @if($errorMessage)
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm text-right">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- ── معاينة الصور الموجودة من الإعلان ─────────────────────────────────── --}}
    @if(!empty($existingImages))
        <div class="mb-4">
            <p class="text-xs text-gray-400 mb-2 text-right">{{ __('ui.ad_creator.passed_images') }}</p>
            <div class="flex flex-wrap gap-2 justify-center">
                @foreach($existingImages as $img)
                    @if(!empty($img))
                        <div class="relative w-14 h-14 rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                            <img src="{{ Storage::disk('public')->url($img) }}"
                                 class="object-cover w-full h-full"
                                 onerror="this.style.display='none'">
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── رفع صور جديدة (تظهر لو مفيش صور ممررة) ────────────────────────── --}}
    @if(empty($existingImages) && !$isProcessing)
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2 text-right">
                {{ __('ui.ad_creator.upload_label', ['count' => $maxPhotos]) }}
            </label>
            <input type="file"
                   wire:model="photos"
                   accept="image/*"
                   multiple
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">

            @if(!empty($photos))
                <div class="flex flex-wrap gap-2 mt-3 justify-center">
                    @foreach($photos as $i => $photo)
                        <div class="relative w-14 h-14 rounded-lg border overflow-hidden group">
                            <img src="{{ $photo->temporaryUrl() }}" class="object-cover w-full h-full">
                            <button wire:click="removePhoto({{ $i }})"
                                    class="absolute top-0 right-0 bg-red-500 text-white rounded-bl text-xs px-1 hidden group-hover:block">
                                ✕
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ── ملاحظات نصية ───────────────────────────────────────────────────── --}}
    @if(!$isProcessing)
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1 text-right">
                {{ __('ui.ad_creator.notes_label') }}
            </label>
            <textarea wire:model="textNote"
                      rows="2"
                      maxlength="500"
                      placeholder="{{ __('ui.ad_creator.notes_placeholder') }}"
                      class="w-full text-sm border border-gray-300 rounded-xl p-2 resize-none focus:ring-amber-500 focus:border-amber-500 text-right"
                      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"></textarea>
        </div>
    @endif

    {{-- ── أزرار التحكم ───────────────────────────────────────────────────── --}}
    <div class="flex justify-center gap-3 mt-2">
        @if(!$isProcessing)
            <button wire:click="processWithAI"
                    wire:loading.attr="disabled"
                    class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white font-semibold rounded-xl shadow-md hover:from-amber-600 hover:to-amber-700 transition disabled:opacity-60">
                <span wire:loading.remove wire:target="processWithAI">
                    @if(!empty($existingImages))
                        🚀 {{ __('ui.ad_creator.analyze_images') }}
                    @else
                        ✨ {{ __('ui.ad_creator.generate_ai') }}
                    @endif
                </span>
                <span wire:loading wire:target="processWithAI">
                    ⏳ {{ __('ui.ad_creator.processing') }}
                </span>
            </button>
        @endif
    </div>

</div>