@extends('layouts.frontend')

@section('title', (__('ui.pricing.page_title') ?? 'باقات النقاط والأسعار') . ' — ' . config('app.name', 'Nilex'))

@section('content')

<main class="bg-white min-h-screen" style="padding-top:64px;">

    {{-- ════════════════════════════════════════════
         SECTION 1 — HERO
    ════════════════════════════════════════════ --}}
    <section class="border-b border-zinc-100">
        <div class="max-w-xl mx-auto px-4 sm:px-6 py-5 sm:py-6 text-center">
            <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 leading-snug tracking-tight">
                زوّد فرص بيع إعلانك
            </h1>
            <p class="text-zinc-500 text-sm mt-2 leading-relaxed max-w-md mx-auto">
                اشحن رصيد نايلكس لزيادة ظهور إعلانك والوصول لمشترين أكثر بسرعة.
            </p>
        </div>
    </section>


    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-7 sm:space-y-9">

        {{-- ════════════════════════════════════════════
             SECTION 2 — PRICING PLANS
        ════════════════════════════════════════════ --}}
        <section id="plans">
            @if($plans->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center bg-white rounded-3xl border border-zinc-200 shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-[#1D9E75]/10 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-[#1D9E75]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-zinc-800 mb-1">الباقات قيد الإعداد</h3>
                    <p class="text-zinc-400 text-xs">نعمل على إعداد باقات مميزة لك، ترقبنا قريباً!</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 items-stretch">
                    @foreach($plans as $plan)
                        @php
                            $isPopular = $loop->iteration == 2;
                            $btnClass = 'w-full text-center font-semibold text-sm py-2.5 rounded-xl min-h-[42px] leading-none '
                                . ($isPopular
                                    ? 'bg-[#1D9E75] hover:bg-[#178a64] text-white'
                                    : 'bg-zinc-100 text-zinc-700 hover:bg-[#1D9E75] hover:text-white');
                        @endphp

                        <div class="relative flex flex-col h-full bg-white rounded-3xl border shadow-sm p-5 sm:p-6
                                    {{ $isPopular ? 'border-[#1D9E75]' : 'border-zinc-200' }}">

                            @if($isPopular)
                                <span class="absolute -top-2.5 start-4 sm:start-5 inline-block text-[10px] font-semibold text-[#1D9E75] bg-white border border-[#1D9E75]/30 px-2.5 py-0.5 rounded-full leading-none">
                                    الأكثر طلباً
                                </span>
                            @endif

                            <div class="mb-3 {{ $isPopular ? 'pt-1' : '' }}">
                                <h3 class="font-semibold text-zinc-900 text-sm leading-snug">{{ $plan->name_ar }}</h3>
                                <p class="text-zinc-400 text-[11px] mt-1 min-h-[14px] leading-snug">
                                    {{ $plan->name_en ?? '' }}
                                </p>
                            </div>

                            <div class="space-y-1 mb-4">
                                <div class="flex items-baseline gap-1">
                                    <span class="font-bold text-2xl text-zinc-900 leading-none tabular-nums">
                                        {{ number_format($plan->points) }}
                                    </span>
                                    <span class="text-zinc-400 text-xs">نقطة</span>
                                </div>
                                <div class="flex items-baseline gap-0.5">
                                    <span class="font-semibold text-base sm:text-lg text-zinc-900 tabular-nums">
                                        {{ number_format((float) $plan->price, 0) }}
                                    </span>
                                    <span class="text-zinc-400 text-xs">ج.م</span>
                                </div>
                            </div>

                            <p class="flex-1 text-zinc-500 text-xs leading-relaxed line-clamp-2 min-h-[2.5rem] mb-4">
                                {{ $plan->description }}
                            </p>

                            <div class="mt-auto">
                                @auth
                                    <button
                                        onclick="document.getElementById('checkout-{{ $plan->id }}').submit()"
                                        class="{{ $btnClass }}">
                                        شحن الرصيد
                                    </button>
                                    <form id="checkout-{{ $plan->id }}"
                                          action="{{ route('payment.checkout') }}"
                                          method="POST" class="hidden">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    </form>
                                @else
                                    <a href="{{ route('register') }}" class="block {{ $btnClass }}">
                                        سجّل واشحن
                                    </a>
                                @endauth
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>


        {{-- ════════════════════════════════════════════
             SECTION 3 — HOW IT WORKS
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
             SECTION 4 — TRUST STRIP
        ════════════════════════════════════════════ --}}
        <section>
            <div class="bg-zinc-50 rounded-2xl border border-zinc-200 px-4 py-3 sm:px-5 sm:py-3.5">
                <ul class="grid grid-cols-1 gap-2 sm:flex sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-6 sm:gap-y-2 text-[11px] text-zinc-500 leading-relaxed">
                    <li class="flex items-center gap-2 py-0.5 sm:py-0">
                        <span class="shrink-0" aria-hidden="true">✨</span>
                        <span>أول إعلان مميز لك مجاناً!</span>
                    </li>
                    <li class="hidden sm:block w-px h-3 bg-zinc-200 shrink-0" aria-hidden="true"></li>
                    <li class="flex items-center gap-2 py-0.5 sm:py-0 border-t border-zinc-200/80 pt-2 sm:border-0 sm:pt-0">
                        <span class="shrink-0" aria-hidden="true">⏳</span>
                        <span>النقاط صالحة لمدة 90 يوماً.</span>
                    </li>
                    <li class="hidden sm:block w-px h-3 bg-zinc-200 shrink-0" aria-hidden="true"></li>
                    <li class="flex items-center gap-2 py-0.5 sm:py-0 border-t border-zinc-200/80 pt-2 sm:border-0 sm:pt-0">
                        <span class="shrink-0" aria-hidden="true">💳</span>
                        <span>الدفع المباشر متاح للشركات.</span>
                    </li>
                </ul>
            </div>
        </section>

    </div>

</main>

@endsection
