<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتائج البحث عن: {{ $query }} | نايلكس</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-gray-50">

    <nav class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            {{-- اللوجو --}}
            <a href="/" class="text-2xl font-black text-blue-600 tracking-tighter">NILEX</a>

            {{-- شريط البحث في الهيدر (للكومبيوتر) --}}
            <div class="hidden md:block flex-1 max-w-md mx-8">
                <form action="{{ route('listings.search') }}" method="GET" class="relative">
                    <input type="text" name="query" value="{{ $query }}" placeholder="بتدور على إيه؟"
                           class="w-full bg-gray-100 border-none rounded-full py-2 px-5 pr-12 focus:ring-2 focus:ring-blue-500 outline-none text-right shadow-sm">
                    <button type="submit" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </form>
            </div>

            {{-- روابط المستخدم الديناميكية --}}
            <div class="flex items-center gap-3">
                @auth
                    {{-- حالة 1: المستخدم مسجل دخول --}}
                    @if(auth()->user()->email == 'admin@gmail.com') {{-- تأكد من وضع إيميل الأدمن الصحيح هنا --}}
                        <a href="/admin" class="hidden sm:block text-sm font-bold text-red-600 bg-red-50 px-3 py-2 rounded-lg hover:bg-red-100 transition">
                            لوحة التحكم (Admin)
                        </a>
                    @else
                        <a href="/dashboard" class="hidden sm:block text-sm font-bold text-gray-600 hover:text-blue-600 transition bg-gray-50 px-3 py-2 rounded-lg">
                            حسابي
                        </a>
                    @endif

                    <a href="/register" class="bg-blue-600 text-white px-5 py-2 rounded-full hover:bg-blue-700 transition text-sm font-bold shadow-md shadow-blue-200">
                        أضف إعلانك +
                    </a>
                @else
                    {{-- حالة 2: زائر غير مسجل --}}
                    <a href="/login" class="text-gray-600 hover:text-blue-600 transition text-sm font-bold px-2">تسجيل دخول</a>
                    <a href="/register" class="bg-blue-600 text-white px-5 py-2 rounded-full hover:bg-blue-700 transition text-sm font-bold shadow-md shadow-blue-200">
                        ابدأ الآن
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-10">
        {{-- رأس الصفحة --}}
        <div class="mb-10 border-r-4 border-blue-600 pr-6">
            <h1 class="text-3xl font-black text-gray-800">
                نتائج البحث عن: <span class="text-blue-600 italic">"{{ $query }}"</span>
            </h1>
            <p class="text-gray-500 mt-2 font-medium">تم العثور على {{ $listings->total() }} إعلان مطابق</p>
        </div>

        @if($listings->count() > 0)
            {{-- عرض النتائج --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($listings as $listing)
                    @include('frontend.partials.listing-card', ['listing' => $listing])
                @endforeach
            </div>

            {{-- الترقيم --}}
            <div class="mt-16">
                {{ $listings->appends(['query' => $query])->links() }}
            </div>
        @else
            {{-- حالة عدم وجود نتائج --}}
            <div class="text-center py-24 bg-white rounded-3xl shadow-sm border-2 border-dashed border-gray-100">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-50 rounded-full mb-6">
                    <span class="text-5xl">🔍</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">للأسف، لا توجد نتائج مطابقة</h2>
                <p class="text-gray-500 mt-3 max-w-sm mx-auto">جرب تبحث بكلمات تانية، أو استعرض أحدث الإعلانات في الصفحة الرئيسية</p>
                <div class="mt-8">
                    <a href="/" class="bg-blue-600 text-white px-10 py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                        العودة للرئيسية
                    </a>
                </div>
            </div>
        @endif
    </main>

</body>
</html>
