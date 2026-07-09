<x-app-layout>

    @push('meta')
        <title>{{ $page->getEffectiveSeoTitle() }}</title>
        <meta name="description" content="{{ $page->getEffectiveSeoDescription() }}">
        @if($page->meta_keywords)
            <meta name="keywords" content="{{ $page->meta_keywords }}">
        @endif
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ url('/pages/' . $page->slug) }}">
        <meta property="og:type"        content="article">
        <meta property="og:title"       content="{{ $page->getEffectiveSeoTitle() }}">
        <meta property="og:description" content="{{ $page->getEffectiveSeoDescription() }}">
        <meta property="og:url"         content="{{ url('/pages/' . $page->slug) }}">
        <meta property="og:site_name"   content="{{ __('ui.pages.og_site_name') }}">
        <meta property="og:locale"      content="{{ __('ui.pages.og_locale') }}">
    @endpush

    @push('head')
        <style>
            /* Prose typography for legal content */
            .prose-arabic h1,
            .prose-arabic h2,
            .prose-arabic h3  { font-weight: 700; color: #18181b; margin-top: 1.75rem; margin-bottom: 0.75rem; }
            .prose-arabic h2  { font-size: 1.3rem; border-bottom: 2px solid #e4e4e7; padding-bottom: 0.4rem; }
            .prose-arabic h3  { font-size: 1.1rem; }
            .prose-arabic p   { line-height: 2; color: #3f3f46; margin-bottom: 1rem; }
            .prose-arabic ul,
            .prose-arabic ol  { padding-right: 1.5rem; margin-bottom: 1rem; }
            .prose-arabic li  { line-height: 2; color: #3f3f46; margin-bottom: 0.25rem; }
            .prose-arabic a   { color: #14A5A8; text-decoration: underline; }
            .prose-arabic blockquote {
                border-right: 4px solid #14A5A8;
                border-left: none;
                padding: 0.75rem 1.25rem;
                background: #f0faf5;
                color: #52525b;
                margin: 1.25rem 0;
                border-radius: 0 8px 8px 0;
            }
            .prose-arabic strong { color: #18181b; font-weight: 700; }
        </style>
    @endpush

    <div class="bg-zinc-50 min-h-screen" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-zinc-500 mb-6" aria-label="{{ __('ui.pages.breadcrumb_aria') }}">
                <a href="{{ route('home') }}" class="hover:text-nilex transition-colors font-medium">{{ __('ui.footer.link_home') }}</a>
                <svg class="w-4 h-4 text-zinc-300 rotate-180 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-zinc-800 font-semibold">{{ $page->title }}</span>
            </nav>

            {{-- Title card --}}
            <div class="bg-nilex-dark rounded-2xl p-7 mb-8 text-white relative overflow-hidden">
                <div class="absolute inset-0 opacity-5" aria-hidden="true">
                    <svg width="100%" height="100%">
                        <pattern id="dp" x="0" y="0" width="24" height="24" patternUnits="userSpaceOnUse">
                            <circle cx="2" cy="2" r="1.5" fill="white"/>
                        </pattern>
                        <rect width="100%" height="100%" fill="url(#dp)"/>
                    </svg>
                </div>
                <div class="relative z-10 flex items-start gap-4">
                    <div class="bg-white/15 p-2.5 rounded-xl shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-white/60 text-xs font-semibold mb-1">{{ __('ui.pages.official_doc') }}</p>
                        <h1 class="text-xl sm:text-2xl font-black leading-tight">{{ $page->title }}</h1>
                        <p class="text-white/60 text-xs mt-2">
                            {{ __('ui.pages.last_updated') }} {{ $page->updated_at->translatedFormat('d F Y') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Content --}}
            <article class="bg-white rounded-2xl border border-zinc-100 p-7 sm:p-10"
                     style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                <div class="prose-arabic max-w-none text-base leading-8">
                    {!! $page->content !!}
                </div>
            </article>

            {{-- Other legal pages --}}
            @php
                $otherPages = \App\Models\LegalPage::active()
                    ->where('slug', '!=', $page->slug)
                    ->get(['title', 'slug']);
            @endphp

            @if($otherPages->isNotEmpty())
                <div class="mt-8">
                    <h2 class="text-sm font-bold text-zinc-500 uppercase tracking-wider mb-3">{{ __('ui.pages.other_docs') }}</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach($otherPages as $other)
                            <a href="{{ route('legal.show', $other->slug) }}"
                               class="inline-flex items-center gap-2 bg-white border border-zinc-200 hover:border-nilex/40 hover:text-nilex rounded-xl px-4 py-2.5 text-sm font-medium text-zinc-700 transition-all"
                               style="box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                                <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ $other->title }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

</x-app-layout>
