<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $listing->title }} | نايلكس</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-gray-50">

    <nav class="bg-white shadow-sm p-4 mb-6">
        <div class="container mx-auto">
            <a href="/" class="text-blue-600 font-bold">← العودة للرئيسية</a>
        </div>
    </nav>

    <main class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white p-2 rounded-xl shadow-sm">
                    @php $images = $listing->images; @endphp
                    <img src="{{ asset('storage/' . ($images[0]['url'] ?? $images[0])) }}" class="w-full h-[400px] object-cover rounded-lg">

                    <div class="grid grid-cols-4 gap-2 mt-2">
                        @foreach(array_slice($images, 1) as $img)
                            <img src="{{ asset('storage/' . ($img['url'] ?? $img)) }}" class="h-24 w-full object-cover rounded-md cursor-pointer opacity-70 hover:opacity-100">
                        @endforeach
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <h1 class="text-2xl font-bold mb-4">{{ $listing->title }}</h1>
                    <p class="text-gray-600 leading-relaxed whitespace-pre-line">{{ $listing->description }}</p>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border-t-4 border-blue-600">
                    <div class="text-3xl font-black text-blue-600 mb-4 text-center">
                        {{ number_format($listing->price) }} <span class="text-sm font-normal">EGP</span>
                    </div>

                    <div class="space-y-3">
                        <a href="tel:{{ $listing->user?->phone }}" class="block w-full bg-green-500 text-white text-center py-3 rounded-lg font-bold hover:bg-green-600">
                            📞 اتصل الآن
                        </a>
                        <a href="https://wa.me/{{ $listing->user?->phone }}" class="block w-full border border-green-500 text-green-600 text-center py-3 rounded-lg font-bold hover:bg-green-50">
                            💬 واتساب
                        </a>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <p class="text-sm text-gray-500">الموقع: <strong>{{ $listing->location?->name_ar }}</strong></p>
                        <p class="text-sm text-gray-500">الحالة: <strong>{{ $listing->condition == 'new' ? 'جديد' : 'مستعمل' }}</strong></p>
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
