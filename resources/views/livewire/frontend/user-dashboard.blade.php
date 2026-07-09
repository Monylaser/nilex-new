{{-- resources/views/livewire/frontend/user-dashboard.blade.php --}}
<div class="bg-zinc-50 min-h-screen pb-10" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- ── WELCOME + CTA ───────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-zinc-100 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
             style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-nilex/10 rounded-2xl flex items-center justify-center font-black text-nilex text-2xl shrink-0">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
                <div>
                    <h2 class="text-xl font-black text-zinc-900 leading-tight">
                        {{ __('ui.dashboard.greeting', ['name' => explode(' ', $user->name)[0]]) }}
                    </h2>
                    <div class="flex items-center gap-3 mt-1 flex-wrap">
                        <span class="flex items-center gap-1.5 text-sm font-semibold text-zinc-500">
                            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 14a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z"/></svg>
                            {{ number_format($user->points ?? 0) }} {{ __('ui.dashboard.points_suffix') }}
                        </span>
                        @if($user->is_phone_verified ?? false)
                            <span class="flex items-center gap-1 text-xs font-bold text-nilex bg-nilex/8 px-2 py-0.5 rounded-full">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                {{ __('ui.sections.verified') }}
                            </span>
                        @endif
                        <span class="text-xs text-zinc-400 font-medium">{{ __('ui.dashboard.member_since') }} {{ $user->created_at->diffForHumans() }}</span>
                        {{-- ملخص تقييم البائع (نفس مكوّن النجوم المستخدم في بطاقة الثقة) --}}
                        <x-rating-stars :avg="$user->ratings_avg" :count="$user->ratings_count" />
                    </div>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                @if(\App\Services\AdCampaignService::selfServiceEnabled())
                    <a href="{{ route('dashboard.ads.index') }}"
                       class="flex items-center justify-center gap-2 bg-white border border-amber-200 text-amber-700 hover:bg-amber-50 font-bold py-3 px-5 rounded-xl transition-all text-sm">
                        📢 {{ __('ui.dashboard.my_campaigns') }}
                    </a>
                @endif
                <a href="{{ route('dashboard.leads') }}"
                   class="flex items-center justify-center gap-2 bg-white border border-nilex/30 text-nilex hover:bg-nilex/5 font-bold py-3 px-5 rounded-xl transition-all text-sm">
                    {{ __('ui.leads.nav_link') }}
                </a>
                <a href="{{ route('dashboard.favorites') }}"
                   class="flex items-center justify-center gap-2 bg-white border border-red-200 text-red-500 hover:bg-red-50 font-bold py-3 px-5 rounded-xl transition-all text-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    {{ __('ui.favorites.nav_link') }}
                </a>
                <a href="{{ route('dashboard.purchases') }}"
                   class="relative flex items-center justify-center gap-2 bg-white border border-nilex/30 text-nilex hover:bg-nilex/5 font-bold py-3 px-5 rounded-xl transition-all text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    {{ __('ui.sale_confirmation.nav_link') }}
                    @if(($pendingPurchasesCount ?? 0) > 0)
                        <span class="absolute -top-2 -end-2 bg-amber-500 text-white text-[11px] font-black min-w-[20px] h-5 px-1.5 rounded-full inline-flex items-center justify-center">{{ $pendingPurchasesCount }}</span>
                    @endif
                </a>
                <a href="{{ route('listings.create') }}"
                   class="btn-nilex-primary flex items-center justify-center gap-2 py-3 px-6 rounded-xl text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    {{ __('ui.dashboard.add_listing') }}
                </a>
            </div>
        </div>

        {{-- ── STATS CARDS ─────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-3 lg:grid-cols-6 gap-3">
            {{-- Total --}}
            <div class="bg-white rounded-2xl border border-zinc-100 p-4 text-center" style="box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                <p class="text-xs text-zinc-400 font-semibold mb-1">{{ __('ui.dashboard.stat_all') }}</p>
                <p class="text-2xl font-black text-zinc-900">{{ $stats['total'] }}</p>
            </div>
            {{-- Active --}}
            <div class="bg-nilex/5 rounded-2xl border border-nilex/15 p-4 text-center">
                <p class="text-xs text-nilex font-semibold mb-1">{{ __('ui.dashboard.stat_active') }}</p>
                <p class="text-2xl font-black text-nilex">{{ $stats['active'] }}</p>
            </div>
            {{-- Pending --}}
            <div class="bg-amber-50 rounded-2xl border border-amber-100 p-4 text-center">
                <p class="text-xs text-amber-600 font-semibold mb-1">{{ __('ui.dashboard.stat_pending') }}</p>
                <p class="text-2xl font-black text-amber-700">{{ $stats['pending'] }}</p>
            </div>
            {{-- Rejected --}}
            <div class="bg-red-50 rounded-2xl border border-red-100 p-4 text-center">
                <p class="text-xs text-red-500 font-semibold mb-1">{{ __('ui.dashboard.stat_rejected') }}</p>
                <p class="text-2xl font-black text-red-600">{{ $stats['rejected'] }}</p>
            </div>
            {{-- Views --}}
            <div class="bg-zinc-50 rounded-2xl border border-zinc-200 p-4 text-center">
                <p class="text-xs text-zinc-500 font-semibold mb-1">{{ __('ui.dashboard.stat_views') }}</p>
                <p class="text-2xl font-black text-zinc-800">{{ number_format($stats['views']) }}</p>
            </div>
            {{-- WhatsApp clicks --}}
            <div class="rounded-2xl border p-4 text-center" style="background:rgba(37,211,102,0.06); border-color:rgba(37,211,102,0.2);">
                <p class="text-xs font-semibold mb-1" style="color:#1da851;">{{ __('ui.dashboard.stat_whatsapp') }}</p>
                <p class="text-2xl font-black" style="color:#1da851;">{{ number_format($stats['clicks']) }}</p>
            </div>
        </div>

        {{-- ── VERIFIED / EVENT-BASED ANALYTICS (Phase 2) ─────────────────── --}}
        @if($access['analytics'] ?? false)
            <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <h3 class="text-base font-black text-zinc-900">{{ __('ui.analytics.verified_title') }}</h3>
                    <span class="sr-only">Verified Analytics</span>
                    <span class="text-xs font-bold text-nilex bg-nilex/8 px-2.5 py-1 rounded-full">{{ __('ui.analytics.event_based') }}</span>
                    <span class="sr-only">Event-Based Analytics</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @if($access['event_views'] ?? false)
                        <div class="bg-zinc-50 rounded-2xl border border-zinc-200 p-4 text-center">
                            <p class="text-xs text-zinc-500 font-semibold mb-1">👁 {{ __('ui.pricing.analytics_views') }}</p>
                            <span class="sr-only">Event Views</span>
                            <p class="text-2xl font-black text-zinc-800">{{ number_format($stats['views_events'] ?? 0) }}</p>
                        </div>
                    @else
                        <x-upgrade-prompt
                            :feature-name="__('ui.pricing.features.event_views')"
                            required-tier="growth"
                            :current-tier="$user->plan_tier"
                            class="min-h-[5rem]"
                        />
                    @endif
                    @if($access['phone_clicks'] ?? false)
                        <div class="bg-blue-50 rounded-2xl border border-blue-100 p-4 text-center">
                            <p class="text-xs text-blue-600 font-semibold mb-1">📞 {{ __('ui.pricing.analytics_phone') }}</p>
                            <span class="sr-only">Phone Clicks</span>
                            <p class="text-2xl font-black text-blue-700">{{ number_format($stats['phone_clicks'] ?? 0) }}</p>
                        </div>
                    @else
                        <x-upgrade-prompt
                            :feature-name="__('ui.pricing.features.phone_clicks')"
                            required-tier="pro_seller"
                            :current-tier="$user->plan_tier"
                            class="min-h-[5rem]"
                        />
                    @endif
                    @if($access['whatsapp_clicks'] ?? false)
                        <div class="rounded-2xl border p-4 text-center" style="background:rgba(37,211,102,0.06); border-color:rgba(37,211,102,0.2);">
                            <p class="text-xs font-semibold mb-1" style="color:#1da851;">💬 {{ __('ui.pricing.analytics_whatsapp') }}</p>
                            <span class="sr-only">WhatsApp Clicks</span>
                            <p class="text-2xl font-black" style="color:#1da851;">{{ number_format($stats['whatsapp_clicks_events'] ?? 0) }}</p>
                        </div>
                    @else
                        <x-upgrade-prompt
                            :feature-name="__('ui.pricing.features.whatsapp_clicks')"
                            required-tier="growth"
                            :current-tier="$user->plan_tier"
                            class="min-h-[5rem]"
                        />
                    @endif
                </div>

                {{-- Enhanced stats row --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
                    @if(($access['phone_clicks'] ?? false) && isset($stats['total_phone_reveals']))
                        <div class="bg-white rounded-xl border border-zinc-100 p-3 text-center">
                            <p class="text-xs text-zinc-500 font-semibold mb-1">{{ __('ui.analytics.total_phone_reveals') }}</p>
                            <p class="text-xl font-black text-zinc-800">{{ number_format($stats['total_phone_reveals']) }}</p>
                        </div>
                    @endif
                    @if(($access['whatsapp_clicks'] ?? false) && isset($stats['total_whatsapp_clicks']))
                        <div class="bg-white rounded-xl border border-zinc-100 p-3 text-center">
                            <p class="text-xs text-zinc-500 font-semibold mb-1">{{ __('ui.analytics.total_whatsapp_clicks') }}</p>
                            <p class="text-xl font-black text-zinc-800">{{ number_format($stats['total_whatsapp_clicks']) }}</p>
                        </div>
                    @endif
                    @if(isset($stats['conversion_rate']))
                        <div class="bg-nilex/5 rounded-xl border border-nilex/15 p-3 text-center">
                            <p class="text-xs text-nilex font-semibold mb-1">{{ __('ui.analytics.conversion_rate') }}</p>
                            <p class="text-xl font-black text-nilex">{{ $stats['conversion_rate'] }}%</p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <x-upgrade-prompt
                :feature-name="__('ui.analytics.verified_title')"
                required-tier="growth"
                :current-tier="$user->plan_tier"
            >
                <div class="grid grid-cols-3 gap-3">
                    <div class="h-16 bg-zinc-200 rounded-xl"></div>
                    <div class="h-16 bg-zinc-200 rounded-xl"></div>
                    <div class="h-16 bg-zinc-200 rounded-xl"></div>
                </div>
            </x-upgrade-prompt>
        @endif

        {{-- ── ANALYTICS CHARTS (pro_seller+) ─────────────────────────────── --}}
        @if($access['charts'] ?? false)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- Views per day --}}
                <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                    <h3 class="text-base font-black text-zinc-900 mb-4">{{ __('ui.analytics.chart_views_daily') }}</h3>
                    <div wire:ignore class="relative h-52 w-full"
                         id="viewsDailyChartContainer"
                         data-labels="{{ json_encode($chartData['views_by_day']['labels'] ?? []) }}"
                         data-values="{{ json_encode($chartData['views_by_day']['values'] ?? []) }}">
                        <canvas id="viewsDailyChart"></canvas>
                    </div>
                </div>
                {{-- WhatsApp by listing --}}
                <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                    <h3 class="text-base font-black text-zinc-900 mb-4">{{ __('ui.analytics.chart_whatsapp_listings') }}</h3>
                    <div wire:ignore class="relative h-52 w-full"
                         id="whatsappListingChartContainer"
                         data-labels="{{ json_encode($chartData['whatsapp_by_listing']['labels'] ?? []) }}"
                         data-values="{{ json_encode($chartData['whatsapp_by_listing']['values'] ?? []) }}">
                        <canvas id="whatsappListingChart"></canvas>
                    </div>
                </div>
            </div>
            {{-- Category doughnut --}}
            <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                <h3 class="text-base font-black text-zinc-900 mb-4">{{ __('ui.analytics.chart_category_performance') }}</h3>
                <div wire:ignore class="relative h-56 w-full max-w-md mx-auto"
                     id="categoryChartContainer"
                     data-labels="{{ json_encode($chartData['category_performance']['labels'] ?? []) }}"
                     data-values="{{ json_encode($chartData['category_performance']['values'] ?? []) }}">
                    <canvas id="categoryPerformanceChart"></canvas>
                </div>
            </div>
        @elseif($access['analytics'] ?? false)
            <x-upgrade-prompt
                :feature-name="__('ui.pricing.features.analytics_charts')"
                required-tier="pro_seller"
                :current-tier="$user->plan_tier"
            >
                <div class="grid grid-cols-2 gap-3">
                    <div class="h-32 bg-zinc-200 rounded-xl"></div>
                    <div class="h-32 bg-zinc-200 rounded-xl"></div>
                </div>
            </x-upgrade-prompt>
        @endif

        {{-- ── LEGACY LISTING CHART ───────────────────────────────────────── --}}
        @if($access['analytics'] ?? false)
        <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-black text-zinc-900">{{ __('ui.dashboard.legacy_chart_title') }}</h3>
                <span class="text-xs text-zinc-400 font-medium">{{ __('ui.dashboard.legacy_chart_subtitle') }}</span>
            </div>
            <div wire:ignore
                 id="chartDataContainer"
                 data-labels="{{ json_encode(collect($listings->items())->take(7)->pluck('title')->map(fn($t) => mb_substr($t, 0, 12) . '...')->reverse()->values()) }}"
                 data-views="{{ json_encode(collect($listings->items())->take(7)->pluck('views_count')->reverse()->values()) }}"
                 data-clicks="{{ json_encode(collect($listings->items())->take(7)->pluck('whatsapp_clicks')->reverse()->values()) }}"
                 class="relative h-56 w-full">
                <canvas id="userAnalyticsChart"></canvas>
            </div>
        </div>
        @endif

        {{-- ── INCOMING OFFERS ─────────────────────────────────────────────── --}}
        @if($incomingOffers->count() > 0)
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <h3 class="text-base font-black text-zinc-900">{{ __('ui.dashboard.offers_title') }}</h3>
                    <span class="bg-nilex text-white text-xs px-2.5 py-0.5 rounded-full font-bold">{{ $incomingOffers->count() }}</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($incomingOffers as $offer)
                        <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                            <div class="flex items-start justify-between mb-3">
                                <span class="text-xs font-bold text-nilex bg-nilex/8 px-2.5 py-1 rounded-full">{{ __('ui.dashboard.offer_badge') }}</span>
                                <span class="text-lg font-black text-zinc-900">{{ number_format($offer->amount) }} <span class="text-sm font-bold text-zinc-400">{{ __('ui.sections.currency') }}</span></span>
                            </div>
                            <p class="font-bold text-zinc-800 text-sm leading-snug mb-1 line-clamp-1">{{ $offer->listing->title }}</p>
                            <p class="text-xs text-zinc-500 mb-3">{{ __('ui.dashboard.offer_from') }} <span class="font-semibold text-zinc-700">{{ $offer->sender->name }}</span></p>
                            @if($offer->message)
                                <p class="text-xs text-zinc-500 bg-zinc-50 px-3 py-2 rounded-xl mb-4 italic leading-relaxed">"{{ $offer->message }}"</p>
                            @endif
                            <div class="flex gap-2">
                                <button wire:click="acceptOffer({{ $offer->id }})"
                                        class="btn-nilex-primary flex-1 text-xs py-2.5 rounded-xl">
                                    {{ __('ui.dashboard.offer_accept') }}
                                </button>
                                <button wire:click="rejectOffer({{ $offer->id }})"
                                        class="flex-1 bg-white border border-red-200 hover:bg-red-50 text-red-600 text-xs font-bold py-2.5 rounded-xl transition-all">
                                    {{ __('ui.dashboard.offer_reject') }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── FLASH MESSAGES ──────────────────────────────────────────────── --}}
        @if(session()->has('success'))
            <div class="alert-success text-sm">{{ session('success') }}</div>
        @endif
        @if(session()->has('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl font-bold text-sm">{{ session('error') }}</div>
        @endif

        {{-- ── LISTINGS SECTION ────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-zinc-100 overflow-hidden" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
            <div class="px-5 py-4 border-b border-zinc-100 flex items-center justify-between">
                <h3 class="text-base font-black text-zinc-900">{{ __('ui.dashboard.my_listings') }}</h3>
                <span class="text-sm font-bold text-zinc-500">{{ $listings->total() }} {{ __('ui.dashboard.listing_count_suffix') }}</span>
            </div>

            @if($listings->count() > 0)

                {{-- ── MOBILE CARDS (shown on < md) ─────────────────────────── --}}
                <div class="md:hidden divide-y divide-zinc-50">
                    @foreach($listings as $listing)
                        <div class="p-4 flex gap-3">
                            {{-- Thumbnail --}}
                            <div class="w-16 h-16 rounded-xl bg-zinc-100 overflow-hidden shrink-0">
                                @if($listing->getFirstMediaUrl('images', 'thumb'))
                                    <img src="{{ $listing->getFirstMediaUrl('images', 'thumb') }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                            </div>
                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('listings.show', $listing) }}"
                                   class="font-bold text-zinc-900 hover:text-nilex transition-colors text-sm leading-snug block truncate">
                                    {{ $listing->title }}
                                </a>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <span class="text-sm font-black text-nilex">{{ number_format($listing->price) }} {{ __('ui.sections.currency') }}</span>
                                    @if($listing->status === 'published')
                                        <span class="text-xs font-bold bg-nilex/8 text-nilex px-2 py-0.5 rounded-full">{{ __('ui.dashboard.stat_active') }}</span>
                                    @elseif($listing->status === 'pending')
                                        <span class="text-xs font-bold bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full">{{ __('ui.dashboard.stat_pending') }}</span>
                                    @elseif($listing->status === 'rejected')
                                        <span class="text-xs font-bold bg-red-50 text-red-600 px-2 py-0.5 rounded-full">{{ __('ui.dashboard.stat_rejected') }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 mt-1.5 text-xs text-zinc-400">
                                    <span>👁 {{ $listing->views_count ?? 0 }}</span>
                                    <span>💬 {{ $listing->whatsapp_clicks ?? 0 }}</span>
                                    <span>{{ $listing->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            {{-- Actions --}}
                            <div class="flex flex-col gap-2 shrink-0 justify-center">
                                @if(!$listing->is_featured && $listing->status === 'published')
                                    <button wire:click="featureListing({{ $listing->id }})"
                                            wire:confirm="{{ __('ui.dashboard.confirm_feature') }}"
                                            title="{{ __('ui.dashboard.tooltip_feature') }}"
                                            class="text-zinc-400 hover:text-amber-500 transition-colors p-1.5 rounded-lg hover:bg-amber-50">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                    </button>
                                @elseif($listing->is_featured)
                                    <span class="text-amber-400 p-1.5" title="{{ __('ui.dashboard.tooltip_featured') }}">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    </span>
                                @endif
                                <a href="{{ route('listings.edit', $listing) }}"
                                   title="{{ __('ui.dashboard.tooltip_edit') }}"
                                   class="text-zinc-400 hover:text-nilex transition-colors p-1.5 rounded-lg hover:bg-nilex/5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button wire:click="openClosingModal({{ $listing->id }})"
                                        title="{{ __('ui.dashboard.tooltip_delete') }}"
                                        class="text-zinc-400 hover:text-red-500 transition-colors p-1.5 rounded-lg hover:bg-red-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ── DESKTOP TABLE (shown on md+) ─────────────────────────── --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-zinc-50 border-b border-zinc-100 text-zinc-400 text-xs font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3 font-semibold">{{ __('ui.dashboard.col_listing') }}</th>
                                <th class="px-5 py-3 font-semibold">{{ __('ui.dashboard.col_category') }}</th>
                                <th class="px-5 py-3 font-semibold">{{ __('ui.dashboard.col_price') }}</th>
                                <th class="px-5 py-3 font-semibold">{{ __('ui.dashboard.col_status') }}</th>
                                <th class="px-5 py-3 font-semibold text-center">{{ __('ui.dashboard.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-50">
                            @foreach($listings as $listing)
                                <tr class="hover:bg-zinc-50/60 transition-colors">
                                    {{-- Listing info --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-xl bg-zinc-100 overflow-hidden shrink-0">
                                                @if($listing->getFirstMediaUrl('images', 'thumb'))
                                                    <img src="{{ $listing->getFirstMediaUrl('images', 'thumb') }}" class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <a href="{{ route('listings.show', $listing) }}"
                                                   class="font-bold text-zinc-900 hover:text-nilex transition-colors block max-w-[200px] truncate">
                                                    {{ $listing->title }}
                                                </a>
                                                <div class="flex items-center gap-2 mt-1 text-xs text-zinc-400">
                                                    <span>{{ $listing->created_at->diffForHumans() }}</span>
                                                    <span class="text-zinc-300">·</span>
                                                    <span>👁 {{ $listing->views_count ?? 0 }}</span>
                                                    <span>💬 {{ $listing->whatsapp_clicks ?? 0 }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    {{-- Category --}}
                                    <td class="px-5 py-4">
                                        <span class="text-xs font-semibold bg-zinc-100 text-zinc-600 px-2.5 py-1 rounded-lg">
                                            {{ $listing->category->name ?? __('ui.dashboard.no_category') }}
                                        </span>
                                    </td>
                                    {{-- Price --}}
                                    <td class="px-5 py-4">
                                        <span class="font-black text-zinc-900">{{ number_format($listing->price) }}</span>
                                        <span class="text-xs text-zinc-400 font-medium"> {{ __('ui.sections.currency') }}</span>
                                    </td>
                                    {{-- Status --}}
                                    <td class="px-5 py-4">
                                        @if($listing->status === 'published')
                                            <span class="text-xs font-bold bg-nilex/8 text-nilex px-2.5 py-1 rounded-full">
                                                @if($listing->is_featured)⭐ @endif
                                                {{ __('ui.dashboard.stat_active') }}
                                            </span>
                                        @elseif($listing->status === 'pending')
                                            <span class="text-xs font-bold bg-amber-50 text-amber-700 px-2.5 py-1 rounded-full">{{ __('ui.dashboard.stat_pending') }}</span>
                                        @elseif($listing->status === 'rejected')
                                            <span class="text-xs font-bold bg-red-50 text-red-600 px-2.5 py-1 rounded-full" title="{{ $listing->rejection_reason }}">{{ __('ui.dashboard.stat_rejected') }}</span>
                                        @endif
                                    </td>
                                    {{-- Actions --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            @if(!$listing->is_featured && $listing->status === 'published')
                                                <button wire:click="featureListing({{ $listing->id }})"
                                                        wire:confirm="{{ __('ui.dashboard.confirm_feature') }}"
                                                        title="{{ __('ui.dashboard.tooltip_feature') }}"
                                                        class="text-zinc-400 hover:text-amber-500 transition-colors p-2 rounded-xl hover:bg-amber-50">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                                </button>
                                            @endif
                                            <a href="{{ route('listings.edit', $listing) }}"
                                               title="{{ __('ui.dashboard.tooltip_edit') }}"
                                               class="text-zinc-400 hover:text-nilex transition-colors p-2 rounded-xl hover:bg-nilex/5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                            <button wire:click="openClosingModal({{ $listing->id }})"
                                                    title="{{ __('ui.dashboard.tooltip_delete') }}"
                                                    class="text-zinc-400 hover:text-red-500 transition-colors p-2 rounded-xl hover:bg-red-50">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @else
                {{-- Empty state --}}
                <div class="text-center py-16 px-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-nilex/5 rounded-2xl mb-4">
                        <svg class="w-8 h-8 text-nilex/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <h3 class="text-base font-black text-zinc-800 mb-2">{{ __('ui.dashboard.empty_title') }}</h3>
                    <p class="text-zinc-500 text-sm mb-5">{{ __('ui.dashboard.empty_subtitle') }}</p>
                    <a href="{{ route('listings.create') }}"
                       class="btn-nilex-primary inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        {{ __('ui.dashboard.empty_cta') }}
                    </a>
                </div>
            @endif

            {{-- Pagination --}}
            @if($listings->hasPages())
                <div class="px-5 py-4 border-t border-zinc-50">
                    {{ $listings->links() }}
                </div>
            @endif
        </div>

        {{-- ── LISTING-CLOSING MODAL (Step 1: type selection) ──────────────── --}}
        {{-- Shared, rendered once outside the mobile/desktop loops. Opened from --}}
        {{-- both delete buttons via openClosingModal($listing->id). Visibility   --}}
        {{-- is driven by @if (Livewire morph adds/removes the node) — no Alpine  --}}
        {{-- x-show, so close is deterministic. Escape still closes via Alpine.   --}}
        @if($closingModalOpen)
        <div x-data="{}"
             @keydown.escape.window="$wire.closeClosingModal()"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50"
                 wire:click="closeClosingModal"></div>

            {{-- Panel --}}
            <div class="relative bg-white rounded-2xl w-full max-w-md p-6 space-y-5"
                 style="box-shadow:0 10px 40px rgba(0,0,0,0.18);">

                {{-- ═══ STEP 1: closing-type selection ═══════════════════════════ --}}
                @if($closingStep === 1)
                <div>
                    <h3 class="text-lg font-black text-zinc-900">{{ __('ui.dashboard.close_modal_title') }}</h3>
                    <p class="text-sm text-zinc-500 mt-1">
                        {{ __('ui.dashboard.close_modal_subtitle', ['title' => $closingListingTitle ?? '']) }}
                    </p>
                </div>

                {{-- Three options (toggle cards: clicking the selected one clears it). --}}
                {{-- Non-form <button> + class-driven dot so the selected state is fully  --}}
                {{-- server-rendered — avoids the live `.checked` DOM-property vs morph    --}}
                {{-- problem that a real <input type="radio"> suffers after a toggle-off.  --}}
                <div class="space-y-2.5" role="radiogroup">
                    @foreach (\App\Livewire\Frontend\UserDashboard::CLOSING_TYPES as $closeOption)
                        @php($isSelected = $closingType === $closeOption)
                        <button type="button"
                                role="radio"
                                aria-checked="{{ $isSelected ? 'true' : 'false' }}"
                                wire:click="toggleClosingType('{{ $closeOption }}')"
                                class="w-full flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition-all
                                       {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}
                                       {{ $isSelected ? 'border-nilex bg-nilex/5' : 'border-zinc-200 hover:border-zinc-300' }}">
                            <span class="mt-0.5 w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition-all
                                         {{ $isSelected ? 'border-nilex' : 'border-zinc-300' }}">
                                <span class="w-2 h-2 rounded-full bg-nilex {{ $isSelected ? 'block' : 'hidden' }}"></span>
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-bold text-zinc-900">{{ __('ui.dashboard.close_opt_' . $closeOption) }}</span>
                                <span class="block text-xs text-zinc-500 mt-0.5">{{ __('ui.dashboard.close_opt_' . $closeOption . '_desc') }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 pt-1">
                    <button type="button"
                            wire:click="closeClosingModal"
                            class="flex-1 bg-white border border-zinc-200 hover:bg-zinc-50 text-zinc-600 font-bold py-2.5 rounded-xl text-sm transition-all">
                        {{ __('ui.dashboard.close_cancel') }}
                    </button>
                    <button type="button"
                            wire:click="confirmClosing"
                            @disabled(is_null($closingType))
                            class="btn-nilex-primary flex-1 py-2.5 rounded-xl text-sm disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
                        {{ __('ui.dashboard.close_confirm') }}
                    </button>
                </div>
                @endif

                {{-- ═══ STEP 2: buyer selection (sold_platform only) ════════════ --}}
                @if($closingStep === 2)
                <div>
                    <h3 class="text-lg font-black text-zinc-900">{{ __('ui.sale_confirmation.step2_title') }}</h3>
                    <p class="text-sm text-zinc-500 mt-1">
                        {{ __('ui.sale_confirmation.step2_subtitle', ['title' => $closingListingTitle ?? '']) }}
                    </p>
                </div>

                @if($buyerLeads->isEmpty())
                    {{-- Empty state: no eligible buyers contacted this listing --}}
                    <div class="text-center py-8 px-2">
                        <div class="inline-flex items-center justify-center w-14 h-14 bg-zinc-100 rounded-2xl mb-3">
                            <svg class="w-7 h-7 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                        </div>
                        <h4 class="text-sm font-black text-zinc-800 mb-1">{{ __('ui.sale_confirmation.no_buyers_title') }}</h4>
                        <p class="text-xs text-zinc-500">{{ __('ui.sale_confirmation.no_buyers_subtitle') }}</p>
                    </div>
                @else
                    {{-- Buyer list (toggle cards, same server-rendered pattern as step 1) --}}
                    <div class="space-y-2.5 max-h-72 overflow-y-auto" role="radiogroup">
                        @foreach($buyerLeads as $lead)
                            @php($isBuyerSelected = $selectedBuyerId === $lead->buyer_id)
                            <button type="button"
                                    role="radio"
                                    aria-checked="{{ $isBuyerSelected ? 'true' : 'false' }}"
                                    wire:click="selectBuyer({{ $lead->buyer_id }})"
                                    class="w-full flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all
                                           {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}
                                           {{ $isBuyerSelected ? 'border-nilex bg-nilex/5' : 'border-zinc-200 hover:border-zinc-300' }}">
                                <span class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition-all
                                             {{ $isBuyerSelected ? 'border-nilex' : 'border-zinc-300' }}">
                                    <span class="w-2 h-2 rounded-full bg-nilex {{ $isBuyerSelected ? 'block' : 'hidden' }}"></span>
                                </span>
                                <span class="flex-1 min-w-0">
                                    <span class="flex items-center gap-1.5">
                                        <span class="block text-sm font-bold text-zinc-900 truncate">{{ $lead->buyer?->name ?? '—' }}</span>
                                        @if($lead->buyer?->is_phone_verified)
                                            <svg class="w-3.5 h-3.5 text-nilex shrink-0" fill="currentColor" viewBox="0 0 20 20" title="{{ __('ui.sections.verified') }}"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        @endif
                                    </span>
                                    <span class="block text-xs text-zinc-500 mt-0.5">
                                        {{ $lead->source_type === \App\Models\SellerLead::SOURCE_OFFER ? __('ui.sale_confirmation.buyer_via_offer') : __('ui.sale_confirmation.buyer_via_phone') }}
                                        <span class="text-zinc-300">·</span>
                                        {{ $lead->created_at->diffForHumans() }}
                                    </span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex gap-2 pt-1">
                    <button type="button"
                            wire:click="backToStep1"
                            class="flex-1 bg-white border border-zinc-200 hover:bg-zinc-50 text-zinc-600 font-bold py-2.5 rounded-xl text-sm transition-all">
                        {{ __('ui.sale_confirmation.back') }}
                    </button>
                    @unless($buyerLeads->isEmpty())
                        <button type="button"
                                wire:click="confirmSaleToBuyer"
                                @disabled(is_null($selectedBuyerId))
                                class="btn-nilex-primary flex-1 py-2.5 rounded-xl text-sm disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
                            {{ __('ui.sale_confirmation.confirm_select') }}
                        </button>
                    @endunless
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>
</div>

@section('footer-scripts')
{{-- نسخة مثبَّتة + SRI: أي تلاعب بملف الـ CDN يمنع المتصفح من تنفيذه --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"
        integrity="sha384-jb8JQMbMoBUzgWatfe6COACi2ljcDdZQ2OxczGA3bGNeWe+6DChMTBJemed7ZnvJ"
        crossorigin="anonymous"></script>
<script>
    (function () {
        const chartInstances = {};

        function destroyChart(id) {
            if (chartInstances[id]) {
                chartInstances[id].destroy();
                chartInstances[id] = null;
            }
        }

        function initDashboardChart() {
            const container = document.getElementById('chartDataContainer');
            const canvas    = document.getElementById('userAnalyticsChart');
            if (!container || !canvas) return;

            destroyChart('legacy');

            const labels = JSON.parse(container.getAttribute('data-labels') || '[]');
            const views  = JSON.parse(container.getAttribute('data-views')  || '[]');
            const clicks = JSON.parse(container.getAttribute('data-clicks') || '[]');

            chartInstances.legacy = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: '{{ __('ui.dashboard.chart_views') }}',
                            data: views,
                            backgroundColor: 'rgba(29,158,117,0.15)',
                            borderColor: '#1D9E75',
                            borderWidth: 2,
                            borderRadius: 6,
                        },
                        {
                            label: '{{ __('ui.dashboard.chart_whatsapp_clicks') }}',
                            data: clicks,
                            backgroundColor: 'rgba(37,211,102,0.15)',
                            borderColor: '#25D366',
                            borderWidth: 2,
                            borderRadius: 6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    rtl: true,
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { color: '#a1a1aa' } },
                        x: { grid: { display: false }, ticks: { color: '#a1a1aa' } }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: { font: { family: 'Cairo', size: 11 }, color: '#71717a', boxWidth: 12, padding: 16 }
                        }
                    }
                }
            });
        }

        function initPhase2Charts() {
            const viewsContainer = document.getElementById('viewsDailyChartContainer');
            const viewsCanvas    = document.getElementById('viewsDailyChart');
            if (viewsContainer && viewsCanvas) {
                destroyChart('viewsDaily');
                chartInstances.viewsDaily = new Chart(viewsCanvas, {
                    type: 'line',
                    data: {
                        labels: JSON.parse(viewsContainer.getAttribute('data-labels') || '[]'),
                        datasets: [{
                            label: '{{ __('ui.dashboard.chart_views') }}',
                            data: JSON.parse(viewsContainer.getAttribute('data-values') || '[]'),
                            borderColor: '#1D9E75',
                            backgroundColor: 'rgba(29,158,117,0.1)',
                            fill: true,
                            tension: 0.3,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        rtl: true,
                        scales: { y: { beginAtZero: true } },
                    }
                });
            }

            const waContainer = document.getElementById('whatsappListingChartContainer');
            const waCanvas    = document.getElementById('whatsappListingChart');
            if (waContainer && waCanvas) {
                destroyChart('whatsappListing');
                chartInstances.whatsappListing = new Chart(waCanvas, {
                    type: 'bar',
                    data: {
                        labels: JSON.parse(waContainer.getAttribute('data-labels') || '[]'),
                        datasets: [{
                            label: '{{ __('ui.dashboard.chart_whatsapp_clicks') }}',
                            data: JSON.parse(waContainer.getAttribute('data-values') || '[]'),
                            backgroundColor: 'rgba(37,211,102,0.2)',
                            borderColor: '#25D366',
                            borderWidth: 2,
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        rtl: true,
                        scales: { y: { beginAtZero: true } },
                    }
                });
            }

            const catContainer = document.getElementById('categoryChartContainer');
            const catCanvas    = document.getElementById('categoryPerformanceChart');
            if (catContainer && catCanvas) {
                destroyChart('category');
                const colors = ['#1D9E75','#25D366','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#71717a'];
                chartInstances.category = new Chart(catCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: JSON.parse(catContainer.getAttribute('data-labels') || '[]'),
                        datasets: [{
                            data: JSON.parse(catContainer.getAttribute('data-values') || '[]'),
                            backgroundColor: colors,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        rtl: true,
                    }
                });
            }
        }

        function initAllCharts() {
            initDashboardChart();
            initPhase2Charts();
        }

        document.addEventListener('DOMContentLoaded', initAllCharts);
        document.addEventListener('livewire:navigated', initAllCharts);
    })();
</script>
@endsection
