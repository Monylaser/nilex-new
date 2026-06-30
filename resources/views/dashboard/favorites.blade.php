@php
    $isRtl = app()->getLocale() === 'ar';
@endphp
<x-app-layout>
    <div class="bg-zinc-50 min-h-screen pb-10" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-zinc-900">{{ __('ui.favorites.title') }}</h1>
                    <p class="text-sm text-zinc-500 mt-1">{{ __('ui.favorites.subtitle') }}</p>
                </div>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-2 text-sm font-bold text-nilex hover:text-nilex-dark">
                    ← {{ __('ui.favorites.back_dashboard') }}
                </a>
            </div>

            @if($listings->isEmpty())
                <div class="bg-white rounded-2xl border border-zinc-100 text-center py-16 px-4"
                     style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-2xl mb-4">
                        <svg class="w-8 h-8 text-red-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-black text-zinc-800 mb-2">{{ __('ui.favorites.empty_title') }}</h3>
                    <p class="text-zinc-500 text-sm mb-5">{{ __('ui.favorites.empty_subtitle') }}</p>
                    <a href="{{ route('home') }}"
                       class="btn-nilex-primary inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm">
                        {{ __('ui.favorites.browse_cta') }}
                    </a>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($listings as $listing)
                        @include('frontend.partials.listing-card', ['listing' => $listing, 'isFeatured' => $listing->is_featured])
                    @endforeach
                </div>

                @if($listings->hasPages())
                    <div>{{ $listings->links() }}</div>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>
