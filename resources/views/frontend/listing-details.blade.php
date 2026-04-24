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
<body class="bg-gray-50" x-data="{ mainImage: '' }">

    @php
        $images = $listing->images;
        // تجهيز مصفوفة الصور بشكل نظيف
        $formattedImages = collect($images)->map(function($img) {
            return is_array($img) ? ($img['url'] ?? null) : $img;
        })->filter()->values();

        $firstImage = $formattedImages->first();
    @endphp

    <nav class="bg-white shadow-sm p-4 mb-6">
        <div class="container mx-auto">
            <a href="/" class="text-blue-600 font-bold">← العودة للرئيسية</a>
        </div>
    </nav>

    <main class="container mx-auto px-4 max-w-5xl" x-init="mainImage = '{{ asset('storage/' . $firstImage) }}'">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-6">

                <div class="bg-white rounded-2xl shadow-sm overflow-hidden p-2">
                    <div class="bg-black rounded-xl flex items-center justify-center overflow-hidden h-[400px]">
                        <img :src="mainImage" class="max-w-full max-h-full object-contain transition-all duration-500">
                    </div>

                    @if($formattedImages->count() > 1)
                    <div class="flex gap-2 mt-4 overflow-x-auto pb-2">
                        @foreach($formattedImages as $img)
                            <img src="{{ asset('storage/' . $img) }}"
                                 @click="mainImage = '{{ asset('storage/' . $img) }}'"
                                 :class="mainImage === '{{ asset('storage/' . $img) }}' ? 'border-blue-600 opacity-100' : 'border-transparent opacity-60'"
                                 class="w-20 h-20 object-cover rounded-lg cursor-pointer border-2 transition hover:opacity-100">
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-sm">
                    <h1 class="text-3xl font-black text-gray-800 mb-4">{{ $listing->title }}</h1>
                    <div class="prose max-w-none text-gray-600 leading-relaxed">
                        <h3 class="text-lg font-bold text-gray-800 mb-2 border-r-4 border-blue-600 pr-3">وصف الإعلان:</h3>
                        {!! $listing->description !!}
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm sticky top-6 border border-gray-100">
                    <div class="text-center mb-6">
                        <span class="text-gray-400 text-sm block mb-1">السعر المطلوب</span>
                        <div class="text-4xl font-black text-blue-600">
                            {{ number_format($listing->price) }} <span class="text-sm font-normal">EGP</span>
                        </div>
                    </div>

                    {{-- أزرار التواصل الحقيقية --}}
                    <div class="grid gap-3">
                        @php $phone = $listing->phone ?? $listing->user?->phone; @endphp

                        <a href="tel:{{ $phone }}" class="flex items-center justify-center gap-2 w-full bg-blue-600 text-white py-4 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                            <span>📞</span>
                            اتصل بالبائع
                        </a>

                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $phone) }}" target="_blank" class="flex items-center justify-center gap-2 w-full bg-green-500 text-white py-4 rounded-xl font-bold hover:bg-green-600 transition shadow-lg shadow-green-100">
                            <span>💬</span>
                            واتساب
                        </a>
                    </div>

                    <div class="mt-8 space-y-4 text-sm text-gray-600">
                        <div class="flex justify-between items-center py-2 border-b border-gray-50">
                            <span>📍 الموقع</span>
                            <span class="font-bold text-gray-800">{{ $listing->location?->name_ar }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-50">
                            <span>📂 القسم</span>
                            <span class="font-bold text-gray-800">{{ $listing->category?->name_ar }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-50">
                            <span>🏷️ الحالة</span>
                            <span class="font-bold text-gray-800">{{ $listing->condition == 'new' ? 'جديد' : 'مستعمل' }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
