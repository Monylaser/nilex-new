{{-- ════════════════════════════════════════════════════════════════
     Nilex Global Footer — Premium 5-Column Layout
     bg: #0f172a | accent: #1D9E75 | RTL Arabic
════════════════════════════════════════════════════════════════ --}}
@php
    $footerPlans    = \App\Models\PointPlan::active()->orderBy('price')->get();
    $footerLegal    = \App\Models\LegalPage::where('is_active', true)->orderBy('title')->get();
    $footerCats     = \App\Models\Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->take(5)->get();
@endphp

<footer dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" style="background:#0f172a; color:#94a3b8;" class="mt-16">

    {{-- Top accent line --}}
    <div style="height:3px; background:linear-gradient(90deg,#085041,#1D9E75,#9FE1CB,#1D9E75,#085041);"></div>

    <div class="max-w-7xl mx-auto px-6 py-16">

        {{-- ── 5-Column Grid ── --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-10 mb-14">

            {{-- Col 1 — Logo + Bio --}}
            <div class="col-span-2 md:col-span-3 lg:col-span-1">
                <a href="{{ route('home') }}" class="inline-block mb-5">
                    <img src="{{ asset('images/logo/download.png') }}"
                         class="h-9 w-auto opacity-85 hover:opacity-100 transition-opacity duration-300"
                         alt="نايلكس">
                </a>
                <p class="text-sm leading-loose mb-6" style="color:#64748b;">
                    {!! nl2br(e(__('ui.footer.bio'))) !!}
                </p>
                {{-- Social icons --}}
                <div class="flex gap-3">
                    <a href="#" aria-label="فيسبوك"
                       class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors duration-200 hover:bg-[#1D9E75]"
                       style="background:rgba(255,255,255,0.06);">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="#" aria-label="تويتر"
                       class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors duration-200 hover:bg-sky-500"
                       style="background:rgba(255,255,255,0.06);">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"/>
                        </svg>
                    </a>
                    <a href="#" aria-label="انستجرام"
                       class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors duration-200 hover:bg-pink-600"
                       style="background:rgba(255,255,255,0.06);">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                    </a>
                    <a href="#" aria-label="يوتيوب"
                       class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors duration-200 hover:bg-red-600"
                       style="background:rgba(255,255,255,0.06);">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Col 2 — Quick Links --}}
            <div>
                <h4 class="text-white font-bold text-sm mb-5 flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block" style="background:#1D9E75;"></span>
                    {{ __('ui.footer.quick_links') }}
                </h4>
                <ul class="space-y-3 text-sm">
                    <li>
                        <a href="{{ route('home') }}"
                           class="hover:text-[#1D9E75] transition-colors duration-200 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            {{ __('ui.footer.link_home') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('listings.create') }}"
                           class="hover:text-[#1D9E75] transition-colors duration-200 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            {{ __('ui.footer.link_add_listing') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('listings.search') }}"
                           class="hover:text-[#1D9E75] transition-colors duration-200 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            {{ __('ui.footer.link_browse') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('pricing') }}"
                           class="hover:text-[#1D9E75] transition-colors duration-200 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            {{ __('ui.footer.link_pricing') }}
                        </a>
                    </li>
                    @auth
                    <li>
                        <a href="{{ route('dashboard') }}"
                           class="hover:text-[#1D9E75] transition-colors duration-200 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            {{ __('ui.footer.link_dashboard') }}
                        </a>
                    </li>
                    @else
                    <li>
                        <a href="/login"
                           class="hover:text-[#1D9E75] transition-colors duration-200 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            {{ __('ui.footer.link_login') }}
                        </a>
                    </li>
                    <li>
                        <a href="/register"
                           class="hover:text-[#1D9E75] transition-colors duration-200 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            {{ __('ui.footer.link_register') }}
                        </a>
                    </li>
                    @endauth
                </ul>
            </div>

            {{-- Col 3 — Legal Pages --}}
            <div>
                <h4 class="text-white font-bold text-sm mb-5 flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block" style="background:#1D9E75;"></span>
                    {{ __('ui.footer.legal_pages') }}
                </h4>
                @if($footerLegal->isNotEmpty())
                    <ul class="space-y-3 text-sm">
                        @foreach($footerLegal as $lp)
                            <li>
                                <a href="{{ route('legal.show', $lp->slug) }}"
                                   class="hover:text-[#1D9E75] transition-colors duration-200 flex items-center gap-2 line-clamp-1">
                                    <svg class="w-3.5 h-3.5 opacity-50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    {{ $lp->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm" style="color:#475569;">{{ __('ui.footer.no_legal_pages') }}</p>
                @endif
            </div>

            {{-- Col 4 — Pricing Plans --}}
            <div>
                <h4 class="text-white font-bold text-sm mb-5 flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block" style="background:#085041;"></span>
                    <a href="{{ route('pricing') }}" class="hover:text-[#1D9E75] transition-colors duration-200">
                        {{ __('ui.footer.point_plans') }}
                    </a>
                </h4>
             
            </div>

            {{-- Col 5 — Contact --}}
            <div>
                <h4 class="text-white font-bold text-sm mb-5 flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block" style="background:#9FE1CB;"></span>
                    {{ __('ui.footer.contact') }}
                </h4>
                <ul class="space-y-4 text-sm">
                    <li class="flex items-start gap-3">
                        <svg class="w-4 h-4 mt-0.5 shrink-0" style="color:#1D9E75;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:info@nilex.com" class="hover:text-[#1D9E75] transition-colors duration-200">info@nilex.com</a>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-4 h-4 mt-0.5 shrink-0" style="color:#1D9E75;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span></span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-4 h-4 mt-0.5 shrink-0" style="color:#1D9E75;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ __('ui.footer.address') }}</span>
                    </li>
                </ul>

                {{-- Newsletter teaser --}}
                <div class="mt-6 p-4 rounded-2xl" style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07);">
                    <p class="text-xs font-bold text-white mb-2">{{ __('ui.footer.gift_teaser') }}</p>
                    <a href="/register"
                       class="block text-center text-xs font-bold py-2 px-4 rounded-xl transition-all duration-200"
                       style="background:linear-gradient(135deg,#1D9E75,#085041); color:white; box-shadow:0 4px 14px rgba(29,158,117,.3);">
                        {{ __('ui.footer.register_free') }}
                    </a>
                </div>
            </div>

        </div>

        {{-- ── Divider ── --}}
        <div style="border-top:1px solid rgba(255,255,255,0.07);"></div>

        {{-- ── Bottom bar ── --}}
        <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs" style="color:#475569;">
            <p>© {{ date('Y') }} <span class="text-white font-bold">Nilex نايلكس</span> — {{ __('ui.footer.copyright') }}</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('pricing') }}" class="hover:text-[#1D9E75] transition-colors">{{ __('ui.footer.prices_link') }}</a>
                @foreach($footerLegal->take(3) as $lp)
                    <a href="{{ route('legal.show', $lp->slug) }}" class="hover:text-[#1D9E75] transition-colors">{{ $lp->title }}</a>
                @endforeach
            </div>
        </div>

    </div>
</footer>
