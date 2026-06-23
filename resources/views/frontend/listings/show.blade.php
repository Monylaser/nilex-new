{{-- resources/views/frontend/listings/show.blade.php --}}

@extends('layouts.frontend')

@section('title', $listing->title . ' — ' . number_format($listing->price) . ' ج.م | نايلكس')

@push('meta')
    <meta name="description" content="{{ Str::limit(strip_tags($listing->description), 160) }}">
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $listing->title }} — {{ number_format($listing->price) }} ج.م">
    <meta property="og:description" content="{{ Str::limit(strip_tags($listing->description), 100) }}">
    <meta property="og:image" content="{{ $listing->getFirstMediaUrl('images', 'full_hd') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $listing->title }}">
    <meta name="twitter:image" content="{{ $listing->getFirstMediaUrl('images', 'full_hd') }}">
@endpush

@push('styles')
    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 4px; }
    </style>
@endpush

@section('content')

@php
    $media     = $listing->getMedia('images');
    $fullUrls  = $media->map(fn($m) => $m->getUrl('full_hd'))->values()->toJson();
    // Human-readable labels for the hardcoded car/real-estate custom fields
    // (their categories have no custom_fields_schema, so we map them here so that
    // both admin- and user-created listings render readable Arabic instead of raw keys).
    $cfLabels = [
        'year'           => 'سنة الصنع',
        'transmission'   => 'ناقل الحركة',
        'fuel'           => 'نوع الوقود',
        'condition'      => 'حالة السيارة',
        'mileage'        => 'عداد الكيلومترات',
        'color'          => 'اللون',
        'car_brand_other'=> 'الماركة',
        'property_type'  => 'نوع العقار',
        'listing_type'   => 'نوع العرض',
        'rooms'          => 'عدد الغرف',
        'bathrooms'      => 'عدد الحمامات',
        'floor'          => 'الدور',
        'finishing'      => 'نوع التشطيب',
        'area'           => 'المساحة',
        'compound'       => 'كمباوند',
    ];
    // Coded select values → Arabic labels (must match the wizard / admin option lists).
    $cfValueMaps = [
        'transmission'  => ['automatic' => 'أوتوماتيك', 'manual' => 'مانيوال'],
        'fuel'          => ['petrol' => 'بنزين', 'diesel' => 'ديزل', 'electric' => 'كهربائي', 'hybrid' => 'هجين', 'gas' => 'غاز (CNG/LPG)'],
        'property_type' => ['apartment' => 'شقة', 'villa' => 'فيلا', 'duplex' => 'دوبليكس', 'studio' => 'استوديو', 'chalet' => 'شاليه', 'office' => 'مكتب', 'shop' => 'محل تجاري', 'warehouse' => 'مخزن', 'land' => 'أرض', 'building' => 'عمارة'],
        'listing_type'  => ['sale' => 'للبيع', 'rent' => 'للإيجار'],
        'rooms'         => ['1' => 'غرفة واحدة', '2' => 'غرفتان', '3' => '3 غرف', '4' => '4 غرف', '5' => '5 غرف', '6+' => '6 غرف أو أكثر'],
        'bathrooms'     => ['1' => 'حمام واحد', '2' => 'حمامان', '3' => '3 حمامات', '4+' => '4 أو أكثر'],
        'floor'         => ['ground' => 'أرضي', '1' => 'الأول', '2' => 'الثاني', '3' => 'الثالث', '4' => 'الرابع', '5' => 'الخامس', '6+' => 'السادس فأكثر', 'rooftop' => 'روف'],
        'finishing'     => ['super_lux' => 'سوبر لوكس', 'lux' => 'لوكس', 'semi_lux' => 'نص لوكس', 'core_shell' => 'كور وشل', 'unfinished' => 'تشطيب عادي', 'furnished' => 'مفروش'],
        'compound'      => ['yes' => 'نعم', 'no' => 'لا'],
    ];
    // Numeric fields that read better with a unit suffix.
    $cfSuffix = ['mileage' => ' كم', 'area' => ' م²'];

    $displayFields = [];
    // Hide the generic "أخرى" brand row when a manual brand name was supplied.
    $brandIsOther = $listing->carBrand && $listing->carBrand->slug === 'other'
        && !empty($listing->custom_fields_values['car_brand_other'] ?? null);
    if ($listing->carBrand && ! $brandIsOther) $displayFields[] = ['label' => 'الماركة',  'value' => $listing->carBrand->name_ar];
    if ($listing->carModel)                    $displayFields[] = ['label' => 'الموديل', 'value' => $listing->carModel->name_ar];
    if (!empty($listing->custom_fields_values)) {
        $schema   = $listing->category?->custom_fields_schema ?? [];
        $labelMap = collect($schema)->keyBy('name')->map(fn($f) => $f['label_ar'] ?? $f['name']);
        foreach ($listing->custom_fields_values as $key => $val) {
            if ($val !== null && $val !== '') {
                $label = $labelMap[$key] ?? ($cfLabels[$key] ?? $key);
                $value = $cfValueMaps[$key][$val] ?? $val;
                if (isset($cfSuffix[$key])) {
                    $value .= $cfSuffix[$key];
                }
                $displayFields[] = ['label' => $label, 'value' => $value];
            }
        }
    }
