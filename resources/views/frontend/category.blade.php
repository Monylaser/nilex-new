{{-- resources/views/frontend/category.blade.php --}}

@extends('layouts.frontend')

@section('title', $category->name_ar . ' — إعلانات ' . $category->name_ar . ' في مصر | نايلكس')

@push('meta')
    <meta name="description" content="تصفح {{ number_format($listings->total()) }} إعلان في قسم {{ $category->name_ar }} على منصة نايلكس. بيع واشتري بسهولة وأمان.">
@endpush

@push('styles')
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
@endpush

@section('content')

<main class="bg-white min-h-screen" style="padding-top:64px;" dir="rtl">

    {{-- ── BREADCRUMB ─────────────────────────────────────────────────────── --}}
    <div class="border-b border-zinc-100 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-1.5 py-2.5 text-xs text-zinc-400 font-medium flex-wrap" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                <a href="{{ route('home') }}" class="hover:text-[#1D9E75] transition-colors">الرئيسية</a>
                <svg class="w-3 h-3 rotate-180 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @if($category->parent)
                    <a href="{{ route('category.show', $category->parent->slug) }}" class="hover:text-[#1D9E75] transition-colors">
                        {{ $category->parent->name_ar }}
                    </a>
                    <svg class="w-3 h-3 rotate-180 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @endif
                <span class="text-[#1D9E75] font-semibold">{{ $category->name_ar }}</span>
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- ── CATEGORY HEADER ────────────────────────────────────────────── --}}
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
            <div class="flex items-start gap-3">
                @if($category->icon)
                    <div class="w-11 h-11 bg-[#1D9E75]/10 rounded-xl flex items-center justify-center text-xl shrink-0 border border-[#1D9E75]/10">
                        {{ $category->icon }}
                    </div>
                @endif
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-zinc-900 leading-tight">{{ $category->name_ar }}</h1>
                    @if($category->name_en)
                        <p class="text-xs text-zinc-400 font-medium mt-1">{{ $category->name_en }}</p>
                    @endif
                </div>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-white text-zinc-600 px-3.5 py-1.5 rounded-lg font-semibold text-xs self-start sm:self-auto border border-zinc-200">
                <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>{{ number_format($listings->total()) }} إعلان</span>
            </div>
        </div>

        {{-- ── SUBCATEGORIES STRIP ─────────────────────────────────────────── --}}
        @if($category->children && $category->children->where('is_active', true)->count() > 0)
            <div class="flex gap-2 overflow-x-auto pb-1 mb-6 no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
                @foreach($category->children->where('is_active', true) as $sub)
                    <a href="{{ route('category.show', $sub->slug) }}"
                       class="shrink-0 flex items-center gap-1.5 bg-white border border-zinc-200 hover:border-zinc-300 text-zinc-600 hover:text-[#1D9E75] px-3.5 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition-colors">
                        @if($sub->icon)<span>{{ $sub->icon }}</span>@endif
                        {{ $sub->name_ar }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- ── QUICK SEARCH IN CATEGORY ───────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-zinc-200 p-3 mb-6">
            <form action="{{ route('listings.search') }}" method="GET" class="flex gap-2">
                <input type="hidden" name="category_id" value="{{ $category->id }}">
                <div class="relative flex-1">
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="ابحث داخل {{ $category->name_ar }}..."
                           class="w-full bg-white border border-zinc-200 rounded-xl py-2.5 px-4 pe-10 text-sm font-medium text-zinc-800 focus:outline-none focus:border-zinc-400"
                           style="direction:{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};">
                    <svg class="w-4 h-4 absolute end-3.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button type="submit"
                        class="bg-[#1D9E75] hover:bg-[#178a64] text-white px-5 py-2.5 rounded-xl font-bold text-sm shrink-0">
                    بحث
                </button>
            </form>
        </div>

        {{-- ── LISTINGS GRID ───────────────────────────────────────────────── --}}
        @if($listings->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach($listings as $listing)
                    @include('frontend.partials.listing-card', ['listing' => $listing])
                @endforeach
            </div>

            <div class="mt-8 flex justify-center">
                {{ $listings->links() }}
            </div>

        @else
            {{-- ── EMPTY STATE ─────────────────────────────────────────────── --}}
            <div class="text-center py-16 bg-white rounded-xl border border-zinc-200">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-zinc-50 rounded-xl mb-4 border border-zinc-100">
                    @if($category->icon)
                        <span class="text-2xl opacity-50">{{ $category->icon }}</span>
                    @else
                        <svg class="w-7 h-7 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    @endif
                </div>
                <h2 class="text-base font-bold text-zinc-800 mb-1.5">لا توجد إعلانات هنا حالياً</h2>
                <p class="text-zinc-400 text-sm max-w-xs mx-auto mb-6">
                    كن أول من ينشر إعلاناً في قسم {{ $category->name_ar }}
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('listings.create') }}"
                       class="inline-flex items-center gap-2 bg-[#1D9E75] hover:bg-[#178a64] text-white px-5 py-2.5 rounded-xl font-bold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        أضف إعلانك مجاناً
                    </a>
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center gap-2 border border-zinc-200 hover:border-zinc-300 text-zinc-500 hover:text-zinc-700 px-5 py-2.5 rounded-xl font-semibold text-sm">
                        تصفح أقسام أخرى
                    </a>
                </div>
            </div>
        @endif

    </div>
</main>

@endsection
