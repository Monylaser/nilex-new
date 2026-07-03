@extends('layouts.frontend')

@section('title', __('ui.title'))

@push('styles')
    <style>
        .hero-search-bar {
            display: flex;
            align-items: center;
            background: #fff;
            border-radius: 0.75rem;
            padding: 0.375rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
        }
        .hero-search-bar:focus-within {
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22), 0 0 0 2px rgba(20, 165, 168, 0.35);
        }

        .listing-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
        }
        .listing-card:hover .card-photo {
            opacity: 0.92;
        }

        .card-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
@endpush

@section('content')

@php
    $iconMap = [
        'سيار'   => 'car.png',
        'عقار'   => 'buildings.png', 'شقة' => 'buildings.png', 'منزل' => 'buildings.png',
        'موبايل' => 'mobile.png', 'هاتف' => 'mobile.png', 'إلكترون' => 'mobile.png', 'جوال' => 'mobile.png',
        'جهاز'  => 'appliance.png', 'أجهزة' => 'appliance.png', 'منزلي' => 'appliance.png',
        'ملابس' => 'fashion.png', 'أزياء' => 'fashion.png', 'موضة' => 'fashion.png',
        'حيوان' => 'pets.png',
        'أطفال' => 'kids.png', 'طفل' => 'kids.png',
        'هواية' => 'hobbies.png', 'رياضة' => 'hobbies.png', 'هوايات' => 'hobbies.png',
        'صناع'  => 'industrial.png', 'معدات' => 'industrial.png', 'تجهيز' => 'industrial.png',
    ];
@endphp

<section class="bg-nilex-bg px-4 py-4">
    <div class="max-w-3xl mx-auto">
        <form action="{{ route('listings.search') }}" method="GET">
            <div class="hero-search-bar" dir="ltr">
                <input type="text" name="q"
                       placeholder="{{ __('ui.hero.search_placeholder') ?? 'بتدور على إيه؟' }}"
                       class="flex-1 bg-white border-0 border-transparent outline-none ring-0 focus:ring-0 focus:outline-none text-zinc-800 placeholder-zinc-400 py-3 px-4 text-sm min-w-0"
                       style="direction:{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};">
                <button type="submit"
                        class="btn-nilex-primary flex items-center gap-1.5 text-sm px-5 py-2.5 rounded-lg shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    {{ __('ui.hero.search_btn') ?? 'بحث' }}
                </button>
            </div>
        </form>
    </div>
</section>


