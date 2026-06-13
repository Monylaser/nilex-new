@extends('layouts.frontend')

@section('title', 'المساحات الإعلانية — أعلن معنا على Nilex')

@push('meta')
    <meta name="description" content="اعرض إعلانك على منصة نايلكس — بانر رئيسي، داخل القائمة، أو صفحة القسم. وصل لآلاف المستخدمين النشطين يومياً في مصر.">
@endpush

@section('content')

<main class="bg-white min-h-screen" dir="rtl" style="padding-top:64px;">

    {{-- ════════════════════════════════════════════
         SECTION 1 — HERO
    ════════════════════════════════════════════ --}}
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

            {{-- Stats --}}
            <div class="flex flex-wrap items-center justify-center gap-6 sm:gap-10 mt-8">
                <div class="text-center">
                    <span class="block text-lg sm:text-xl font-black text-zinc-900">١٢٠٠٠+</span>
                    <span class="text-xs text-zinc-400">إعلان نشط</span>
                </div>
                <div class="w-px h-8 bg-zinc-200 hidden sm:block"></div>
                <div class="text-center">
                    <span class="block text-lg sm:text-xl font-black text-zinc-900">٨٠٠٠+</span>
                    <span class="text-xs text-zinc-400">مستخدم</span>
                </div>
                <div class="w-px h-8 bg-zinc-200 hidden sm:block"></div>
                <div class="text-center">
                    <span class="block text-lg sm:text-xl font-black text-zinc-900">٢٧</span>
                    <span class="text-xs text-zinc-400">محافظة</span>
                </div>
            </div>

            <a href="mailto:ads@nilex.com"
               class="inline-flex items-center gap-2 mt-10 bg-[#1D9E75] hover:bg-[#178a64] text-white font-bold px-6 py-3 rounded-xl text-sm transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                تواصل معنا للإعلان
            </a>
        </div>
    </section>


    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-14 sm:space-y-20">

        {{-- ════════════════════════════════════════════
             SECTION 2 — AD SPACES
        ════════════════════════════════════════════ --}}
        <section>
            <div class="text-center mb-8 sm:mb-10">
                <h2 class="text-xl sm:text-2xl font-bold text-zinc-900">مساحات الإعلان المتاحة</h2>
                <p class="text-zinc-500 text-sm mt-2">اختر المكان المناسب لرسالتك التسويقية</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">

                {{-- Card 1: hero_top --}}
                <div class="bg-white rounded-2xl border border-zinc-200 p-6 hover:border-[#1D9E75]/30 hover:shadow-md transition-all duration-200 relative overflow-hidden">
                    <span class="absolute top-4 end-4 text-[10px] font-bold px-2.5 py-1 rounded-full bg-[#1D9E75]/10 text-[#1D9E75]">
                        الأعلى تأثيراً
                    </span>
                    <div class="text-3xl mb-4">🖼️</div>
                    <h3 class="font-bold text-zinc-900 text-lg mb-2">البانر الرئيسي</h3>
                    <p class="text-zinc-500 text-sm leading-relaxed mb-4">
                        أعلى الصفحة الرئيسية مباشرةً، أعلى معدل مشاهدة
                    </p>
                    <p class="text-xs text-zinc-400 font-medium bg-zinc-50 rounded-lg px-3 py-2 border border-zinc-100">
                        1200×400px | JPG, PNG, GIF | Max 2MB
                    </p>
                </div>

                {{-- Card 2: home_feed --}}
                <div class="bg-white rounded-2xl border border-zinc-200 p-6 hover:border-[#1D9E75]/30 hover:shadow-md transition-all duration-200 relative overflow-hidden">
                    <span class="absolute top-4 end-4 text-[10px] font-bold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">
                        الأكثر انتشاراً
                    </span>
                    <div class="text-3xl mb-4">📋</div>
                    <h3 class="font-bold text-zinc-900 text-lg mb-2">داخل القائمة</h3>
                    <p class="text-zinc-500 text-sm leading-relaxed mb-4">
                        يظهر كل 8 إعلانات في الصفحة الرئيسية
                    </p>
                    <p class="text-xs text-zinc-400 font-medium bg-zinc-50 rounded-lg px-3 py-2 border border-zinc-100">
                        768×256px | JPG, PNG | Max 2MB
                    </p>
                </div>

                {{-- Card 3: category_page --}}
                <div class="bg-white rounded-2xl border border-zinc-200 p-6 hover:border-[#1D9E75]/30 hover:shadow-md transition-all duration-200 relative overflow-hidden">
                    <span class="absolute top-4 end-4 text-[10px] font-bold px-2.5 py-1 rounded-full bg-sky-50 text-sky-700">
                        استهداف دقيق
                    </span>
                    <div class="text-3xl mb-4">🏷️</div>
                    <h3 class="font-bold text-zinc-900 text-lg mb-2">صفحة القسم</h3>
                    <p class="text-zinc-500 text-sm leading-relaxed mb-4">
                        استهداف دقيق لجمهور قسم معين
                    </p>
                    <p class="text-xs text-zinc-400 font-medium bg-zinc-50 rounded-lg px-3 py-2 border border-zinc-100">
                        768×256px | JPG, PNG | Max 2MB
                    </p>
                </div>

            </div>
        </section>


        {{-- ════════════════════════════════════════════
             SECTION 3 — PACKAGES
        ════════════════════════════════════════════ --}}
        <section>
            <div class="text-center mb-8 sm:mb-10">
                <h2 class="text-xl sm:text-2xl font-bold text-zinc-900">باقات الإعلان</h2>
                <p class="text-zinc-500 text-sm mt-2">اختر النموذج الذي يناسب أهدافك</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6 max-w-4xl mx-auto">

                {{-- Exclusive --}}
                <div class="bg-white rounded-2xl border-2 border-[#1D9E75]/20 p-6 sm:p-8 relative">
                    <div class="absolute -top-3 start-6 bg-[#1D9E75] text-white text-[10px] font-bold px-3 py-1 rounded-full">
                        موصى به
                    </div>
                    <h3 class="font-black text-zinc-900 text-xl mb-2">إعلان حصري</h3>
                    <p class="text-zinc-500 text-sm mb-6 leading-relaxed">
                        إعلانك فقط في المكان المختار بدون منافسة
                    </p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2 text-sm text-zinc-700">
                            <svg class="w-4 h-4 text-[#1D9E75] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            أعلى أولوية
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-700">
                            <svg class="w-4 h-4 text-[#1D9E75] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            ظهور ثابت
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-700">
                            <svg class="w-4 h-4 text-[#1D9E75] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            تقارير مفصلة
                        </li>
                    </ul>
                    <a href="mailto:ads@nilex.com?subject=إعلان%20حصري"
                       class="block text-center bg-[#1D9E75] hover:bg-[#178a64] text-white font-bold py-3 rounded-xl text-sm transition-colors">
                        تواصل للسعر
                    </a>
                </div>

                {{-- Rotation --}}
                <div class="bg-white rounded-2xl border border-zinc-200 p-6 sm:p-8">
                    <h3 class="font-black text-zinc-900 text-xl mb-2">إعلان بالتناوب</h3>
                    <p class="text-zinc-500 text-sm mb-6 leading-relaxed">
                        تناوب عادل مع إعلانات أخرى بنفس الأولوية
                    </p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2 text-sm text-zinc-700">
                            <svg class="w-4 h-4 text-[#1D9E75] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            سعر أقل
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-700">
                            <svg class="w-4 h-4 text-[#1D9E75] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            تناوب عشوائي عادل
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-700">
                            <svg class="w-4 h-4 text-[#1D9E75] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            تقارير أساسية
                        </li>
                    </ul>
                    <a href="mailto:ads@nilex.com?subject=إعلان%20بالتناوب"
                       class="block text-center border border-zinc-200 hover:border-[#1D9E75] text-zinc-700 hover:text-[#1D9E75] font-bold py-3 rounded-xl text-sm transition-colors">
                        تواصل للسعر
                    </a>
                </div>

            </div>
        </section>


        {{-- ════════════════════════════════════════════
             SECTION 4 — HOW IT WORKS
        ════════════════════════════════════════════ --}}
        <section class="bg-zinc-50 rounded-2xl border border-zinc-100 p-6 sm:p-10">
            <div class="text-center mb-8">
                <h2 class="text-xl sm:text-2xl font-bold text-zinc-900">كيف يعمل؟</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8 max-w-3xl mx-auto">
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-[#1D9E75]/10 text-[#1D9E75] font-black text-lg flex items-center justify-center mx-auto mb-3">
                        ١
                    </div>
                    <h3 class="font-bold text-zinc-900 text-sm mb-1">تواصل معنا</h3>
                    <p class="text-zinc-400 text-xs">أرسل طلبك عبر البريد الإلكتروني</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-[#1D9E75]/10 text-[#1D9E75] font-black text-lg flex items-center justify-center mx-auto mb-3">
                        ٢
                    </div>
                    <h3 class="font-bold text-zinc-900 text-sm mb-1">ارفع صورة إعلانك</h3>
                    <p class="text-zinc-400 text-xs">نراجع التصميم ونفعّل الحملة</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-[#1D9E75]/10 text-[#1D9E75] font-black text-lg flex items-center justify-center mx-auto mb-3">
                        ٣
                    </div>
                    <h3 class="font-bold text-zinc-900 text-sm mb-1">ابدأ الظهور فوراً</h3>
                    <p class="text-zinc-400 text-xs">إعلانك يظهر للمستخدمين مباشرة</p>
                </div>
            </div>
        </section>


        {{-- ════════════════════════════════════════════
             SECTION 5 — CTA BANNER
        ════════════════════════════════════════════ --}}
        <section>
            <div class="rounded-2xl bg-gradient-to-br from-[#1D9E75] to-[#085041] px-6 sm:px-10 py-10 sm:py-12 text-center text-white">
                <h2 class="text-xl sm:text-2xl font-black mb-2">مستعد تبدأ؟</h2>
                <p class="text-white/80 text-sm mb-6 max-w-md mx-auto">
                    فريقنا جاهز يساعدك تختار المساحة المناسبة وتطلق حملتك
                </p>
                <a href="mailto:ads@nilex.com"
                   class="inline-flex items-center gap-2 bg-white text-[#1D9E75] hover:bg-zinc-50 font-bold px-6 py-3 rounded-xl text-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    راسلنا الآن
                </a>
            </div>
        </section>

    </div>
</main>

@endsection
