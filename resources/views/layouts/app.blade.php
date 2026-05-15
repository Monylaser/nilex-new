<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- 🚀 SEO & Social Sharing Stack --}}
        @stack('meta')

        {{-- 📱 PWA Meta Tags --}}
        <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#111827" media="(prefers-color-scheme: dark)">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="manifest" href="/manifest.json">

        <title>{{ config('app.name', 'Nilex') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Cairo', 'figtree', sans-serif; }
        </style>

        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        </script>
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="min-h-screen">
            
            {{-- الشريط العلوي الجديد الذي يحتوي على التنقل والجرس --}}
            <nav class="bg-white border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        @include('layouts.navigation')

                        {{-- 🔔 جرس الإشعارات اللحظي --}}
                        @auth
                        <div class="relative mr-4" x-data="{ open: false }">
                            <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-blue-600 transition-colors focus:outline-none">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                @if(auth()->user()->unreadNotifications->count() > 0)
                                    <span class="absolute top-2 right-2 block h-3 w-3 rounded-full bg-red-500 ring-2 ring-white"></span>
                                @endif
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute left-0 mt-2 w-72 bg-white rounded-2xl shadow-xl border border-gray-100 z-50 overflow-hidden">
                                <div class="p-3 border-b border-gray-50 bg-gray-50/50">
                                    <span class="font-bold text-gray-800 text-sm">الإشعارات الأخيرة</span>
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                                        <div class="p-4 border-b border-gray-50 hover:bg-blue-50/30 transition-colors">
                                            <p class="text-xs text-gray-700 leading-tight">{{ $notification->data['message'] ?? 'تحديث في حسابك' }}</p>
                                            <span class="text-[10px] text-gray-400 mt-1 block">{{ $notification->created_at->diffForHumans() }}</span>
                                        </div>
                                    @empty
                                        <div class="p-6 text-center text-gray-400 text-xs">لا توجد إشعارات جديدة</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        @endauth
                    </div>
                </div>
            </nav>

            {{-- 🔍 شريط البحث السريع --}}
            <div class="bg-white border-b border-gray-200 py-4 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <form action="{{ route('listings.search') }}" method="GET" class="relative max-w-2xl mx-auto">
                        <input type="text" name="query" value="{{ request('query') }}"
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
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-emerald-600 text-white px-4 py-3 rounded-xl shadow-lg flex items-center justify-between animate-pulse">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span class="font-bold tracking-wide">{{ session('points_added') }}</span>
                        </div>
                        <button @click="show = false" class="text-white hover:text-emerald-100 p-1"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    </div>
                </div>
            @endif

            <main>
                {{ $slot }}
            </main>
        </div>

        @yield('footer-scripts')

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js').then(reg => console.log('SW Registered')).catch(err => console.log('SW Failed'));
                });
            }
        </script>
    </body>
</html>