<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $listing->title }} | نايلكس</title>
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- إضافة Alpine.js لعمل معرض الصور --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
{{-- تهيئة Alpine.js مع أول صورة بالإعلان بالنسخة المختومة (full_hd) --}}
<body class="bg-gray-50" x-data="{ mainImage: '{{ $listing->getFirstMediaUrl('images', 'full_hd') ?: 'https://via.placeholder.com/800x600?text=بدون+صورة' }}' }">

    <nav class="bg-white shadow-sm p-4 mb-6">
        <div class="container mx-auto">
            <a href="/" class="text-blue-600 font-bold hover:text-blue-800 transition">← العودة للرئيسية</a>
        </div>
    </nav>

    <main class="container mx-auto px-4 max-w-5xl">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- ── معرض الصور والوصف ──────────────────────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-6">

                <div class="bg-white rounded-2xl shadow-sm overflow-hidden p-2 border border-gray-100">
                    {{-- الصورة الرئيسية (العلامة المائية هتظهر هنا) --}}
                    <div class="bg-gray-100 rounded-xl flex items-center justify-center overflow-hidden h-[400px]">
                        <img :src="mainImage" class="max-w-full max-h-full object-contain transition-all duration-300">
                    </div>

                    {{-- شريط الصور المصغرة (بدون علامة مائية للسرعة) --}}
                    @if($listing->getMedia('images')->count() > 1)
                    <div class="flex gap-2 mt-4 overflow-x-auto pb-2 custom-scrollbar">
                        @foreach($listing->getMedia('images') as $media)
                            <img src="{{ $media->getUrl('thumb') }}"
                                 @click="mainImage = '{{ $media->getUrl('full_hd') }}'"
                                 :class="mainImage === '{{ $media->getUrl('full_hd') }}' ? 'border-blue-600 opacity-100 shadow-md' : 'border-transparent opacity-50 hover:opacity-100'"
                                 class="w-24 h-24 object-cover rounded-lg cursor-pointer border-2 transition-all duration-200">
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h1 class="text-3xl font-black text-gray-900 mb-4">{{ $listing->title }}</h1>
                    <div class="prose max-w-none text-gray-600 leading-relaxed text-lg">
                        <h3 class="text-xl font-bold text-gray-900 mb-3 border-r-4 border-blue-600 pr-3 bg-blue-50 py-1 inline-block rounded-l-lg">وصف الإعلان:</h3>
                        {{-- عشان الـ HTML يطبع بشكل سليم لو جاي من مٌحرر نصوص --}}
                        <div class="mt-4">{!! $listing->description !!}</div>
                    </div>
                </div>
            </div>

            {{-- ── تفاصيل السعر والتواصل ─────────────────────────────────────────── --}}
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm sticky top-6 border border-gray-100">
                    <div class="text-center mb-6">
                        <span class="text-gray-500 text-sm font-bold block mb-2">السعر المطلوب</span>
                        <div class="text-4xl font-black text-blue-600 bg-blue-50 py-3 rounded-xl">
                            {{ number_format($listing->price) }} <span class="text-lg font-bold text-blue-400">ج.م</span>
                        </div>
                    </div>

                    {{-- أزرار التواصل --}}
                    <div class="grid gap-3">
                        @php $phone = $listing->phone ?? $listing->user?->phone; @endphp

                        @if($phone)
                            <a href="tel:{{ $phone }}" class="flex items-center justify-center gap-2 w-full bg-gray-900 text-white py-4 rounded-xl font-bold hover:bg-gray-800 transition shadow-sm">
                                <span>📞</span> اتصل بالبائع
                            </a>

                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $phone) }}" target="_blank" class="flex items-center justify-center gap-2 w-full bg-emerald-500 text-white py-4 rounded-xl font-bold hover:bg-emerald-600 transition shadow-sm">
                                <span>💬</span> تواصل واتساب
                            </a>
                        @else
                            <div class="text-center text-red-500 font-bold bg-red-50 py-3 rounded-xl">لا يوجد رقم للتواصل</div>
                        @endif
                    </div>

                    <div class="mt-8 space-y-4 text-sm text-gray-600">
                        <div class="flex justify-between items-center py-3 border-b border-gray-50">
                            <span class="font-bold text-gray-400">📍 الموقع</span>
                            <span class="font-black text-gray-900">{{ $listing->location?->name_ar ?? 'غير محدد' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-3 border-b border-gray-50">
                            <span class="font-bold text-gray-400">📂 القسم</span>
                            <span class="font-black text-gray-900">{{ $listing->category?->name_ar ?? 'غير محدد' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-3 border-b border-gray-50">
                            <span class="font-bold text-gray-400">🏷️ الحالة</span>
                            <span class="font-black {{ $listing->condition == 'new' ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $listing->condition == 'new' ? 'جديد (لم يستعمل)' : 'مستعمل' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-3">
                            <span class="font-bold text-gray-400">⏱️ تاريخ النشر</span>
                            <span class="font-bold text-gray-900">{{ $listing->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>