@endphp

{{-- ── Outer wrapper with shared Alpine state ────────────────────────────── --}}
<div class="bg-white min-h-screen pb-24 lg:pb-10"
     style="padding-top:64px;"
     x-data="{
         activeIdx: 0,
         images: {{ $fullUrls }},
         lightboxOpen: false,
         touchStartX: 0,
         openLightbox(i = null) { if (i !== null) this.activeIdx = i; this.lightboxOpen = true; },
         closeLightbox() { this.lightboxOpen = false; },
         nextImg() { this.activeIdx = (this.activeIdx + 1) % this.images.length; },
         prevImg() { this.activeIdx = (this.activeIdx - 1 + this.images.length) % this.images.length; },
         lightboxTouchStart(e) { this.touchStartX = e.changedTouches[0].clientX; },
         lightboxTouchEnd(e) {
             if (this.images.length < 2) return;
             const dx = e.changedTouches[0].clientX - this.touchStartX;
             if (Math.abs(dx) > 40) { dx < 0 ? this.nextImg() : this.prevImg(); }
         },
         revealed: false,
         phone: '',
         whatsappUrl: '',
         loading: false,
         offerOpen: false,
         offerAmount: '',
         offerMsg: '',
         offerSubmitting: false,
         offerFeedback: null,
         revealPhone() {
             @guest window.location.href = '{{ route('login') }}'; return; @endguest
             if (this.revealed) return;
             this.loading = true;
             fetch('{{ route('listings.reveal-phone', $listing->id) }}', {
                 method: 'POST',
                 headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
             })
             .then(r => r.status === 401 ? (window.location.href = '{{ route('login') }}', null) : r.json())
             .then(data => {
                 if (data && data.phone) { this.phone = data.phone; this.whatsappUrl = data.whatsapp_url; this.revealed = true; }
             })
             .finally(() => this.loading = false);
         },
         trackWhatsappClick() {
             fetch('{{ route('listings.whatsapp-click', $listing->id) }}', {
                 method: 'POST',
                 headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
             }).finally(() => window.open(this.whatsappUrl, '_blank'));
         },
         @if(Route::has('listings.offer'))
         submitOffer() {
             @guest window.location.href = '{{ route('login') }}'; return; @endguest
             this.offerSubmitting = true;
             this.offerFeedback = null;
             fetch('{{ route('listings.offer', $listing->id) }}', {
                 method: 'POST',
                 headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                 body: JSON.stringify({ amount: this.offerAmount, message: this.offerMsg })
             })
             .then(async r => { const d = await r.json(); if (!r.ok) throw new Error(d.error || 'حدث خطأ'); return d; })
             .then(d => { this.offerFeedback = { type: 'success', text: d.success }; setTimeout(() => { this.offerOpen = false; this.offerFeedback = null; this.offerAmount = ''; this.offerMsg = ''; }, 2500); })
             .catch(e => { this.offerFeedback = { type: 'error', text: e.message }; })
             .finally(() => this.offerSubmitting = false);
         },
         @endif
     }">

    {{-- ── BREADCRUMB ─────────────────────────────────────────────────────── --}}
    <div class="border-b border-zinc-100 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-1.5 py-3 text-sm text-zinc-400 font-medium flex-wrap" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                <a href="{{ route('home') }}" class="hover:text-[#1D9E75] transition-colors">الرئيسية</a>
                @if($listing->category)
                    <svg class="w-3 h-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <a href="{{ route('category.show', $listing->category->slug) }}" class="hover:text-[#1D9E75] transition-colors">{{ $listing->category->name }}</a>
                @endif
                <svg class="w-3 h-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-zinc-600 truncate max-w-[200px] sm:max-w-xs">{{ Str::limit($listing->title, 45) }}</span>
            </nav>
        </div>
    </div>

    {{-- ── MAIN CONTENT ────────────────────────────────────────────────────── --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ══ LEFT COLUMN: media + description ════════════════════════════ --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Image gallery -------------------------------------------------- --}}
                <div class="bg-white rounded-xl border border-zinc-200 overflow-hidden">
                    {{-- Main image --}}
                    <div class="relative bg-zinc-100 h-72 sm:h-[380px]">
                        @if($media->isNotEmpty())
                            <img :src="images[activeIdx] || '{{ $media->first()->getUrl('full_hd') }}'"
                                 alt="{{ $listing->title }}"
                                 @click="openLightbox()"
                                 class="w-full h-full object-cover cursor-zoom-in"
                                 loading="eager">

                            {{-- Counter badge --}}
                            @if($media->count() > 1)
                                <div class="absolute top-3 end-3 bg-zinc-900/60 text-white text-xs font-bold px-2.5 py-1 rounded-full">
                                    <span x-text="activeIdx + 1"></span>/{{ $media->count() }}
                                </div>
                            @endif

                            {{-- Nav arrows --}}
                            @if($media->count() > 1)
                                <button @click="activeIdx = (activeIdx - 1 + images.length) % images.length"
                                        class="absolute top-1/2 end-3 -translate-y-1/2 w-9 h-9 bg-white border border-zinc-200 hover:bg-zinc-50 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                                <button @click="activeIdx = (activeIdx + 1) % images.length"
                                        class="absolute top-1/2 start-3 -translate-y-1/2 w-9 h-9 bg-white border border-zinc-200 hover:bg-zinc-50 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                            @endif
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center gap-3 text-zinc-300">
                                <svg class="w-16 h-16 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-sm font-medium text-zinc-400">لا توجد صور</span>
                            </div>
                        @endif
                    </div>

                    {{-- Thumbnails --}}
                    @if($media->count() > 1)
                        <div class="flex gap-2 p-3 overflow-x-auto custom-scrollbar bg-zinc-50 border-t border-zinc-100">
                            @foreach($media as $i => $img)
                                <button @click="openLightbox({{ $i }})"
                                        :class="activeIdx === {{ $i }}
                                            ? 'border-[#1D9E75] ring-2 ring-[#1D9E75]/20 opacity-100'
                                            : 'border-transparent opacity-50 hover:opacity-80'"
                                        class="w-16 h-16 shrink-0 rounded-xl overflow-hidden border-2 transition-opacity">
                                    <img src="{{ $img->getUrl('thumb') }}" alt="" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Title + meta strip -------------------------------------------- --}}
                <div class="bg-white rounded-xl border border-zinc-200 p-5">
                    @if($listing->is_featured)
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-[#1D9E75] bg-[#1D9E75]/8 px-3 py-1 rounded-full mb-3 border border-[#1D9E75]/15">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            إعلان مميز
                        </span>
                    @endif
                    <h1 class="text-2xl sm:text-3xl font-black text-zinc-900 leading-snug">{{ $listing->title }}</h1>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-3 text-sm text-zinc-500">
                        @if($listing->location)
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $listing->location->name_ar }}@if($listing->province), {{ $listing->province->name_ar }}@endif
                            </span>
                        @endif
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $listing->created_at->diffForHumans() }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            {{ number_format($listing->views_count) }} مشاهدة
                        </span>
                    </div>
                </div>

                {{-- Custom fields -------------------------------------------------- --}}
                @if(count($displayFields) > 0)
                    <div class="bg-white rounded-xl border border-zinc-200 p-5">
                        <h2 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-4">تفاصيل الإعلان</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($displayFields as $field)
                                <div class="bg-zinc-50 rounded-xl px-4 py-3 border border-zinc-100">
                                    <p class="text-[11px] text-zinc-400 font-semibold mb-0.5">{{ $field['label'] }}</p>
                                    <p class="font-bold text-zinc-800 text-sm leading-snug">{{ $field['value'] }}</p>
                                </div>
                            @endforeach
                            @if($listing->condition)
                                <div class="bg-zinc-50 rounded-xl px-4 py-3 border border-zinc-100">
                                    <p class="text-[11px] text-zinc-400 font-semibold mb-0.5">الحالة</p>
                                    <p class="font-bold text-sm {{ $listing->condition === 'new' ? 'text-[#1D9E75]' : 'text-amber-700' }}">
                                        {{ $listing->condition === 'new' ? '✦ جديد' : '◉ مستعمل' }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Description ---------------------------------------------------- --}}
                <div class="bg-white rounded-xl border border-zinc-200 p-5">
                    <h2 class="text-base font-black text-zinc-900 mb-4">وصف الإعلان</h2>
                    <div class="text-zinc-600 leading-relaxed text-sm">
                        {!! $listing->description !!}
                    </div>
                </div>

                {{-- Offer button (desktop) ----------------------------------------- --}}
                @if(Route::has('listings.offer') && auth()->id() !== $listing->user_id)
                    <div class="bg-white rounded-xl border border-zinc-200 p-5">
                        <button @click="offerOpen = true"
                                class="w-full flex justify-center items-center gap-2.5 border-2 border-[#1D9E75]/30 hover:border-[#1D9E75] text-[#1D9E75] hover:bg-[#1D9E75] hover:text-white px-6 py-3.5 rounded-xl font-bold text-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                            قدّم عرض سعر للبائع
                        </button>
                    </div>
                @endif

                {{-- Seller info ---------------------------------------------------- --}}
                <div class="bg-white rounded-xl border border-zinc-200 p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-[#1D9E75]/10 rounded-full flex items-center justify-center font-black text-[#1D9E75] text-lg shrink-0">
                        {{ mb_substr($listing->user->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[11px] text-zinc-400 font-semibold mb-0.5">المعلن</p>
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-black text-zinc-900 leading-snug">{{ $listing->user->name }}</p>
                            @include('frontend.partials.business-badge', ['seller' => $listing->user])
                        </div>
                        <p class="text-xs text-zinc-400 mt-0.5">عضو منذ {{ $listing->user->created_at->format('Y/m') }}</p>
                    </div>
                </div>

            </div>

            {{-- ══ RIGHT COLUMN: price + contact sidebar (desktop only) ══════════ --}}
            <div class="hidden lg:block">
                <div class="sticky top-20 space-y-4">

                    {{-- Price card --}}
                    <div class="bg-white rounded-xl border border-zinc-200 p-5">
                        <p class="text-xs text-zinc-400 font-semibold mb-1.5">السعر المطلوب</p>
                        <div class="text-3xl font-black text-[#1D9E75]">
                            {{ number_format($listing->price) }}
                            <span class="text-base font-bold text-zinc-400">ج.م</span>
                        </div>
                        @if($listing->condition)
                            <span class="inline-block mt-2 text-xs font-bold px-2.5 py-1 rounded-full {{ $listing->condition === 'new' ? 'bg-[#1D9E75]/8 text-[#1D9E75]' : 'bg-amber-50 text-amber-700' }}">
                                {{ $listing->condition === 'new' ? 'جديد' : 'مستعمل' }}
                            </span>
                        @endif
                    </div>

                    {{-- Contact card --}}
                    <div class="bg-white rounded-xl border border-zinc-200 p-5 space-y-3">
                        {{-- Before reveal --}}
                        <div x-show="!revealed">
                            <button @click="revealPhone()" :disabled="loading"
                                    class="w-full flex items-center justify-center gap-2 bg-[#1D9E75] hover:bg-[#178a64] text-white py-3.5 rounded-xl font-bold disabled:opacity-60">
                                <span x-show="!loading">
                                    <svg class="w-4 h-4 inline me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    إظهار رقم التواصل
                                </span>
                                <span x-show="loading" style="display:none;">جاري التحميل...</span>
                            </button>
                            @guest
                                <p class="text-xs text-center text-zinc-400 mt-2">يجب تسجيل الدخول لعرض الرقم</p>
                            @endguest
                        </div>
                        {{-- After reveal --}}
                        <div x-show="revealed" style="display:none;" class="space-y-2.5">
                            <a href="#" @click.prevent="trackWhatsappClick()"
                               class="flex items-center justify-center gap-2 w-full py-3.5 rounded-xl font-bold text-white"
                               style="background:#25D366;">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z M12.043 0C5.384 0 0 5.384 0 12.043c0 2.138.566 4.257 1.645 6.105L.057 23.814a.5.5 0 00.615.621l5.794-1.512a12.003 12.003 0 005.577 1.379h.005C18.703 24.302 24.086 18.918 24.086 12.258 24.086 5.599 18.702.214 12.043 0z"/></svg>
                                تواصل واتساب
                            </a>
                            <a :href="'tel:' + phone"
                               class="flex items-center justify-center gap-2 w-full bg-zinc-900 hover:bg-zinc-800 text-white py-3.5 rounded-xl font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span x-text="phone"></span>
                            </a>
                        </div>
                    </div>

                    {{-- Meta details --}}
                    <div class="bg-white rounded-xl border border-zinc-200 p-5 text-sm space-y-0">
                        @if($listing->category)
                            <div class="flex justify-between items-center py-2.5 border-b border-zinc-50">
                                <span class="text-zinc-400 font-semibold">القسم</span>
                                <a href="{{ route('category.show', $listing->category->slug) }}" class="font-bold text-zinc-800 hover:text-[#1D9E75] transition-colors">{{ $listing->category->name }}</a>
                            </div>
                        @endif
                        @if($listing->province || $listing->location)
                            <div class="flex justify-between items-center py-2.5 border-b border-zinc-50">
                                <span class="text-zinc-400 font-semibold">الموقع</span>
                                <span class="font-bold text-zinc-800 text-end max-w-[130px]">{{ $listing->province?->name_ar ?? $listing->location?->name_ar }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-2.5 border-b border-zinc-50">
                            <span class="text-zinc-400 font-semibold">تاريخ النشر</span>
                            <span class="font-bold text-zinc-800">{{ $listing->created_at->format('Y/m/d') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-zinc-400 font-semibold">المشاهدات</span>
                            <span class="font-bold text-zinc-800">{{ number_format($listing->views_count) }}</span>
                        </div>
                    </div>

                    {{-- Seller trust card --}}
                    @if($listing->user)
                        <x-seller-trust-card :seller="$listing->user" />
                    @endif

                </div>
            </div>

        </div>
    </div>

    {{-- ── MOBILE: FIXED BOTTOM CONTACT BAR ───────────────────────────────── --}}
    <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-zinc-200 px-4 py-3">
        <div class="flex items-center gap-3">
            {{-- Price chip --}}
            <div class="shrink-0 min-w-0">
                <p class="text-lg font-black text-[#1D9E75] leading-none">{{ number_format($listing->price) }}</p>
                <p class="text-xs text-zinc-400 font-medium leading-none mt-0.5">ج.م</p>
            </div>

            {{-- Before reveal --}}
            <div x-show="!revealed" class="flex-1">
                <button @click="revealPhone()" :disabled="loading"
                        class="w-full flex items-center justify-center gap-2 bg-[#1D9E75] hover:bg-[#178a64] text-white py-3 rounded-xl font-bold text-sm disabled:opacity-60">
                    <span x-show="!loading">إظهار رقم البائع</span>
                    <span x-show="loading" style="display:none;">جاري...</span>
                </button>
            </div>

            {{-- After reveal --}}
            <div x-show="revealed" style="display:none;" class="flex flex-1 gap-2">
                <a :href="'tel:' + phone"
                   class="flex-1 flex items-center justify-center gap-1.5 bg-zinc-900 hover:bg-zinc-800 text-white py-3 rounded-xl font-bold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    اتصال
                </a>
                <a href="#" @click.prevent="trackWhatsappClick()"
                   class="flex-1 flex items-center justify-center gap-1.5 text-white py-3 rounded-xl font-bold text-sm"
                   style="background:#25D366;">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z M12.043 0C5.384 0 0 5.384 0 12.043c0 2.138.566 4.257 1.645 6.105L.057 23.814a.5.5 0 00.615.621l5.794-1.512a12.003 12.003 0 005.577 1.379h.005C18.703 24.302 24.086 18.918 24.086 12.258 24.086 5.599 18.702.214 12.043 0z"/></svg>
                    واتساب
                </a>
            </div>
        </div>
    </div>

    {{-- ── IMAGE LIGHTBOX ──────────────────────────────────────────────────── --}}
    @if($media->isNotEmpty())
        <div x-show="lightboxOpen" style="display:none;"
             class="fixed inset-0 z-[120] flex items-center justify-center bg-black/90"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @keydown.escape.window="closeLightbox()"
             @keydown.arrow-left.window="lightboxOpen && prevImg()"
             @keydown.arrow-right.window="lightboxOpen && nextImg()">

            {{-- Backdrop (click to close) --}}
            <div class="absolute inset-0" @click="closeLightbox()"></div>

            {{-- Close button --}}
            <button @click="closeLightbox()"
                    class="absolute top-4 end-4 z-10 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center backdrop-blur-sm transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            {{-- Counter --}}
            @if($media->count() > 1)
                <div class="absolute top-6 left-1/2 -translate-x-1/2 z-10 text-white/80 text-sm font-bold">
                    <span x-text="activeIdx + 1"></span> / {{ $media->count() }}
                </div>
            @endif

            {{-- Image --}}
            <img :src="images[activeIdx]" alt="{{ $listing->title }}"
                 class="relative max-w-[92vw] max-h-[86vh] object-contain select-none"
                 @click.stop
                 @touchstart="lightboxTouchStart($event)" @touchend="lightboxTouchEnd($event)">

            {{-- Nav arrows --}}
            @if($media->count() > 1)
                <button @click.stop="prevImg()"
                        class="absolute top-1/2 left-4 -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center backdrop-blur-sm transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button @click.stop="nextImg()"
                        class="absolute top-1/2 right-4 -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center backdrop-blur-sm transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            @endif
        </div>
    @endif

    {{-- ── OFFER MODAL ─────────────────────────────────────────────────────── --}}
    @if(Route::has('listings.offer') && auth()->id() !== $listing->user_id)
        <div x-show="offerOpen" style="display:none;"
             class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-zinc-900/50"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.away="offerOpen = false"
                 class="bg-white rounded-xl border border-zinc-200 w-full max-w-md p-6"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-xl font-black text-zinc-900">تقديم عرض سعر</h3>
                    <button @click="offerOpen = false" class="text-zinc-400 hover:text-zinc-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <p class="text-zinc-500 text-sm mb-5">السعر المطلوب: <strong class="text-[#1D9E75]">{{ number_format($listing->price) }} ج.م</strong></p>

                <template x-if="offerFeedback">
                    <div :class="offerFeedback.type === 'success' ? 'bg-[#1D9E75]/8 text-[#178a64] border-[#1D9E75]/20' : 'bg-red-50 text-red-700 border-red-200'"
                         class="p-3.5 rounded-xl border text-sm font-bold mb-4" x-text="offerFeedback.text"></div>
                </template>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 mb-1.5">سعرك المقترح (ج.م)</label>
                        <input type="number" x-model="offerAmount"
                               class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-3 font-bold text-lg text-zinc-900 focus:outline-none focus:border-zinc-400"
                               placeholder="اكتب سعرك هنا...">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 mb-1.5">رسالة للبائع (اختياري)</label>
                        <textarea x-model="offerMsg" rows="3"
                                  class="w-full bg-white border border-zinc-200 rounded-xl px-4 py-3 text-sm text-zinc-900 focus:outline-none focus:border-zinc-400 resize-none"
                                  placeholder="مثال: أنا جاهز للشراء اليوم..."></textarea>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button @click="submitOffer()"
                            :disabled="offerSubmitting || !offerAmount"
                            class="flex-1 bg-[#1D9E75] hover:bg-[#178a64] text-white py-3 rounded-xl font-bold disabled:opacity-40 disabled:cursor-not-allowed">
                        <span x-show="!offerSubmitting">إرسال العرض</span>
                        <span x-show="offerSubmitting" style="display:none;">جاري الإرسال...</span>
                    </button>
                    <button @click="offerOpen = false" :disabled="offerSubmitting"
                            class="flex-1 bg-zinc-100 hover:bg-zinc-200 text-zinc-700 py-3 rounded-xl font-bold">
                        إلغاء
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>

@endsection
