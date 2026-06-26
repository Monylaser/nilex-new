<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @stack('meta')

    <title>@yield('title', config('app.name', 'Nilex'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        #home-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            background: #ffffff;
            border-bottom: 1px solid #ebebeb;
        }

        .search-pill {
            border: 1px solid #dddddd;
            border-radius: 40px;
            background: #fff;
        }
        .search-pill:focus-within {
            border-color: #b0b0b0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
    </style>

    @stack('styles')
</head>
<body class="bg-white antialiased" style="font-family:'Cairo',sans-serif;">

{{-- ══════════════════════════════════════════
     NAVBAR — Public frontend header
══════════════════════════════════════════ --}}
<nav id="home-nav" x-data="{ mobileOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4 h-16">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="shrink-0">
                <img src="{{ asset('images/logo/download.png') }}"
                     class="h-12 w-auto"
                     alt="Nilex نايلكس">
            </a>

            {{-- Search — desktop (hidden on homepage; the hero search is the primary search there) --}}
            @unless(request()->routeIs('home'))
            <form action="{{ route('listings.search') }}" method="GET"
                  class="hidden md:block flex-1 max-w-md mx-auto">
                <div class="search-pill flex items-center">
                    <input type="text" name="q"
                           placeholder="{{ __('ui.nav.search_placeholder') ?? 'ابحث...' }}"
                           class="flex-1 bg-transparent text-zinc-800 placeholder-zinc-400 py-2.5 px-5 text-sm focus:outline-none"
                           style="direction:{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}; min-width:0;">
                    <button type="submit"
                            class="w-8 h-8 me-1 rounded-full bg-[#1D9E75] flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </form>
            @endunless

            {{-- Right actions --}}
            <div class="flex items-center gap-2 ms-auto">

                {{-- Language --}}
                @php $nextLocale = app()->getLocale() === 'ar' ? 'en' : 'ar'; @endphp
                <form method="POST" action="{{ route('language.switch', $nextLocale) }}" class="hidden sm:block">
                    @csrf
                    <button type="submit"
                            class="text-xs font-bold text-zinc-500 border border-zinc-200 px-2.5 py-1 rounded-lg hover:border-zinc-400">
                        {{ app()->getLocale() === 'ar' ? 'EN' : 'ع' }}
                    </button>
                </form>

                {{-- Auth --}}
                @guest
                    <a href="{{ route('login') }}"
                       class="hidden sm:block text-sm font-semibold text-zinc-700 hover:text-zinc-900">
                        {{ __('ui.nav.login') ?? 'دخول' }}
                    </a>
                @endguest
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="hidden sm:flex items-center gap-1.5 text-sm font-semibold text-zinc-700 hover:text-zinc-900">
                        <div class="w-7 h-7 rounded-full bg-[#1D9E75]/10 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-[#1D9E75]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        {{ __('ui.nav.my_account') ?? 'حسابي' }}
                    </a>
                @endauth

                {{-- Add Listing CTA --}}
                <a href="{{ route('listings.create') }}"
                   class="flex items-center gap-1.5 bg-[#1D9E75] hover:bg-[#178a64] text-white text-sm font-bold px-4 py-2 rounded-xl shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span class="hidden sm:inline">{{ __('ui.nav.add_listing') ?? 'أضف إعلان' }}</span>
                    <span class="sm:hidden">{{ __('ui.nav.add_short') }}</span>
                </a>

                {{-- Mobile menu toggle --}}
                <button @click="mobileOpen = !mobileOpen"
                        class="sm:hidden p-1.5 rounded-lg text-zinc-600 hover:bg-zinc-50">
                    <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile dropdown --}}
    <div x-show="mobileOpen"
         class="sm:hidden bg-white border-t border-zinc-100"
         style="display:none;">
        <div class="px-4 py-3 space-y-1">
            @unless(request()->routeIs('home'))
            <form action="{{ route('listings.search') }}" method="GET" class="mb-3">
                <div class="search-pill flex items-center">
                    <input type="text" name="q"
                           placeholder="{{ __('ui.nav.search_placeholder') ?? 'ابحث...' }}"
                           class="flex-1 bg-transparent py-2.5 px-4 text-sm focus:outline-none placeholder-zinc-400 text-zinc-800"
                           style="direction:{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};">
                    <button type="submit"
                            class="w-8 h-8 me-1 rounded-full bg-[#1D9E75] flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </form>
            @endunless

            @guest
                <a href="{{ route('login') }}" class="block px-3 py-2.5 text-sm font-semibold text-zinc-700 hover:text-[#1D9E75] rounded-lg hover:bg-zinc-50">
                    {{ __('ui.nav.login') ?? 'تسجيل الدخول' }}
                </a>
                <a href="{{ route('register') }}" class="block px-3 py-2.5 text-sm font-semibold text-zinc-700 hover:text-[#1D9E75] rounded-lg hover:bg-zinc-50">
                    {{ __('ui.nav.register') ?? 'إنشاء حساب' }}
                </a>
            @endguest
            @auth
                <a href="{{ route('dashboard') }}" class="block px-3 py-2.5 text-sm font-semibold text-zinc-700 hover:text-[#1D9E75] rounded-lg hover:bg-zinc-50">
                    {{ __('ui.nav.my_account') ?? 'حسابي' }}
                </a>
            @endauth

            <div class="pt-2 border-t border-zinc-100">
                <form method="POST" action="{{ route('language.switch', $nextLocale) }}">
                    @csrf
                    <button type="submit" class="text-xs font-bold text-zinc-500 border border-zinc-200 px-3 py-1.5 rounded-lg hover:border-zinc-400">
                        {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

@hasSection('content')
    @yield('content')
@else
    {{ $slot ?? '' }}
@endif

@if(config('features.self_service_ads'))
    <x-ad-popup />
@endif

<x-footer />

@stack('scripts')
@yield('footer-scripts')

</body>
</html>
