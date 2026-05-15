<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتائج البحث عن: {{ $query ?? 'كل الإعلانات' }} | نايلكس</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-gray-50">

    {{-- ── 1. الناف بار ───────────────────────────────────────────────────────── --}}
    <nav class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            {{-- اللوجو --}}
            <a href="/" class="text-2xl font-black text-blue-600 tracking-tighter">NILEX</a>

            {{-- روابط المستخدم الديناميكية --}}
            <div class="flex items-center gap-3">
                @auth
                    @if(auth()->user()->email == 'admin@gmail.com')
                        <a href="/admin" class="hidden sm:block text-sm font-bold text-red-600 bg-red-50 px-3 py-2 rounded-lg hover:bg-red-100 transition">
                            لوحة التحكم (Admin)
                        </a>
                    @else
                        <a href="/dashboard" class="hidden sm:block text-sm font-bold text-gray-600 hover:text-blue-600 transition bg-gray-50 px-3 py-2 rounded-lg">
                            حسابي
                        </a>
                    @endif

                    <a href="{{ route('listings.create') }}" class="bg-blue-600 text-white px-5 py-2 rounded-full hover:bg-blue-700 transition text-sm font-bold shadow-md shadow-blue-200">
                        أضف إعلانك +
                    </a>
                @else
                    <a href="/login" class="text-gray-600 hover:text-blue-600 transition text-sm font-bold px-2">تسجيل دخول</a>
                    <a href="/register" class="bg-blue-600 text-white px-5 py-2 rounded-full hover:bg-blue-700 transition text-sm font-bold shadow-md shadow-blue-200">
                        ابدأ الآن
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-10">
        
        {{-- ── 2. محرك البحث المتقدم والخرائط ───────────────────────────────────────── --}}
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 mb-10">
            <h1 class="text-2xl font-black text-gray-800 mb-6 border-r-4 border-blue-600 pr-4">
                @if($query) نتائج البحث عن: <span class="text-blue-600 italic">"{{ $query }}"</span> @else تصفح جميع الإعلانات @endif
            </h1>

            <form action="{{ route('listings.search') }}" method="GET" id="geoSearchForm" class="flex flex-col lg:flex-row gap-4">
                {{-- حقول مخفية للإحداثيات الجغرافية --}}
                <input type="hidden" name="lat" id="latInput" value="{{ request('lat') }}">
                <input type="hidden" name="lng" id="lngInput" value="{{ request('lng') }}">

                {{-- حقل كلمة البحث --}}
                <div class="relative flex-1">
                    <input type="text" name="q" value="{{ $query }}" placeholder="بتدور على إيه؟ (سيارة، شقة، موبايل...)"
                           class="w-full bg-gray-50 border border-gray-200 rounded-2xl py-4 px-5 pr-12 focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-bold text-gray-800 transition">
                    <svg class="w-6 h-6 absolute right-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <div class="flex gap-4">
                    {{-- تحديد نطاق المسافة --}}
                    <select name="radius" class="bg-gray-50 border border-gray-200 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-blue-500 outline-none font-bold text-gray-700 cursor-pointer">
                        <option value="5" {{ request('radius') == 5 ? 'selected' : '' }}>نطاق 5 كيلو</option>
                        <option value="10" {{ request('radius') == 10 ? 'selected' : '' }}>نطاق 10 كيلو</option>
                        <option value="50" {{ request('radius') == 50 || !request('radius') ? 'selected' : '' }}>نطاق 50 كيلو</option>
                        <option value="200" {{ request('radius') == 200 ? 'selected' : '' }}>نطاق 200 كيلو</option>
                    </select>

                    {{-- زرار GPS السحري --}}
                    <button type="button" id="getLocationBtn" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 px-6 py-4 rounded-2xl font-black flex items-center gap-2 transition shadow-sm whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>الأقرب لي</span>
                    </button>

                    {{-- زرار البحث العادي --}}
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-2xl font-black shadow-md shadow-blue-200 transition">
                        بحث
                    </button>
                </div>
            </form>
            
            @if(request('lat') && request('lng'))
                <p class="text-sm text-emerald-600 font-bold mt-4 bg-emerald-50 inline-block px-3 py-1 rounded-lg">
                    📍 يتم عرض النتائج الأقرب لموقعك الجغرافي الحالي بنجاح.
                </p>
            @endif
        </div>

        {{-- ── 3. عرض النتائج ─────────────────────────────────────────────────────── --}}
        @if($listings->count() > 0)
            <p class="text-gray-500 mb-6 font-medium text-lg">تم العثور على <span class="font-black text-gray-900">{{ $listings->total() }}</span> إعلان مطابق</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($listings as $listing)
                    @include('frontend.partials.listing-card', ['listing' => $listing])
                @endforeach
            </div>

            {{-- الترقيم مع الحفاظ على كل متغيرات البحث --}}
            <div class="mt-16">
                {{ $listings->appends(request()->query())->links() }}
            </div>
        @else
            {{-- حالة عدم وجود نتائج --}}
            <div class="text-center py-24 bg-white rounded-3xl shadow-sm border-2 border-dashed border-gray-100">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-50 rounded-full mb-6">
                    <span class="text-5xl">🔍</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">للأسف، لا توجد نتائج مطابقة</h2>
                <p class="text-gray-500 mt-3 max-w-sm mx-auto">جرب توسع نطاق المسافة، أو ابحث بكلمات مختلفة.</p>
                <div class="mt-8">
                    <a href="{{ route('listings.search') }}" class="bg-blue-600 text-white px-10 py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                        عرض كل الإعلانات
                    </a>
                </div>
            </div>
        @endif
    </main>

    {{-- ── 4. كود الـ JavaScript للخرائط ─────────────────────────────────────── --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const getLocationBtn = document.getElementById('getLocationBtn');
            const latInput = document.getElementById('latInput');
            const lngInput = document.getElementById('lngInput');
            const searchForm = document.getElementById('geoSearchForm');

            if (getLocationBtn) {
                getLocationBtn.addEventListener('click', function() {
                    // تغيير شكل الزرار أثناء التحميل
                    const originalContent = this.innerHTML;
                    this.innerHTML = '<span class="animate-spin text-xl">⏳</span> <span>جاري التحديد...</span>';
                    this.classList.add('opacity-75', 'cursor-not-allowed');
                    this.disabled = true;

                    // التحقق من دعم المتصفح للـ GPS
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            // في حالة النجاح
                            function(position) {
                                latInput.value = position.coords.latitude;
                                lngInput.value = position.coords.longitude;
                                // إرسال الفورم أوتوماتيكياً
                                searchForm.submit();
                            },
                            // في حالة رفض المستخدم أو حدوث خطأ
                            function(error) {
                                alert('عذراً! يجب السماح للمتصفح بمعرفة موقعك لتشغيل ميزة (الأقرب لي). 📍');
                                getLocationBtn.innerHTML = originalContent;
                                getLocationBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                                getLocationBtn.disabled = false;
                            },
                            { enableHighAccuracy: true, timeout: 10000 }
                        );
                    } else {
                        alert('متصفحك لا يدعم خاصية تحديد الموقع.');
                        this.innerHTML = originalContent;
                        this.disabled = false;
                    }
                });
            }
        });
    </script>
</body>
</html>