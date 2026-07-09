{{-- resources/views/frontend/search-results.blade.php --}}

@extends('layouts.frontend')

@section('title', $query
    ? __('ui.search.title_with_query', ['query' => $query, 'brand' => __('ui.footer.brand')])
    : __('ui.search.title_browse_all', ['brand' => __('ui.footer.brand')]))

@push('meta')
    <meta name="description" content="{{ __('ui.search.meta_description') }}">
@endpush

@push('styles')
    <style>
        .listing-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
        }
        .listing-card:hover .card-image {
            opacity: 0.92;
        }
        .card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
@endpush

@section('content')

<div class="bg-white min-h-screen" style="padding-top:64px;">

    {{-- ── BREADCRUMB ─────────────────────────────────────────────────────── --}}
    <div class="border-b border-zinc-100 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-1.5 py-3 text-sm text-zinc-400 font-medium flex-wrap" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                <a href="{{ route('home') }}" class="hover:text-[#1D9E75] transition-colors">{{ __('ui.footer.link_home') }}</a>
                <svg class="w-3 h-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-zinc-600" dir="auto">
                    @if($query)
                        {{ __('ui.search.breadcrumb_results_for', ['query' => $query]) }}
                    @else
                        {{ __('ui.search.breadcrumb_all_listings') }}
                    @endif
                </span>
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- ── ADVANCED SEARCH + FILTERS ───────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-zinc-200 p-4 sm:p-5 mb-6">
            <form action="{{ route('listings.search') }}" method="GET" id="searchForm">
                {{-- Geo coordinates (hidden) --}}
                <input type="hidden" name="lat" id="latInput" value="{{ request('lat') }}">
                <input type="hidden" name="lng" id="lngInput" value="{{ request('lng') }}">

                {{-- Main keyword row --}}
                <div class="flex gap-2.5 mb-4">
                    <div class="relative flex-1">
                        <input type="text" name="q" value="{{ $query }}"
                               placeholder="{{ __('ui.search.placeholder') }}"
                               class="w-full bg-white border border-zinc-200 rounded-xl py-3 px-4 pe-11 font-semibold text-zinc-800 text-sm focus:outline-none focus:border-zinc-400"
                               style="direction:{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};"
                               autofocus>
                        <svg class="w-4.5 h-4.5 absolute end-3.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <button type="submit"
                            class="btn-nilex-primary px-5 sm:px-7 py-3 rounded-xl text-sm shrink-0">
                        {{ __('ui.search.submit') }}
                    </button>
                </div>

                {{-- Filters row --}}
                <div class="flex flex-wrap gap-2.5 items-center">

                    {{-- Category filter --}}
                    @php
                        $allCategories = \App\Models\Category::whereNull('parent_id')
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->get();
                    @endphp
                    <select name="category_id"
                            class="bg-white border border-zinc-200 rounded-xl py-2.5 px-3.5 text-sm font-semibold text-zinc-700 focus:outline-none focus:border-zinc-400 cursor-pointer"
                            onchange="this.form.submit()">
                        <option value="">{{ __('ui.search.all_categories') }}</option>
                        @foreach($allCategories as $cat)
                            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                                @if($cat->icon){{ $cat->icon }} @endif{{ $cat->name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Price range --}}
                    <div class="flex items-center gap-1 bg-white border border-zinc-200 rounded-xl px-3 py-0.5">
                        <input type="number" name="min_price" value="{{ $minPrice }}" placeholder="{{ __('ui.search.price_from') }}"
                               class="w-16 bg-transparent py-2 text-sm font-semibold text-zinc-700 focus:outline-none placeholder-zinc-400 text-center">
                        <span class="text-zinc-300 text-xs select-none">—</span>
                        <input type="number" name="max_price" value="{{ $maxPrice }}" placeholder="{{ __('ui.search.price_to') }}"
                               class="w-16 bg-transparent py-2 text-sm font-semibold text-zinc-700 focus:outline-none placeholder-zinc-400 text-center">
                        <span class="text-xs text-zinc-400 font-medium select-none me-1">{{ __('ui.sections.currency') }}</span>
                    </div>

                    {{-- GPS button --}}
                    <button type="button" id="getLocationBtn"
                            class="btn-nilex-primary flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span id="locBtnLabel">{{ __('ui.search.near_me') }}</span>
                    </button>

                    {{-- Radius (only when geo is active) --}}
                    @if(request('lat') && request('lng'))
                        <select name="radius"
                                class="bg-white border border-zinc-200 rounded-xl py-2.5 px-3.5 text-sm font-semibold text-zinc-700 focus:outline-none focus:border-zinc-400 cursor-pointer"
                                onchange="this.form.submit()">
                            <option value="5"   {{ $radius == 5   ? 'selected' : '' }}>{{ __('ui.search.radius_km', ['km' => 5]) }}</option>
                            <option value="10"  {{ $radius == 10  ? 'selected' : '' }}>{{ __('ui.search.radius_km', ['km' => 10]) }}</option>
                            <option value="50"  {{ $radius == 50  ? 'selected' : '' }}>{{ __('ui.search.radius_km', ['km' => 50]) }}</option>
                            <option value="200" {{ $radius == 200 ? 'selected' : '' }}>{{ __('ui.search.radius_km', ['km' => 200]) }}</option>
                        </select>
                    @endif

                    {{-- Sort --}}
                    @include('frontend.partials.listing-sort-select', ['sort' => $sort ?? \App\Support\ListingSort::DEFAULT])

                    {{-- Clear filters --}}
                    @if($query || $categoryId || $minPrice || $maxPrice || request('lat'))
                        <a href="{{ route('listings.search') }}"
                           class="flex items-center gap-1.5 text-zinc-500 hover:text-zinc-700 border border-zinc-200 hover:border-zinc-300 px-3.5 py-2.5 rounded-xl text-sm font-semibold">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            {{ __('ui.search.clear_filters') }}
                        </a>
                    @endif

                </div>

                {{-- Geo active indicator --}}
                @if(request('lat') && request('lng') && ($sort ?? \App\Support\ListingSort::DEFAULT) === \App\Support\ListingSort::DEFAULT)
                    <div class="flex items-center gap-1.5 mt-3 text-xs text-[#1D9E75] font-semibold">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                        {{ __('ui.search.geo_sorted') }}
                        <a href="{{ route('listings.search', array_merge(request()->except(['lat', 'lng']), [])) }}" class="text-zinc-400 hover:text-zinc-600 underline">{{ __('ui.search.geo_cancel') }}</a>
                    </div>
                @endif
            </form>
        </div>

        {{-- ── RESULTS ─────────────────────────────────────────────────────── --}}
        @if($listings->count() > 0)

            {{-- Results summary bar --}}
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <p class="text-sm text-zinc-500 font-medium">
                    <span class="font-black text-zinc-900">
                        {{ trans_choice('ui.search.results_count', $listings->total(), ['count' => number_format($listings->total())]) }}
                    </span>
                    @if($query)
                        {{ __('ui.search.results_for_query') }}
                        "<span class="text-[#1D9E75] font-bold" dir="auto">{{ $query }}</span>"
                    @endif
                    @if($categoryId && $allCategories->firstWhere('id', $categoryId))
                        {{ __('ui.search.results_in_category') }} <span dir="auto">{{ $allCategories->firstWhere('id', $categoryId)->name }}</span>
                    @endif
                </p>
            </div>

            {{-- Listings grid — 2 cols mobile, 3 tablet, 4 desktop --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($listings as $listing)
                    <a href="{{ route('listings.show', $listing) }}" class="listing-card block group">

                        {{-- Image --}}
                        <div class="relative bg-zinc-100 rounded-xl overflow-hidden" style="aspect-ratio:4/3;">
                            @if($listing->getFirstMediaUrl('images', 'card'))
                                <img src="{{ $listing->getFirstMediaUrl('images', 'card') }}"
                                     alt="{{ $listing->title }}"
                                     class="card-image"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-zinc-50">
                                    <svg class="w-8 h-8 text-zinc-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif

                            @if($listing->condition)
                                <div class="absolute bottom-2 end-2">
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full text-white"
                                          style="{{ $listing->condition === 'new' ? 'background:rgba(29,158,117,0.9);' : 'background:rgba(0,0,0,0.55);' }}">
                                        {{ $listing->condition === 'new' ? __('ui.sections.condition_new') : __('ui.sections.condition_used') }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Card body: PRICE → TITLE → LOCATION · TIME --}}
                        <div class="pt-2 pb-1 px-0.5">
                            <div class="mb-0.5">
                                @if($listing->price > 0)
                                    <span class="font-bold text-zinc-900 text-sm">
                                        {{ number_format($listing->price) }}
                                        <span class="font-normal text-zinc-400 text-[10px] ms-0.5">{{ __('ui.sections.currency') }}</span>
                                    </span>
                                @else
                                    <span class="text-zinc-400 font-medium text-xs">{{ __('ui.sections.on_contact') }}</span>
                                @endif
                            </div>

                            <h3 class="font-semibold text-zinc-900 text-[13px] line-clamp-1 leading-snug group-hover:text-[#1D9E75]" dir="auto">
                                {{ $listing->title }}
                            </h3>

                            <div class="flex items-center gap-1 mt-0.5 text-[11px] text-zinc-400">
                                @if($listing->location)
                                    <span class="line-clamp-1" dir="auto">{{ $listing->location->name ?? '' }}</span>
                                    <span>·</span>
                                @endif
                                <span class="shrink-0">{{ $listing->created_at->locale(app()->getLocale())->diffForHumans() }}</span>
                                @if(($listing->views_count ?? 0) > 0)
                                    <span class="ms-auto text-zinc-300 shrink-0">{{ number_format($listing->views_count) }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-8 flex justify-center">
                {{ $listings->appends(request()->query())->links() }}
            </div>

        @else
            {{-- ── EMPTY STATE ─────────────────────────────────────────────── --}}
            <div class="text-center py-20 bg-white rounded-2xl border border-zinc-200">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-zinc-50 rounded-2xl mb-5 border border-zinc-100">
                    <svg class="w-10 h-10 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-black text-zinc-800 mb-2">{{ __('ui.search.empty_title') }}</h2>
                <p class="text-zinc-500 text-sm max-w-xs mx-auto mb-7">
                    @if($query)
                        {{ __('ui.search.empty_with_query') }}
                    @else
                        {{ __('ui.search.empty_no_query') }}
                    @endif
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('listings.search') }}"
                       class="btn-nilex-primary inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm">
                        {{ __('ui.search.view_all_listings') }}
                    </a>
                    <a href="{{ route('listings.create') }}"
                       class="inline-flex items-center gap-2 border border-zinc-200 hover:border-zinc-300 text-zinc-700 hover:text-[#1D9E75] px-6 py-3 rounded-xl font-bold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        {{ __('ui.search.add_listing') }}
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchI18n = {!! json_encode([
            'geoUnsupported' => __('ui.search.geo_unsupported'),
            'nearMeLoading' => __('ui.search.near_me_loading'),
            'nearMe' => __('ui.search.near_me'),
            'geoDenied' => __('ui.search.geo_denied'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
        const btn      = document.getElementById('getLocationBtn');
        const label    = document.getElementById('locBtnLabel');
        const latInput = document.getElementById('latInput');
        const lngInput = document.getElementById('lngInput');
        const form     = document.getElementById('searchForm');

        if (!btn) return;

        btn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert(searchI18n.geoUnsupported);
                return;
            }
            label.textContent = searchI18n.nearMeLoading;
            btn.disabled = true;
            btn.style.opacity = '0.7';

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    latInput.value = pos.coords.latitude;
                    lngInput.value = pos.coords.longitude;
                    form.submit();
                },
                function () {
                    alert(searchI18n.geoDenied);
                    label.textContent = searchI18n.nearMe;
                    btn.disabled = false;
                    btn.style.opacity = '1';
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    });
</script>
@endpush
