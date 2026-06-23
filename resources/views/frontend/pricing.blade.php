@extends('layouts.frontend')

@section('title', (__('ui.pricing.page_title') ?? 'باقات النقاط والأسعار') . ' — ' . config('app.name', 'Nilex'))

@section('content')

@php
    $resolvePlanKey = static function ($plan): ?string {
        $en = strtolower(trim($plan->name_en ?? ''));
        $ar = trim($plan->name_ar ?? '');

        if (str_contains($en, 'starter') || str_contains($ar, 'مبتد')) {
            return 'starter';
        }
        if (str_contains($en, 'growth') || str_contains($ar, 'نمو')) {
            return 'growth';
        }
        if (str_contains($en, 'pro') || str_contains($ar, 'محترف')) {
            return 'pro_seller';
        }
        if (str_contains($en, 'business') || str_contains($ar, 'أعمال') || str_contains($ar, 'شرك')) {
            return 'business';
        }

        return null;
    };

    $isGrowthPlan = static function ($plan) use ($resolvePlanKey): bool {
        return $resolvePlanKey($plan) === 'growth';
    };

    $isBusinessPlan = static function ($plan) use ($resolvePlanKey): bool {
        return $resolvePlanKey($plan) === 'business';
    };

    $planDescription = static function ($plan) use ($isBusinessPlan): string {
        if ($isBusinessPlan($plan)) {
            return (string) __('ui.pricing.business_description');
        }

        return (string) ($plan->description ?? '');
    };
@endphp

