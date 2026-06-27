{{--
    معاينة المحتوى داخل مودال "اتخاذ قرار" بطابور المراجعة.
    تظهر فوق فورم القرار حتى يراجع المشرف الصور + الوصف الكامل قبل أي قرار
    (إصلاح "المراجعة العمياء": الفورم كان يظهر بلا أي صورة/وصف).
    عربي فقط — اتساقاً مع باقي app/Filament/* (Phase D i18n مؤجلة).
--}}
@php
    $images = $listing->getMedia('images');
@endphp

<div class="space-y-4" dir="rtl">

    {{-- حقائق أساسية --}}
    <div class="grid grid-cols-2 gap-3 text-sm">
        <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-3">
            <div class="text-xs text-gray-500 dark:text-gray-400">السعر</div>
            <div class="font-semibold text-gray-900 dark:text-gray-100">
                {{ number_format((float) $listing->price) }} ج.م
            </div>
        </div>
        <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-3">
            <div class="text-xs text-gray-500 dark:text-gray-400">القسم</div>
            <div class="font-semibold text-gray-900 dark:text-gray-100">
                {{ $listing->category?->name_ar ?? '—' }}
            </div>
        </div>
        <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-3">
            <div class="text-xs text-gray-500 dark:text-gray-400">المعلن</div>
            <div class="font-semibold text-gray-900 dark:text-gray-100">
                {{ $listing->user?->name ?? '—' }}
            </div>
        </div>
        <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-3">
            <div class="text-xs text-gray-500 dark:text-gray-400">المحافظة</div>
            <div class="font-semibold text-gray-900 dark:text-gray-100">
                {{ $listing->province?->name_ar ?? '—' }}
            </div>
        </div>
    </div>

    {{-- الوصف الكامل --}}
    <div>
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">وصف الإعلان</div>
        <div class="rounded-lg border border-gray-200 dark:border-white/10 p-3 text-sm leading-relaxed text-gray-800 dark:text-gray-200 max-h-60 overflow-y-auto">
            {!! $listing->description ? nl2br(e(strip_tags($listing->description, '<p><br><strong><em><ul><ol><li>'))) : '<span class="text-gray-400">لا يوجد وصف</span>' !!}
        </div>
    </div>

    {{-- معرض الصور --}}
    <div>
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
            صور الإعلان ({{ $images->count() }})
        </div>

        @if ($images->isNotEmpty())
            <div class="grid grid-cols-3 gap-2">
                @foreach ($images as $image)
                    <a href="{{ $image->getUrl() }}" target="_blank" rel="noopener"
                       class="block aspect-square overflow-hidden rounded-lg border border-gray-200 dark:border-white/10 hover:opacity-90 transition">
                        <img src="{{ $image->hasGeneratedConversion('card') ? $image->getUrl('card') : $image->getUrl() }}"
                             alt="صورة الإعلان"
                             class="h-full w-full object-cover" loading="lazy">
                    </a>
                @endforeach
            </div>
        @else
            <div class="rounded-lg border border-dashed border-gray-300 dark:border-white/10 p-4 text-center text-sm text-gray-400">
                لا توجد صور لهذا الإعلان
            </div>
        @endif
    </div>

</div>
