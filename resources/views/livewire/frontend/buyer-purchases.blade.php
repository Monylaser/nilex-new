{{-- resources/views/livewire/frontend/buyer-purchases.blade.php --}}
@php($isRtl = app()->getLocale() === 'ar')
<div class="bg-zinc-50 min-h-screen pb-10" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-zinc-900">{{ __('ui.sale_confirmation.page_title') }}</h1>
                <p class="text-sm text-zinc-500 mt-1">{{ __('ui.sale_confirmation.page_subtitle') }}</p>
            </div>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-2 text-sm font-bold text-nilex hover:text-nilex-dark">
                ← {{ __('ui.sale_confirmation.back_dashboard') }}
            </a>
        </div>

        @if (session('success'))
            <div class="bg-nilex/10 border border-nilex/20 text-nilex rounded-xl px-4 py-3 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm font-semibold">
                {{ session('error') }}
            </div>
        @endif

        @forelse($purchases as $sc)
            @php($hasReview = $sc->review !== null)
            <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">

                {{-- Header: listing + seller + status --}}
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h3 class="text-base font-black text-zinc-900 truncate">{{ $sc->listing?->title ?? '—' }}</h3>
                        <p class="text-sm text-zinc-500 mt-1">
                            {{ __('ui.sale_confirmation.from_seller', ['name' => $sc->seller?->name ?? '—']) }}
                        </p>
                        <span class="text-xs text-zinc-400 mt-1 block">{{ $sc->created_at->diffForHumans() }}</span>
                    </div>

                    @if($sc->status === \App\Models\SaleConfirmation::STATUS_PENDING)
                        <span class="shrink-0 bg-amber-50 text-amber-700 text-xs font-bold px-3 py-1 rounded-full">
                            {{ __('ui.sale_confirmation.pending_badge') }}
                        </span>
                    @elseif($sc->status === \App\Models\SaleConfirmation::STATUS_CONFIRMED)
                        <span class="shrink-0 bg-nilex/10 text-nilex text-xs font-bold px-3 py-1 rounded-full">
                            {{ __('ui.sale_confirmation.confirmed_badge') }}
                        </span>
                    @else
                        <span class="shrink-0 bg-zinc-100 text-zinc-500 text-xs font-bold px-3 py-1 rounded-full">
                            {{ __('ui.sale_confirmation.canceled_badge') }}
                        </span>
                    @endif
                </div>

                {{-- ── State 1: pending → confirm purchase ────────────────────────── --}}
                @if($sc->status === \App\Models\SaleConfirmation::STATUS_PENDING)
                    <div class="mt-4 pt-4 border-t border-zinc-100">
                        <p class="text-sm text-zinc-600 mb-3">{{ __('ui.sale_confirmation.confirm_help') }}</p>
                        <button type="button"
                                wire:click="confirmPurchase({{ $sc->id }})"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center gap-2 bg-nilex hover:bg-nilex-dark text-white font-bold py-2.5 px-6 rounded-xl transition-all active:scale-95 text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ __('ui.sale_confirmation.confirm_purchase') }}
                        </button>
                    </div>

                {{-- ── State 2: confirmed, not yet reviewed → rating form inline ───── --}}
                @elseif($sc->status === \App\Models\SaleConfirmation::STATUS_CONFIRMED && ! $hasReview)
                    @php($current = (int) ($ratingValues[$sc->id] ?? 0))
                    <div class="mt-4 pt-4 border-t border-zinc-100">
                        <h4 class="text-sm font-black text-zinc-800">{{ __('ui.reviews.title') }}</h4>
                        <p class="text-xs text-zinc-500 mt-1 mb-3">{{ __('ui.reviews.subtitle') }}</p>

                        {{-- Stars 1–5 --}}
                        <div class="flex items-center gap-1 mb-3">
                            @for($n = 1; $n <= 5; $n++)
                                <button type="button"
                                        wire:click="setRating({{ $sc->id }}, {{ $n }})"
                                        aria-label="{{ $n }}"
                                        class="p-0.5 transition-transform active:scale-90">
                                    <svg class="w-7 h-7 {{ $current >= $n ? 'text-amber-400' : 'text-zinc-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.96a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.96c.3.922-.755 1.688-1.54 1.118l-3.37-2.448a1 1 0 00-1.175 0l-3.37 2.448c-.784.57-1.838-.196-1.539-1.118l1.287-3.96a1 1 0 00-.364-1.118L2.98 9.387c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.96z"/>
                                    </svg>
                                </button>
                            @endfor
                        </div>

                        {{-- Optional comment --}}
                        <textarea wire:model="ratingComments.{{ $sc->id }}"
                                  rows="2"
                                  maxlength="1000"
                                  placeholder="{{ __('ui.reviews.comment_placeholder') }}"
                                  dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
                                  class="w-full rounded-xl border border-zinc-200 px-4 py-2.5 text-sm focus:border-nilex focus:ring-1 focus:ring-nilex resize-none"></textarea>

                        <div class="mt-3">
                            <button type="button"
                                    wire:click="submitReview({{ $sc->id }})"
                                    @disabled($current < 1)
                                    class="inline-flex items-center justify-center gap-2 bg-nilex hover:bg-nilex-dark text-white font-bold py-2.5 px-6 rounded-xl transition-all active:scale-95 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ __('ui.reviews.submit') }}
                            </button>
                        </div>
                    </div>

                {{-- ── State 3: confirmed + reviewed → thank-you (read-only) ───────── --}}
                @elseif($sc->status === \App\Models\SaleConfirmation::STATUS_CONFIRMED && $hasReview)
                    <div class="mt-4 pt-4 border-t border-zinc-100">
                        <div class="flex items-center gap-1 mb-1">
                            @for($n = 1; $n <= 5; $n++)
                                <svg class="w-5 h-5 {{ $sc->review->rating >= $n ? 'text-amber-400' : 'text-zinc-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.96a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.96c.3.922-.755 1.688-1.54 1.118l-3.37-2.448a1 1 0 00-1.175 0l-3.37 2.448c-.784.57-1.838-.196-1.539-1.118l1.287-3.96a1 1 0 00-.364-1.118L2.98 9.387c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.96z"/>
                                </svg>
                            @endfor
                        </div>
                        <p class="text-sm font-bold text-zinc-700">{{ __('ui.reviews.thanks_title') }}</p>
                        @if($sc->review->comment)
                            <p class="text-sm text-zinc-500 mt-1">"{{ $sc->review->comment }}"</p>
                        @endif
                    </div>
                @endif

            </div>
        @empty
            <div class="bg-white rounded-2xl border border-zinc-100 text-center py-16 px-4" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-nilex/5 rounded-2xl mb-4">
                    <svg class="w-8 h-8 text-nilex/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-base font-black text-zinc-800 mb-2">{{ __('ui.sale_confirmation.empty_title') }}</h3>
                <p class="text-zinc-500 text-sm mb-5">{{ __('ui.sale_confirmation.empty_subtitle') }}</p>
                <a href="{{ route('home') }}"
                   class="inline-flex items-center gap-2 bg-nilex hover:bg-nilex-dark text-white px-6 py-3 rounded-xl font-bold text-sm transition-all active:scale-95"
                   style="box-shadow:0 4px 14px rgba(29,158,117,0.22);">
                    {{ __('ui.sale_confirmation.browse_cta') }}
                </a>
            </div>
        @endforelse

        @if($purchases->hasPages())
            <div>{{ $purchases->links() }}</div>
        @endif

    </div>
</div>