<main class="bg-white min-h-screen" style="padding-top:64px;">

    {{-- ════════════════════════════════════════════
         SECTION 1 — HERO (enhanced, legacy copy preserved in lang keys)
    ════════════════════════════════════════════ --}}
    <section class="border-b border-zinc-100 bg-gradient-to-b from-[#1D9E75]/[0.04] to-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8 sm:py-10 text-center">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-[#1D9E75] mb-3">
                {{ __('ui.pricing.page_title') }}
            </p>
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-zinc-900 leading-snug tracking-tight">
                {{ __('ui.pricing.hero_title') }}
            </h1>
            <p class="text-zinc-500 text-sm sm:text-base mt-3 leading-relaxed max-w-2xl mx-auto">
                {{ __('ui.pricing.hero_subtitle') }}
            </p>

            @if(($registrationWelcomePoints ?? 0) > 0)
                <div class="mt-5 inline-flex items-center gap-2 text-xs sm:text-sm font-semibold text-[#1D9E75] bg-[#1D9E75]/8 border border-[#1D9E75]/20 px-4 py-2 rounded-full">
                    {{ __('ui.pricing.welcome_gift', ['points' => number_format($registrationWelcomePoints)]) }}
                </div>
            @endif
        </div>
    </section>


    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-7 sm:space-y-10">

        {{-- ════════════════════════════════════════════
             SECTION 2 — PRICING PLANS (enhanced cards)
        ════════════════════════════════════════════ --}}
        <section id="plans" x-data="{ refundAccepted: false }">
            @auth
                @if($plans->isNotEmpty())
                    <div class="mb-5 sm:mb-6 bg-[#1D9E75]/[0.05] border border-[#1D9E75]/20 rounded-2xl px-4 py-3.5 sm:px-5 sm:py-4">
                        <label class="flex items-start gap-3 cursor-pointer select-none">
                            <input type="checkbox"
                                   x-model="refundAccepted"
                                   class="mt-0.5 h-4 w-4 shrink-0 rounded border-zinc-300 text-[#1D9E75] focus:ring-[#1D9E75]">
                            <span class="text-xs sm:text-sm text-zinc-700 leading-relaxed">
                                أوافق على
                                <a href="{{ route('legal.show', 'refund-policy') }}"
                                   target="_blank" rel="noopener"
                                   class="font-semibold text-[#1D9E75] underline hover:text-[#178a64]">سياسة الاسترجاع والاسترداد</a>
                            </span>
                        </label>
                    </div>
                @endif
            @endauth

            @if($plans->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center bg-white rounded-3xl border border-zinc-200 shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-[#1D9E75]/10 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-[#1D9E75]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-zinc-800 mb-1">{{ __('ui.pricing.empty_title') }}</h3>
                    <p class="text-zinc-400 text-xs">{{ __('ui.pricing.empty_subtitle') }}</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 items-stretch">
                    @foreach($plans as $plan)
                        @php
                            $isPopular = $isGrowthPlan($plan) || $loop->iteration === 2;
                            $btnClass = 'w-full text-center font-semibold text-sm py-2.5 rounded-xl min-h-[42px] leading-none transition-colors '
                                . ($isPopular
                                    ? 'bg-[#1D9E75] hover:bg-[#178a64] text-white shadow-sm shadow-[#1D9E75]/20'
                                    : 'bg-zinc-100 text-zinc-700 hover:bg-[#1D9E75] hover:text-white');
                        @endphp

                        <div class="relative flex flex-col h-full bg-white rounded-3xl border shadow-sm p-5 sm:p-6 transition-shadow hover:shadow-md
                                    {{ $isPopular ? 'border-[#1D9E75] ring-1 ring-[#1D9E75]/20' : 'border-zinc-200' }}">

                            @if($isPopular)
                                <span class="absolute -top-2.5 start-4 sm:start-5 inline-block text-[10px] font-semibold text-[#1D9E75] bg-white border border-[#1D9E75]/30 px-2.5 py-0.5 rounded-full leading-none shadow-sm">
                                    {{ __('ui.pricing.most_popular') }}
                                </span>
                            @endif

                            <div class="mb-3 {{ $isPopular ? 'pt-1' : '' }}">
                                <h3 class="font-bold text-zinc-900 text-base leading-snug">{{ $plan->name_ar }}</h3>
                                <p class="text-zinc-400 text-[11px] mt-1 min-h-[14px] leading-snug">
                                    {{ $plan->name_en ?? '' }}
                                </p>
                            </div>

                            <div class="space-y-1 mb-4 pb-4 border-b border-zinc-100">
                                <div class="flex items-baseline gap-1">
                                    <span class="font-black text-3xl text-zinc-900 leading-none tabular-nums">
                                        {{ number_format($plan->points) }}
                                    </span>
                                    <span class="text-zinc-400 text-xs font-medium">{{ __('ui.pricing.points_label') }}</span>
                                </div>
                                <div class="flex items-baseline gap-0.5 mt-1">
                                    <span class="font-bold text-lg sm:text-xl text-zinc-900 tabular-nums">
                                        {{ number_format((float) $plan->price, 0) }}
                                    </span>
                                    <span class="text-zinc-400 text-xs">{{ __('ui.pricing.currency') }}</span>
                                </div>
                            </div>

                            <p class="flex-1 text-zinc-500 text-xs leading-relaxed min-h-[3rem] mb-5">
                                {{ $planDescription($plan) }}
                            </p>

                            <div class="mt-auto">
                                @auth
                                    <button
                                        type="button"
                                        x-bind:disabled="!refundAccepted"
                                        x-on:click="refundAccepted && document.getElementById('checkout-{{ $plan->id }}').submit()"
                                        x-bind:class="{ 'opacity-50 cursor-not-allowed': !refundAccepted }"
                                        class="{{ $btnClass }}">
                                        {{ __('ui.pricing.cta_topup') }}
                                    </button>
                                    <form id="checkout-{{ $plan->id }}"
                                          action="{{ route('payment.checkout') }}"
                                          method="POST" class="hidden">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                        <input type="hidden" name="refund_policy_accepted" value="1">
                                    </form>
                                @else
                                    <a href="{{ route('register') }}" class="block {{ $btnClass }}">
                                        {{ __('ui.pricing.cta_register') }}
                                    </a>
                                @endauth
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>


        {{-- ════════════════════════════════════════════
             SECTION 3 — FEATURE MATRIX (new, additive)
        ════════════════════════════════════════════ --}}
        @if($plans->isNotEmpty())
            @include('frontend.pricing._feature-matrix')
        @endif


        {{-- ════════════════════════════════════════════
             SECTION 4 — ANALYTICS MARKETING (existing features only)
        ════════════════════════════════════════════ --}}
        <section id="analytics" class="scroll-mt-24">
            <div class="bg-gradient-to-br from-zinc-50 to-white rounded-3xl border border-zinc-200 p-5 sm:p-8 shadow-sm">
                <div class="max-w-3xl mx-auto text-center mb-6 sm:mb-8">
                    <h2 class="text-lg sm:text-xl font-bold text-zinc-900 tracking-tight">
                        {{ __('ui.pricing.analytics_title') }}
                    </h2>
                    <p class="text-zinc-500 text-xs sm:text-sm mt-2 leading-relaxed">
                        {{ __('ui.pricing.analytics_subtitle') }}
                    </p>
                </div>
                <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 max-w-4xl mx-auto">
                    @foreach([
                        ['M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z', __('ui.pricing.analytics_views')],
                        ['M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', __('ui.pricing.analytics_phone')],
                        ['M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', __('ui.pricing.analytics_whatsapp')],
                        ['M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', __('ui.pricing.analytics_dashboard')],
                        ['M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z', __('ui.pricing.analytics_chart')],
                    ] as [$icon, $label])
                        <li class="flex items-center gap-3 bg-white rounded-2xl border border-zinc-200 px-4 py-3.5 shadow-sm">
                            <span class="w-9 h-9 rounded-xl bg-[#1D9E75]/10 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-[#1D9E75]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $icon }}"/>
                                </svg>
                            </span>
                            <span class="text-xs sm:text-sm font-semibold text-zinc-700 leading-snug">{{ $label }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>


        {{-- ════════════════════════════════════════════
             SECTION 4B — SELLER DASHBOARD (verified capabilities only)
        ════════════════════════════════════════════ --}}
        <section id="seller-dashboard" class="scroll-mt-24">
            <div class="bg-white rounded-3xl border border-zinc-200 p-5 sm:p-8 shadow-sm">
                <div class="max-w-3xl mx-auto text-center mb-6 sm:mb-8">
                    <h2 class="text-lg sm:text-xl font-bold text-zinc-900 tracking-tight">
                        {{ __('ui.pricing.dashboard_title') }}
                    </h2>
                    <p class="text-zinc-500 text-xs sm:text-sm mt-2 leading-relaxed">
                        {{ __('ui.pricing.dashboard_subtitle') }}
                    </p>
                </div>
                <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 max-w-5xl mx-auto">
                    @foreach([
                        __('ui.pricing.dashboard_listing_views'),
                        __('ui.pricing.dashboard_event_views'),
                        __('ui.pricing.dashboard_phone_clicks'),
                        __('ui.pricing.dashboard_whatsapp_clicks'),
                        __('ui.pricing.dashboard_listing_status'),
                        __('ui.pricing.dashboard_performance'),
                        __('ui.pricing.dashboard_points_history'),
                        __('ui.pricing.dashboard_offers'),
                    ] as $feature)
                        <li class="flex items-center gap-2.5 bg-zinc-50 rounded-2xl border border-zinc-200 px-4 py-3.5">
                            <span class="text-[#1D9E75] text-sm shrink-0" aria-hidden="true">✅</span>
                            <span class="text-xs sm:text-sm font-semibold text-zinc-700 leading-snug">{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>


        {{-- ════════════════════════════════════════════
             SECTION 5 — BUSINESS VALUE FUNNEL (marketing only)
        ════════════════════════════════════════════ --}}
        <section id="value">
            <div class="bg-zinc-900 rounded-3xl px-5 sm:px-8 py-6 sm:py-8 text-center shadow-lg">
                <h2 class="text-base sm:text-lg font-bold text-white mb-5 sm:mb-6">
                    {{ __('ui.pricing.value_title') }}
                </h2>
                <div class="flex flex-col sm:flex-row flex-wrap items-center justify-center gap-2 sm:gap-3 text-sm sm:text-base font-semibold">
                    <span class="inline-flex items-center px-4 py-2 rounded-full bg-white/10 text-white border border-white/10">
                        {{ __('ui.pricing.value_step_visibility') }}
                    </span>
                    <span class="text-[#1D9E75] hidden sm:inline" aria-hidden="true">→</span>
                    <span class="inline-flex items-center px-4 py-2 rounded-full bg-white/10 text-white border border-white/10">
                        {{ __('ui.pricing.value_step_leads') }}
                    </span>
                    <span class="text-[#1D9E75] hidden sm:inline" aria-hidden="true">→</span>
                    <span class="inline-flex items-center px-4 py-2 rounded-full bg-white/10 text-white border border-white/10">
                        {{ __('ui.pricing.value_step_conversations') }}
                    </span>
                    <span class="text-[#1D9E75] hidden sm:inline" aria-hidden="true">→</span>
                    <span class="inline-flex items-center px-4 py-2 rounded-full bg-[#1D9E75] text-white shadow-sm">
                        {{ __('ui.pricing.value_step_sales') }}
                    </span>
                </div>
            </div>
        </section>


        {{-- ════════════════════════════════════════════
             SECTION 6 — HOW IT WORKS (preserved)
        ════════════════════════════════════════════ --}}
        <section>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 items-stretch">

                <div class="flex flex-col h-full bg-white rounded-2xl border border-zinc-200 p-4 sm:p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-zinc-900 mb-3.5 leading-snug">
                        كيف تكسب النقاط ببطء؟
                    </h3>
                    <ul class="space-y-2.5">
                        @foreach([
                            ['M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'إنشاء حساب', '+50'],
                            ['M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', 'توثيق الهاتف', '+50'],
                            ['M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z', 'إحالة مستخدم', '+25'],
                            ['M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'نشر إعلان مكتمل', '+3'],
                            ['M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'تسجيل يومي', '+1'],
                        ] as [$icon, $label, $points])
                            <li class="flex items-center gap-2.5 sm:gap-3 text-xs text-zinc-600 leading-snug">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-zinc-50 border border-zinc-200 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $icon }}"/>
                                    </svg>
                                </span>
                                <span class="flex-1 min-w-0">{{ $label }}</span>
                                <span class="font-semibold text-[#1D9E75] shrink-0 tabular-nums">{{ $points }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="flex flex-col h-full bg-white rounded-2xl border border-zinc-200 p-4 sm:p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-zinc-900 mb-3.5 leading-snug">
                        كيف تستثمر النقاط لسرعة البيع؟
                    </h3>
                    <ul class="space-y-2.5">
                        @foreach([
                            ['M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'تمييز الإعلان لمدة يوم', '25 نقطة'],
                            ['M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'تمييز لمدة 3 أيام', '60 نقطة'],
                            ['M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'تمييز لمدة 7 أيام', '120 نقطة'],
                            ['M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'تمييز لمدة 14 يوم', '220 نقطة'],
                        ] as [$icon, $label, $cost])
                            <li class="flex items-center gap-2.5 sm:gap-3 text-xs text-zinc-600 leading-snug">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-zinc-50 border border-zinc-200 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $icon }}"/>
                                    </svg>
                                </span>
                                <span class="flex-1 min-w-0">{{ $label }}</span>
                                <span class="font-semibold text-zinc-500 shrink-0">{{ $cost }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

            </div>
        </section>


        {{-- ════════════════════════════════════════════
             SECTION 7 — TRUST STRIP (preserved)
        ════════════════════════════════════════════ --}}
        <section>
            <div class="bg-zinc-50 rounded-2xl border border-zinc-200 px-4 py-3 sm:px-5 sm:py-3.5">
                <ul class="grid grid-cols-1 gap-2 sm:flex sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-6 sm:gap-y-2 text-[11px] text-zinc-500 leading-relaxed">
                    <li class="flex items-center gap-2 py-0.5 sm:py-0">
                        <span class="shrink-0" aria-hidden="true">✨</span>
                        <span>{{ __('ui.pricing.trust_first_feature') }}</span>
                    </li>
                    <li class="hidden sm:block w-px h-3 bg-zinc-200 shrink-0" aria-hidden="true"></li>
                    <li class="flex items-center gap-2 py-0.5 sm:py-0 border-t border-zinc-200/80 pt-2 sm:border-0 sm:pt-0">
                        <span class="shrink-0" aria-hidden="true">⏳</span>
                        <span>{{ __('ui.pricing.trust_points_validity') }}</span>
                    </li>
                    <li class="hidden sm:block w-px h-3 bg-zinc-200 shrink-0" aria-hidden="true"></li>
                    <li class="flex items-center gap-2 py-0.5 sm:py-0 border-t border-zinc-200/80 pt-2 sm:border-0 sm:pt-0">
                        <span class="shrink-0" aria-hidden="true">💳</span>
                        <span>{{ __('ui.pricing.trust_company_payment') }}</span>
                    </li>
                </ul>
            </div>
        </section>

    </div>

</main>

@endsection
