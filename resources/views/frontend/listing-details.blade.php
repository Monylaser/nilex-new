{{--
    resources/views/frontend/listing-details.blade.php
    ──────────────────────────────────────────────────
    NOTE: The active listing-details route renders resources/views/listings/show.blade.php
    This standalone file is kept for backward compatibility only.
    It uses the 'images' media collection (standalone preview mode).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{{ $listing->title }} | نايلكس</title>
    <meta name="description" content="{{ Str::limit(strip_tags($listing->description), 160) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-50 font-sans antialiased"
      x-data="{
          activeIdx: 0,
          images: {{ $listing->getMedia('images')->map(fn($m) => $m->getUrl('full_hd'))->values()->toJson() ?: '[]' }},
          revealed: false, phone: '', whatsappUrl: '', loading: false,
          revealPhone() {
              @guest window.location.href = '{{ route('login') }}'; return; @endguest
              if (this.revealed) return;
              this.loading = true;
              fetch('{{ route('listings.reveal-phone', $listing->id) }}', {
                  method: 'POST',
                  headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
              })
              .then(r => r.status === 401 ? (window.location.href = '{{ route('login') }}', null) : r.json())
              .then(d => { if (d && d.phone) { this.phone = d.phone; this.whatsappUrl = d.whatsapp_url; this.revealed = true; } })
              .finally(() => this.loading = false);
          }
      }">

    {{-- Navbar --}}
    <header class="sticky top-0 z-50 bg-white border-b border-zinc-100" style="box-shadow:0 1px 4px rgba(0,0,0,0.04);">
        <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="shrink-0">
                <img src="{{ asset('images/logo/download.png') }}" class="h-8 w-auto" alt="Nilex">
            </a>
            <a href="{{ route('listings.create') }}"
               class="flex items-center gap-1.5 bg-nilex hover:bg-nilex-dark text-white text-sm font-bold px-4 py-2 rounded-xl transition-all active:scale-95 shrink-0"
               style="box-shadow:0 3px 10px rgba(29,158,117,0.22);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                أضف إعلان
            </a>
        </div>
    </header>

    {{-- Breadcrumb --}}
    <div class="max-w-5xl mx-auto px-4 py-3">
        <nav class="flex items-center gap-1.5 text-sm text-zinc-400 font-medium">
            <a href="{{ route('home') }}" class="hover:text-nilex transition-colors">الرئيسية</a>
            @if($listing->category)
                <svg class="w-3 h-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('category.show', $listing->category->slug) }}" class="hover:text-nilex transition-colors">{{ $listing->category->name_ar }}</a>
            @endif
            <svg class="w-3 h-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-zinc-600 truncate max-w-[200px]">{{ Str::limit($listing->title, 40) }}</span>
        </nav>
    </div>

    <main class="max-w-5xl mx-auto px-4 pb-24 lg:pb-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Left: images + description --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Image gallery --}}
                <div class="bg-white rounded-2xl border border-zinc-100 overflow-hidden" style="box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                    <div class="relative h-72 sm:h-[360px] bg-zinc-100">
                        @php $imgs = $listing->getMedia('images'); @endphp
                        @if($imgs->isNotEmpty())
                            <img :src="images[activeIdx] || '{{ $imgs->first()->getUrl('full_hd') }}'"
                                 alt="{{ $listing->title }}"
                                 class="w-full h-full object-cover">
                            @if($imgs->count() > 1)
                                <div class="absolute top-3 end-3 bg-zinc-900/60 text-white text-xs font-bold px-2.5 py-1 rounded-full">
                                    <span x-text="activeIdx + 1"></span>/{{ $imgs->count() }}
                                </div>
                                <button @click="activeIdx = (activeIdx - 1 + images.length) % images.length"
                                        class="absolute top-1/2 end-3 -translate-y-1/2 w-8 h-8 bg-white/90 hover:bg-white rounded-full shadow flex items-center justify-center">
                                    <svg class="w-4 h-4 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                                <button @click="activeIdx = (activeIdx + 1) % images.length"
                                        class="absolute top-1/2 start-3 -translate-y-1/2 w-8 h-8 bg-white/90 hover:bg-white rounded-full shadow flex items-center justify-center">
                                    <svg class="w-4 h-4 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                            @endif
                        @else
                            <div class="w-full h-full flex items-center justify-center text-zinc-300">
                                <svg class="w-16 h-16 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif
                    </div>
                    @if($imgs->count() > 1)
                        <div class="flex gap-2 p-3 overflow-x-auto custom-scrollbar bg-zinc-50/60">
                            @foreach($imgs as $i => $img)
                                <button @click="activeIdx = {{ $i }}"
                                        :class="activeIdx === {{ $i }} ? 'border-nilex ring-2 ring-nilex/20 opacity-100' : 'border-transparent opacity-50 hover:opacity-80'"
                                        class="w-14 h-14 shrink-0 rounded-xl overflow-hidden border-2 transition-all">
                                    <img src="{{ $img->getUrl('thumb') }}" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Title + meta --}}
                <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                    <h1 class="text-2xl font-black text-zinc-900 leading-snug">{{ $listing->title }}</h1>
                    <div class="flex flex-wrap gap-3 mt-3 text-sm text-zinc-500">
                        @if($listing->location)
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $listing->location?->name_ar }}
                            </span>
                        @endif
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $listing->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                {{-- Description --}}
                <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                    <h2 class="text-base font-black text-zinc-900 mb-4">وصف الإعلان</h2>
                    <div class="text-zinc-600 leading-relaxed text-sm">{!! nl2br(e($listing->description)) !!}</div>
                </div>
            </div>

            {{-- Right: price + contact (desktop) --}}
            <div class="hidden lg:block">
                <div class="sticky top-20 space-y-4">
                    <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                        <p class="text-xs text-zinc-400 font-semibold mb-1">السعر</p>
                        <div class="text-3xl font-black text-nilex">{{ number_format($listing->price) }}<span class="text-sm font-bold text-zinc-400 ms-1">ج.م</span></div>
                        @if($listing->condition)
                            <span class="inline-block mt-2 text-xs font-bold px-2.5 py-1 rounded-full {{ $listing->condition === 'new' ? 'bg-nilex/8 text-nilex' : 'bg-amber-50 text-amber-700' }}">
                                {{ $listing->condition === 'new' ? 'جديد' : 'مستعمل' }}
                            </span>
                        @endif
                    </div>
                    <div class="bg-white rounded-2xl border border-zinc-100 p-5 space-y-3" style="box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                        <div x-show="!revealed">
                            <button @click="revealPhone()" :disabled="loading"
                                    class="w-full flex items-center justify-center gap-2 bg-nilex hover:bg-nilex-dark text-white py-3.5 rounded-xl font-bold transition-all active:scale-95 text-sm"
                                    style="box-shadow:0 4px 14px rgba(29,158,117,0.25);">
                                <span x-show="!loading">إظهار رقم التواصل</span>
                                <span x-show="loading" style="display:none;">جاري...</span>
                            </button>
                        </div>
                        <div x-show="revealed" style="display:none;" class="space-y-2.5">
                            <a :href="whatsappUrl" target="_blank" class="flex items-center justify-center gap-2 w-full py-3.5 rounded-xl font-bold text-white text-sm" style="background:#25D366;">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z M12.043 0C5.384 0 0 5.384 0 12.043c0 2.138.566 4.257 1.645 6.105L.057 23.814a.5.5 0 00.615.621l5.794-1.512a12.003 12.003 0 005.577 1.379h.005C18.703 24.302 24.086 18.918 24.086 12.258 24.086 5.599 18.702.214 12.043 0z"/></svg>
                                واتساب
                            </a>
                            <a :href="'tel:' + phone" class="flex items-center justify-center gap-2 w-full bg-zinc-900 hover:bg-zinc-800 text-white py-3.5 rounded-xl font-bold text-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span x-text="phone"></span>
                            </a>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-zinc-100 p-5 text-sm space-y-0" style="box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                        @if($listing->category)
                            <div class="flex justify-between items-center py-2.5 border-b border-zinc-50">
                                <span class="text-zinc-400 font-semibold">القسم</span>
                                <span class="font-bold text-zinc-800">{{ $listing->category->name_ar }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-2.5 border-b border-zinc-50">
                            <span class="text-zinc-400 font-semibold">الحالة</span>
                            <span class="font-bold {{ $listing->condition === 'new' ? 'text-nilex' : 'text-amber-700' }}">{{ $listing->condition === 'new' ? 'جديد' : 'مستعمل' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2.5">
                            <span class="text-zinc-400 font-semibold">تاريخ النشر</span>
                            <span class="font-bold text-zinc-800">{{ $listing->created_at->format('Y/m/d') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Mobile fixed contact bar --}}
    <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-zinc-100 px-4 py-3"
         style="box-shadow:0 -4px 20px rgba(0,0,0,0.08);">
        <div class="flex items-center gap-3">
            <div class="shrink-0">
                <p class="text-lg font-black text-nilex leading-none">{{ number_format($listing->price) }}</p>
                <p class="text-xs text-zinc-400 mt-0.5">ج.م</p>
            </div>
            <div x-show="!revealed" class="flex-1">
                <button @click="revealPhone()" :disabled="loading"
                        class="w-full flex items-center justify-center gap-2 bg-nilex hover:bg-nilex-dark text-white py-3 rounded-xl font-bold text-sm transition-all active:scale-95"
                        style="box-shadow:0 3px 12px rgba(29,158,117,0.22);">
                    <span x-show="!loading">إظهار رقم البائع</span>
                    <span x-show="loading" style="display:none;">جاري...</span>
                </button>
            </div>
            <div x-show="revealed" style="display:none;" class="flex flex-1 gap-2">
                <a :href="'tel:' + phone" class="flex-1 flex items-center justify-center gap-1.5 bg-zinc-900 text-white py-3 rounded-xl font-bold text-sm active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    اتصال
                </a>
                <a :href="whatsappUrl" target="_blank" class="flex-1 flex items-center justify-center gap-1.5 text-white py-3 rounded-xl font-bold text-sm active:scale-95" style="background:#25D366;">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z M12.043 0C5.384 0 0 5.384 0 12.043c0 2.138.566 4.257 1.645 6.105L.057 23.814a.5.5 0 00.615.621l5.794-1.512a12.003 12.003 0 005.577 1.379h.005C18.703 24.302 24.086 18.918 24.086 12.258 24.086 5.599 18.702.214 12.043 0z"/></svg>
                    واتساب
                </a>
            </div>
        </div>
    </div>

</body>
</html>
