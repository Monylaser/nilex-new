<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قسم {{ $category->name }} | نايلكس</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-gray-50">

    <nav class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/" class="text-2xl font-black text-blue-600">NILEX</a>

            {{-- شريط بحث سريع --}}
            <div class="hidden md:block flex-1 max-w-sm mx-8">
                <form action="{{ route('listings.search') }}" method="GET" class="relative">
                    <input type="text" name="query" placeholder="بحث في {{ $category->name }}..."
                           class="w-full bg-gray-100 border-none rounded-full py-2 px-5 pr-12 outline-none focus:ring-2 focus:ring-blue-500">
                </form>
            </div>

            <div class="flex items-center gap-4">
                @auth
                    <a href="/dashboard" class="text-gray-600 hover:text-blue-600 font-bold text-sm">حسابي</a>
                @else
                    <a href="/login" class="text-gray-600 hover:text-blue-600 font-bold text-sm">تسجيل دخول</a>
                @endauth
                <a href="/register" class="bg-blue-600 text-white px-5 py-2 rounded-full hover:bg-blue-700 transition text-sm font-bold shadow-md shadow-blue-100">أضف إعلانك</a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-10">
        {{-- عنوان القسم --}}
        <div class="mb-12 flex flex-col md:flex-row md:items-end justify-between border-b pb-8">
            <div>
                <nav class="flex mb-4 text-sm text-gray-400 font-bold">
                    <a href="/" class="hover:text-blue-600 transition">الرئيسية</a>
                    <span class="mx-2">/</span>
                    <span class="text-blue-600">الأقسام</span>
                </nav>
                <h1 class="text-4xl font-black text-gray-800">
                    قسم <span class="text-blue-600 underline decoration-wavy decoration-2 underline-offset-8">{{ $category->name }}</span>
                </h1>
            </div>
            <div class="mt-4 md:mt-0">
                <span class="bg-blue-50 text-blue-700 px-4 py-2 rounded-xl font-bold text-sm border border-blue-100">
                    {{ $listings->total() }} إعلان متاح
                </span>
            </div>
        </div>

        @if($listings->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($listings as $listing)
                    @include('frontend.partials.listing-card', ['listing' => $listing])
                @endforeach
            </div>

            <div class="mt-16">
                {{ $listings->links() }}
            </div>
        @else
            <div class="text-center py-24 bg-white rounded-3xl border-2 border-dashed border-gray-200 shadow-sm">
                <div class="text-7xl mb-6 opacity-40">📂</div>
                <h2 class="text-2xl font-bold text-gray-700 italic">مفيش إعلانات هنا حالياً</h2>
                <p class="text-gray-500 mt-2">كن أول من ينشر إعلان في قسم {{ $category->name }}</p>
                <a href="/register" class="mt-8 inline-block bg-blue-600 text-white px-10 py-3 rounded-2xl font-black hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                    انشر إعلانك مجاناً
                </a>
            </div>
        @endif
    </main>

</body>
</html>
