<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 group relative">

    {{-- منطقة الصورة --}}
    <div class="relative h-48 bg-gray-100 overflow-hidden">
        {{-- التعديل هنا: استخدام Spatie Media Library لجلب النسخة المختومة --}}
        @if($listing->hasMedia('images'))
            <img src="{{ $listing->getFirstMediaUrl('images', 'full_hd') }}"
                 alt="{{ $listing->title }}"
                 class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500">
        @else
            <div class="flex flex-col items-center justify-center h-full text-gray-400">
                <span class="text-3xl">🖼️</span>
                <span class="text-xs mt-2">لا توجد صور</span>
            </div>
        @endif

        {{-- شارة "مميز" --}}
        @if($isFeatured ?? false)
            <div class="absolute top-3 right-3 bg-yellow-400 text-gray-900 text-[10px] font-black px-2 py-1 rounded-md shadow-sm">
                مُميز
            </div>
        @endif

        {{-- السعر --}}
        <div class="absolute bottom-3 left-3 bg-blue-600 text-white px-3 py-1 rounded-xl font-bold text-sm">
            {{ number_format($listing->price) }} ج.م
        </div>
    </div>

    {{-- التفاصيل --}}
    <div class="p-5">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-[10px] font-bold text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full">
                {{ $listing->category->name_ar ?? 'عام' }}
            </span>
        </div>

        <h3 class="font-bold text-gray-800 mb-2 line-clamp-1 group-hover:text-blue-600 transition">
            <a href="{{ route('listings.show', $listing->id) }}">
                {{ $listing->title }}
            </a>
        </h3>

        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-50">
            <span class="text-[10px] text-gray-400">
                {{ $listing->created_at->diffForHumans() }}
            </span>
            <a href="{{ route('listings.show', $listing->id) }}" class="text-blue-600 text-xs font-bold">
                التفاصيل ←
            </a>
        </div>
    </div>
</div>