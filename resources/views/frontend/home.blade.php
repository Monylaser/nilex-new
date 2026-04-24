<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نايلكس | منصة الإعلانات الأولى</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-50">

    {{-- Navbar --}}
    <nav class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/" class="text-3xl font-black text-blue-600 tracking-tighter">NILEX</a>

            <div class="hidden md:block flex-1 max-w-md mx-8">
                <form action="{{ route('listings.search') }}" method="GET" class="relative">
                    <input type="text" name="query" placeholder="بتدور على إيه؟"
                           class="w-full bg-gray-100 border-none rounded-2xl py-2.5 px-5 pr-12 focus:ring-2 focus:ring-blue-500 outline-none transition">
                    <button type="submit" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </form>
            </div>

            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="text-gray-600 font-bold text-sm hover:text-blue-600">حسابي</a>
                @else
                    <a href="/login" class="text-gray-600 font-bold text-sm hover:text-blue-600">دخول</a>
                @endauth
                <a href="{{ route('listings.create') }}" class="bg-blue-600 text-white px-6 py-2.5 rounded-2xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100 text-sm">
                    + أضف إعلان
                </a>
            </div>
        </div>
    </nav>

    {{-- Categories Bar --}}
<div class="bg-white border-b overflow-x-auto no-scrollbar">
    <div class="container mx-auto px-4 py-6 flex gap-8 justify-start sm:justify-center items-center">

        @foreach(\App\Models\Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->get() as $cat)
            <a href="{{ route('category.show', $cat) }}" class="flex flex-col items-center group min-w-max">
                
                {{-- حاوية الأيقونة: شفافة تماماً وبدون أي حدود --}}
                <div class="w-14 h-14 flex items-center justify-center transition-all duration-300 group-hover:-translate-y-2">
                    @if($cat->icon)
                        <img src="{{ asset('storage/' . $cat->icon) }}" 
                             alt="{{ $cat->name_ar }}" 
                             {{-- filter: drop-shadow بيعمل ظل على حدود الرسمة نفسها مش المربع --}}
                             class="w-12 h-12 object-contain filter drop-shadow-sm group-hover:drop-shadow-lg transition-all">
                    @else
                        {{-- في حال عدم وجود أيقونة، نظهر أول حرف بستايل مميز --}}
                        <span class="text-xl font-black text-blue-500 bg-blue-50 w-12 h-12 flex items-center justify-center rounded-full">
                            {{ mb_substr($cat->name_ar, 0, 1) }}
                        </span>
                    @endif
                </div>

                {{-- اسم القسم --}}
                <span class="text-xs mt-2 font-bold text-gray-700 group-hover:text-blue-600 transition text-center max-w-[80px] truncate">
                    {{ $cat->name_ar }}
                </span>

                {{-- عداد المشاهدات الذكي --}}
                <div class="flex items-center gap-1 mt-1 opacity-40 group-hover:opacity-100 transition-opacity">
                    <svg class="w-3 h-3 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <span class="text-[9px] font-black text-gray-500">{{ number_format($cat->views_count ?? 0) }}</span>
                </div>
            </a>
        @endforeach

    </div>
</div>

<main class="container mx-auto px-4 py-12">
    {{-- هنا باقي محتوى الصفحة (الإعلانات المميزة وأحدث الإعلانات) --}}
</main>

</body>
</html>