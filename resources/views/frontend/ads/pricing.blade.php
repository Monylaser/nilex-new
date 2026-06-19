@extends('layouts.frontend')

@section('title', 'المساحات الإعلانية — أعلن معنا على Nilex')

@push('meta')
    <meta name="description" content="اعرض إعلانك على منصة نايلكس — بانر رئيسي، داخل القائمة، صفحة القسم، أو صفحة تسجيل الدخول. وصل لآلاف المستخدمين النشطين يومياً في مصر.">
@endpush

@section('content')

@php
    $currency = $pricing['currency'] ?? 'EGP';
    $durations = $pricing['durations'] ?? [];
    $phase5APlacements = ['hero_top', 'home_feed', 'category_page', 'login_page', 'popup'];
    $placementCards = collect($pricing['placements'] ?? [])->only($phase5APlacements);
    $ctaUrl = auth()->check()
        ? route('dashboard.ads.create')
        : route('login');
@endphp

<main class="bg-white min-h-screen" dir="rtl" style="padding-top:64px;">

    {{-- HERO --}}
    <section class="border-b border-zinc-100 bg-gradient-to-b from-[#1D9E75]/[0.05] to-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-16 text-center">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-[#1D9E75] mb-4">
                فرص إعلانية
            </p>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-zinc-900 leading-tight">
                أعلن معنا على <span class="text-[#1D9E75]">Nilex</span>
            </h1>
            <p class="text-zinc-500 text-sm sm:text-base mt-4 leading-relaxed max-w-2xl mx-auto">
                وصل لآلاف المستخدمين النشطين يومياً في مصر
            </p>

            @if($selfServiceEnabled)
                <a href="{{ $ctaUrl }}"
                   class="inline-flex items-center gap-2 mt-10 bg-[#1D9E75] hover:bg-[#178a64] text-white font-bold px-6 py-3 rounded-xl text-sm transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    أعلن معنا الآن
                </a>
            @else
                <a href="mailto:ads@nilex.com"
                   class="inline-flex items-center gap-2 mt-10 bg-[#1D9E75] hover:bg-[#178a64] text-white font-bold px-6 py-3 rounded-xl text-sm transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    تواصل معنا للإعلان
                </a>
            @endif
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-14 sm:space-y-20">

        {{-- AD SPACES --}}
        <section>
            <div class="text-center mb-8 sm:mb-10">
                <h2 class="text-xl sm:text-2xl font-bold text-zinc-900">مساحات الإعلان المتاحة</h2>
                <p class="text-zinc-500 text-sm mt-2">اختر المكان المناسب لرسالتك التسويقية</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6">
                @foreach($placementCards as $key => $placement)
                    <div class="bg-white rounded-2xl border border-zinc-200 p-6 hover:border-[#1D9E75]/30 hover:shadow-md transition-all duration-200">
                        <h3 class="font-bold text-zinc-900 text-lg mb-2">
                            {{ $placement['label_ar'] ?? $placement['label'] ?? $key }}
                        </h3>
                        @if(! empty($placement['description_ar']))
                            <p class="text-zinc-500 text-sm leading-relaxed mb-4">
                                {{ $placement['description_ar'] }}
                            </p>
                        @endif
                        <p class="text-xs text-zinc-400 font-medium bg-zinc-50 rounded-lg px-3 py-2 border border-zinc-100">
                            {{ $placement['dimensions'] ?? '—' }}
                            | {{ $placement['formats'] ?? '—' }}
                            | Max {{ $placement['max_size'] ?? '2MB' }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- PRICING TABLE --}}
        <section>
            <div class="text-center mb-8 sm:mb-10">
                <h2 class="text-xl sm:text-2xl font-bold text-zinc-900">أسعار الإعلان</h2>
                <p class="text-zinc-500 text-sm mt-2">جميع الأسعار بالجنيه المصري — حسب المساحة والمدة</p>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-zinc-200">
                <table class="w-full text-sm text-right min-w-[640px]">
                    <thead class="bg-zinc-50 text-zinc-600">
                        <tr>
                            <th class="px-4 py-3 font-bold">المساحة</th>
                            @foreach($durations as $days => $duration)
                                <th class="px-4 py-3 font-bold whitespace-nowrap">
                                    {{ $duration['label_ar'] ?? $duration['label'] ?? $days . ' days' }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach($placementCards as $key => $placement)
                            <tr class="hover:bg-zinc-50/50">
                                <td class="px-4 py-3 font-semibold text-zinc-900">
                                    {{ $placement['label_ar'] ?? $placement['label'] ?? $key }}
                                </td>
                                @foreach($durations as $days => $duration)
                                    <td class="px-4 py-3 text-zinc-700 whitespace-nowrap">
                                        @php $price = $placement['prices'][$days] ?? null; @endphp
                                        @if($price !== null)
                                            {{ number_format($price, 0) }} {{ $currency }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- HOW IT WORKS --}}
        <section class="bg-zinc-50 rounded-2xl border border-zinc-100 p-6 sm:p-10">
            <div class="text-center mb-8">
                <h2 class="text-xl sm:text-2xl font-bold text-zinc-900">كيف يعمل؟</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8 max-w-3xl mx-auto">
                @if($selfServiceEnabled)
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-[#1D9E75]/10 text-[#1D9E75] font-black text-lg flex items-center justify-center mx-auto mb-3">١</div>
                        <h3 class="font-bold text-zinc-900 text-sm mb-1">أنشئ حملتك</h3>
                        <p class="text-zinc-400 text-xs">اختر الموضع والمدة وارفع صورة إعلانك</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-[#1D9E75]/10 text-[#1D9E75] font-black text-lg flex items-center justify-center mx-auto mb-3">٢</div>
                        <h3 class="font-bold text-zinc-900 text-sm mb-1">ادفع عبر Paymob</h3>
                        <p class="text-zinc-400 text-xs">دفع آمن وفوري عبر بوابة Paymob</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-[#1D9E75]/10 text-[#1D9E75] font-black text-lg flex items-center justify-center mx-auto mb-3">٣</div>
                        <h3 class="font-bold text-zinc-900 text-sm mb-1">موافقة وظهور</h3>
                        <p class="text-zinc-400 text-xs">بعد مراجعة الفريق يبدأ إعلانك بالظهور</p>
                    </div>
                @else
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-[#1D9E75]/10 text-[#1D9E75] font-black text-lg flex items-center justify-center mx-auto mb-3">١</div>
                        <h3 class="font-bold text-zinc-900 text-sm mb-1">تواصل معنا</h3>
                        <p class="text-zinc-400 text-xs">أرسل طلبك عبر البريد الإلكتروني</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-[#1D9E75]/10 text-[#1D9E75] font-black text-lg flex items-center justify-center mx-auto mb-3">٢</div>
                        <h3 class="font-bold text-zinc-900 text-sm mb-1">ارفع صورة إعلانك</h3>
                        <p class="text-zinc-400 text-xs">نراجع التصميم ونفعّل الحملة</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-[#1D9E75]/10 text-[#1D9E75] font-black text-lg flex items-center justify-center mx-auto mb-3">٣</div>
                        <h3 class="font-bold text-zinc-900 text-sm mb-1">ابدأ الظهور فوراً</h3>
                        <p class="text-zinc-400 text-xs">إعلانك يظهر للمستخدمين مباشرة</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- CTA --}}
        <section>
            <div class="rounded-2xl bg-gradient-to-br from-[#1D9E75] to-[#085041] px-6 sm:px-10 py-10 sm:py-12 text-center text-white">
                <h2 class="text-xl sm:text-2xl font-black mb-2">مستعد تبدأ؟</h2>
                <p class="text-white/80 text-sm mb-6 max-w-md mx-auto">
                    @if($selfServiceEnabled)
                        أنشئ حملتك من لوحة التحكم وادفع إلكترونياً
                    @else
                        فريقنا جاهز يساعدك تختار المساحة المناسبة وتطلق حملتك
                    @endif
                </p>
                @if($selfServiceEnabled)
                    <a href="{{ $ctaUrl }}"
                       class="inline-flex items-center gap-2 bg-white text-[#1D9E75] hover:bg-zinc-50 font-bold px-6 py-3 rounded-xl text-sm transition-colors">
                        أعلن معنا الآن
                    </a>
                @else
                    <a href="mailto:ads@nilex.com"
                       class="inline-flex items-center gap-2 bg-white text-[#1D9E75] hover:bg-zinc-50 font-bold px-6 py-3 rounded-xl text-sm transition-colors">
                        راسلنا الآن
                    </a>
                @endif
            </div>
        </section>

    </div>
</main>

@endsection