{{-- ══════════════════════════════════════════
     STICKY CATEGORIES — horizontal scrollable pills
══════════════════════════════════════════ --}}
<div class="bg-nilex-bg border-b border-zinc-100 sticky top-16 z-40">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="flex flex-nowrap overflow-x-auto no-scrollbar gap-4 px-4 py-2"
             dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
            @foreach($categories as $cat)
                @php
                    $iconUrl = $cat->getFirstMediaUrl('icon') ?: null;
                    if (! $iconUrl) {
                        foreach ($iconMap as $kw => $file) {
                            if (str_contains($cat->name_ar, $kw)) {
                                $iconUrl = asset('images/categories/'.$file);
                                break;
                            }
                        }
                    }
                @endphp
                <a href="{{ route('category.show', $cat) }}"
                   class="flex flex-col items-center gap-1 shrink-0 hover:opacity-80 transition-opacity">
                    <img src="{{ $iconUrl ?? '' }}" alt="{{ $cat->name }}" class="w-10 h-10 object-contain">
                    <span class="text-xs text-nilex-ink font-medium whitespace-nowrap">{{ $cat->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

<x-ad-banner placement="hero_top" wrapper-class="w-full" />


{{-- ══════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════ --}}
<main class="max-w-7xl mx-auto px-4 py-8 space-y-12">

    {{-- ── FEATURED LISTINGS ── --}}
    @if($featuredListings->count())
    <section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-zinc-900">{{ __('ui.sections.featured') ?? 'إعلانات مميزة' }}</h2>
            <a href="{{ route('listings.search') }}"
               class="text-sm font-semibold text-nilex underline underline-offset-2">
                {{ __('ui.sections.view_all') ?? 'كل الإعلانات' }}
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
            @foreach($featuredListings as $listing)
                <div x-data="{ saved: false }" class="relative">
                    <a href="{{ route('listings.show', $listing) }}" class="listing-card block group">

                        {{-- Image — aspect-ratio 4/3 --}}
                        <div class="relative bg-zinc-100 rounded-xl overflow-hidden" style="aspect-ratio:4/3;">
                            @if($listing->getFirstMediaUrl('images', 'card'))
                                <img src="{{ $listing->getFirstMediaUrl('images', 'card') }}"
                                     alt="{{ $listing->title }}"
                                     class="card-photo"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-zinc-50">
                                    <svg class="w-10 h-10 text-zinc-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif

                            {{-- Featured badge --}}
                            <div class="absolute top-2.5 end-2.5">
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full"
                                      style="background:#f59e0b; color:#451a03;">
                                    {{ __('ui.sections.featured_badge') ?? 'مميز' }}
                                </span>
                            </div>

                            {{-- Condition badge --}}
                            @if($listing->condition)
                                <div class="absolute bottom-2.5 start-2.5">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full text-white"
                                          style="{{ $listing->condition === 'new' ? 'background:rgba(5,150,105,0.9);' : 'background:rgba(0,0,0,0.55);' }}">
                                        {{ $listing->condition === 'new' ? (__('ui.sections.condition_new') ?? 'جديد') : (__('ui.sections.condition_used') ?? 'مستعمل') }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Card body: TITLE → LOCATION · TIME → PRICE --}}
                        <div class="pt-3 pb-1 px-0.5">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-semibold text-zinc-900 text-sm line-clamp-1 leading-snug">
                                    {{ $listing->title }}
                                </h3>
                                @if($listing->category)
                                    <span class="text-[10px] font-semibold text-zinc-400 shrink-0 line-clamp-1" style="max-width:70px;">
                                        {{ $listing->category->name }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-1 mt-0.5 text-[12px] text-zinc-400">
                                @if($listing->location)
                                    <span class="line-clamp-1">{{ $listing->location->name ?? '' }}</span>
                                    <span>·</span>
                                @endif
                                <span class="shrink-0">{{ $listing->created_at->locale(app()->getLocale())->diffForHumans() }}</span>
                                @if(($listing->views_count ?? 0) > 0)
                                    <span class="ms-auto flex items-center gap-0.5 shrink-0">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        {{ number_format($listing->views_count) }}
                                    </span>
                                @endif
                            </div>

                            <div class="mt-1.5">
                                @if($listing->price > 0)
                                    <span class="price-tag text-[14px]">
                                        {{ number_format($listing->price) }}
                                        <span class="font-normal text-zinc-500 text-xs ms-0.5">{{ __('ui.sections.currency') ?? 'ج.م' }}</span>
                                    </span>
                                @else
                                    <span class="text-zinc-400 font-medium text-sm">{{ __('ui.sections.price_on_contact') ?? 'تواصل للسعر' }}</span>
                                @endif

                                @if($listing->user?->email_verified_at)
                                    <span class="inline-flex items-center gap-1 ms-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    <span class="text-[10px] text-zinc-400 font-medium">{{ __('ui.sections.verified') }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>

                    {{-- Wishlist heart --}}
                    <button @click="saved = !saved"
                            class="absolute top-2.5 start-2.5 w-7 h-7 rounded-full bg-white/90 flex items-center justify-center shadow-sm z-10">
                        <svg class="w-3.5 h-3.5"
                             :class="saved ? 'text-red-500' : 'text-zinc-400'"
                             :fill="saved ? 'currentColor' : 'none'"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    </section>
    @endif


    {{-- ── LATEST LISTINGS ── --}}
    <section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-zinc-900">{{ __('ui.sections.latest') ?? 'أحدث الإعلانات' }}</h2>
            @if($latestListings->total() > 0)
                <span class="text-sm text-zinc-400">
                    {{ number_format($latestListings->total()) }} {{ __('ui.sections.listing_count_suffix') ?? 'إعلان' }}
                </span>
            @endif
        </div>

        @if($latestListings->count())
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach($latestListings as $listing)
                    <div x-data="{ saved: false }" class="relative">
                        <a href="{{ route('listings.show', $listing) }}" class="listing-card block group">

                            {{-- Image — aspect-ratio square --}}
                            <div class="relative bg-zinc-100 rounded-xl overflow-hidden" style="aspect-ratio:1/1;">
                                @if($listing->getFirstMediaUrl('images', 'card'))
                                    <img src="{{ $listing->getFirstMediaUrl('images', 'card') }}"
                                         alt="{{ $listing->title }}"
                                         class="card-photo"
                                         loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-zinc-50">
                                        <svg class="w-8 h-8 text-zinc-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif

                                {{-- Condition badge --}}
                                @if($listing->condition)
                                    <div class="absolute bottom-2 end-2">
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full text-white"
                                              style="{{ $listing->condition === 'new' ? 'background:rgba(5,150,105,0.9);' : 'background:rgba(0,0,0,0.55);' }}">
                                            {{ $listing->condition === 'new' ? (__('ui.sections.condition_new') ?? 'جديد') : (__('ui.sections.condition_used') ?? 'مستعمل') }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- Card body: TITLE → LOCATION · TIME → PRICE --}}
                            <div class="pt-2 pb-1 px-0.5">
                                <h3 class="font-semibold text-zinc-900 text-[13px] line-clamp-1 leading-snug">
                                    {{ $listing->title }}
                                </h3>

                                <div class="flex items-center gap-1 mt-0.5 text-[11px] text-zinc-400">
                                    @if($listing->location)
                                        <span class="line-clamp-1" style="max-width:56px;">{{ $listing->location->name ?? '' }}</span>
                                        <span>·</span>
                                    @endif
                                    <span class="shrink-0">{{ $listing->created_at->locale(app()->getLocale())->diffForHumans() }}</span>
                                    @if(($listing->views_count ?? 0) > 0)
                                        <span class="ms-auto text-zinc-300 shrink-0">{{ number_format($listing->views_count) }}</span>
                                    @endif
                                </div>

                                <div class="mt-1">
                                    @if($listing->price > 0)
                                        <span class="price-tag text-sm">
                                            {{ number_format($listing->price) }}
                                            <span class="font-normal text-zinc-400 text-[10px] ms-0.5">{{ __('ui.sections.currency') ?? 'ج.م' }}</span>
                                        </span>
                                    @else
                                        <span class="text-zinc-400 font-medium text-xs">{{ __('ui.sections.on_contact') ?? 'تواصل' }}</span>
                                    @endif

                                    @if($listing->user?->email_verified_at)
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 ms-1 align-middle" title="{{ __('ui.sections.verified') }}"></span>
                                    @endif
                                </div>
                            </div>
                        </a>

                        {{-- Wishlist heart --}}
                        <button @click="saved = !saved"
                                class="absolute top-2 start-2 w-6 h-6 rounded-full bg-white/90 flex items-center justify-center shadow-sm z-10">
                            <svg class="w-3 h-3"
                                 :class="saved ? 'text-red-500' : 'text-zinc-400'"
                                 :fill="saved ? 'currentColor' : 'none'"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    </div>
                    @if($loop->iteration % 8 === 0 && config('features.self_service_ads'))
                        <x-ad-banner
                            placement="home_feed"
                            wrapper-class="col-span-2 sm:col-span-3 md:col-span-4"
                        />
                    @endif
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($latestListings->hasPages())
                <div class="mt-8 flex justify-center">
                    {{ $latestListings->links() }}
                </div>
            @endif

        @else
            <div class="rounded-xl border border-dashed border-zinc-200 bg-white overflow-hidden">
                <div class="flex flex-col sm:flex-row items-center gap-4 px-6 py-6 border-b border-zinc-100">
                    <div class="w-10 h-10 rounded-xl bg-zinc-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div class="text-center sm:text-start">
                        <h3 class="text-sm font-bold text-zinc-800">{{ __('ui.empty.no_listings_title') ?? 'لا توجد إعلانات بعد' }}</h3>
                        <p class="text-zinc-400 text-xs mt-0.5">{{ __('ui.empty.no_listings_subtitle') ?? 'كن أول من ينشر إعلانه على منصة نايلكس' }}</p>
                    </div>
                    <a href="{{ route('listings.create') }}"
                       class="btn-nilex-primary sm:ms-auto inline-flex items-center gap-1.5 text-xs px-4 py-2 rounded-xl shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('ui.empty.add_free') ?? 'أضف إعلانك مجاناً' }}
                    </a>
                </div>
                <div class="px-6 py-4">
                    <p class="text-[10px] font-semibold text-zinc-400 mb-3 uppercase tracking-wide">{{ __('ui.sections.browse_by_category') }}</p>
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach($categories->take(8) as $cat)
                            <a href="{{ route('category.show', $cat) }}"
                               class="text-xs font-semibold text-zinc-600 bg-zinc-50 hover:bg-nilex/8 hover:text-nilex border border-zinc-200 hover:border-nilex/20 px-3 py-1.5 rounded-lg">
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    </div>
                    <p class="text-[10px] font-semibold text-zinc-400 mb-2 uppercase tracking-wide">{{ __('ui.sections.search_in') }}</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach([['q'=>'شقق', 'loc'=>'القاهرة'], ['q'=>'سيارات', 'loc'=>'القاهرة'], ['q'=>'موبايل', 'loc'=>'الإسكندرية'], ['q'=>'أثاث', 'loc'=>null]] as $trend)
                            <a href="{{ route('listings.search', array_filter(['q' => $trend['q']])) }}"
                               class="text-[10px] font-medium text-zinc-500 hover:text-nilex bg-zinc-50 border border-zinc-100 px-2.5 py-1 rounded-lg">
                                {{ $trend['q'] }}{{ $trend['loc'] ? ' ' . __('ui.misc.in') . ' ' . $trend['loc'] : '' }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </section>


    {{-- ── TRUST STRIP ── --}}
    <section class="border-t border-zinc-100 pt-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach(__('ui.trust.cards') ?? [] as $card)
                <div class="flex items-center gap-3 bg-white rounded-xl border border-zinc-100 px-4 py-3">
                    <div class="w-9 h-9 rounded-xl bg-zinc-50 flex items-center justify-center text-lg shrink-0">
                        {{ $card['icon'] }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-zinc-900 text-xs line-clamp-1">{{ $card['title'] }}</p>
                        <p class="text-zinc-400 text-[10px] leading-relaxed line-clamp-2 mt-0.5">{{ $card['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>


    {{-- ── CTA STRIP ── --}}
    <section>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 rounded-2xl border border-zinc-200 px-6 py-5">
            <div class="text-center sm:text-start">
                <p class="font-black text-zinc-900 text-base">{{ __('ui.cta.title') ?? 'أضف إعلانك مجاناً اليوم' }}</p>
                <p class="text-zinc-400 text-sm mt-0.5">{{ __('ui.cta.subtitle') ?? 'انضم لآلاف البائعين والمشترين على منصة نايلكس' }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('listings.create') }}"
                   class="btn-nilex-primary inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl text-sm shrink-0">
                    {{ __('ui.cta.btn_primary') ?? 'أضف الآن' }}
                </a>
                @guest
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-1.5 font-semibold text-zinc-700 border border-zinc-200 hover:border-zinc-400 px-5 py-2.5 rounded-xl text-sm shrink-0">
                        {{ __('ui.cta.btn_secondary') ?? 'سجّل مجاناً' }}
                    </a>
                @endguest
            </div>
        </div>
    </section>

</main>

@endsection

@push('scripts')
<script>
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) window.location.reload();
    });
</script>
@endpush
