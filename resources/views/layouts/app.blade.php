<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl"> {{-- ضفنا dir=rtl عشان التنسيق العربي --}}
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        {{-- ضفنا خط كايرو عشان يكون متناسق مع الموقع --}}
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Cairo', 'figtree', sans-serif; }
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="min-h-screen">
           @include('layouts.navigation')

            {{-- 🔍 شريط البحث السريع - شلنا الـ IF عشان يظهر غصب عنه --}}
            <div class="bg-white border-b border-gray-200 py-4 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <form action="{{ route('listings.search') }}" method="GET" class="relative max-w-2xl mx-auto">
                        <input type="text"
                               name="query"
                               value="{{ request('query') }}"
                               placeholder="بتدور على إيه النهاردة؟ (شقة، سيارة، موبايل...)"
                               class="w-full bg-gray-100 border-transparent rounded-2xl py-3 px-6 pr-12 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-right"
                               style="direction: rtl;">

                        <button type="submit" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>


            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-right">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            {{-- 🎖️ نظام الإشعارات اللحظية للنقاط --}}
            @if (session()->has('points_added'))
                <div x-data="{ show: true }"
                     x-show="show"
                     x-init="setTimeout(() => show = false, 5000)"
                     class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-emerald-600 text-white px-4 py-3 rounded-xl shadow-lg flex items-center justify-between animate-pulse">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-bold tracking-wide">{{ session('points_added') }}</span>
                        </div>
                        <button @click="show = false" class="text-white hover:text-emerald-100 p-1">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
            @endif

                       <main>
                {{ $slot }}
            </main>
        </div>

        {{-- 🔥 يخرج الـ scripts الإضافية من أي صفحة --}}
        @yield('footer-scripts')

    </body>
</html>
