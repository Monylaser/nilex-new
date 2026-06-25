<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="w-1 h-6 bg-nilex rounded-full shrink-0"></div>
            <h2 class="text-lg font-black text-zinc-900">{{ __('ui.points.header') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ── Balance Card ───────────────────────── --}}
            <div class="bg-white rounded-2xl border border-zinc-100 p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                 style="box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                <div>
                    <p class="text-xs font-bold text-nilex uppercase tracking-wider mb-1">{{ __('ui.points.balance_label') }}</p>
                    <h3 class="text-zinc-900 font-black text-lg">{{ __('ui.points.balance_title') }}</h3>
                    <p class="text-zinc-500 text-sm mt-0.5">{{ __('ui.points.balance_subtitle') }}</p>
                </div>
                <div class="flex items-baseline gap-2 shrink-0">
                    <span class="font-black text-nilex leading-none" style="font-size:2.25rem;">
                        {{ number_format(Auth::user()->points) }}
                    </span>
                    <span class="text-zinc-400 font-semibold text-sm">{{ __('ui.points.unit') }}</span>
                </div>
            </div>

            {{-- ── Quick Actions ───────────────────────── --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="{{ route('pricing') }}"
                   class="flex items-center gap-4 bg-white rounded-2xl border border-zinc-100 p-4 hover:border-nilex/25 hover:shadow-nilex transition-all duration-200 group"
                   style="box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                    <div class="w-10 h-10 rounded-xl bg-nilex/8 flex items-center justify-center shrink-0 group-hover:bg-nilex/15 transition-colors duration-200">
                        <svg class="w-5 h-5 text-nilex" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-zinc-900 text-sm">{{ __('ui.points.topup_title') }}</p>
                        <p class="text-zinc-500 text-xs mt-0.5">{{ __('ui.points.topup_subtitle') }}</p>
                    </div>
                    <svg class="w-4 h-4 text-zinc-300 shrink-0 rotate-180 rtl:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <a href="{{ route('listings.create') }}"
                   class="flex items-center gap-4 bg-white rounded-2xl border border-zinc-100 p-4 hover:border-nilex/25 hover:shadow-nilex transition-all duration-200 group"
                   style="box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                    <div class="w-10 h-10 rounded-xl bg-nilex/8 flex items-center justify-center shrink-0 group-hover:bg-nilex/15 transition-colors duration-200">
                        <svg class="w-5 h-5 text-nilex" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-zinc-900 text-sm">{{ __('ui.points.add_listing_title') }}</p>
                        <p class="text-zinc-500 text-xs mt-0.5">{{ __('ui.points.add_listing_subtitle') }}</p>
                    </div>
                    <svg class="w-4 h-4 text-zinc-300 shrink-0 rotate-180 rtl:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            {{-- ── Transactions Table ───────────────────── --}}
            <div class="bg-white rounded-2xl border border-zinc-100 overflow-hidden"
                 style="box-shadow:0 1px 4px rgba(0,0,0,0.05);">

                {{-- Table header --}}
                <div class="px-5 py-4 border-b border-zinc-50 flex items-center justify-between">
                    <h3 class="font-black text-zinc-900 text-sm">{{ __('ui.points.transactions_title') }}</h3>
                    <span class="text-xs text-zinc-400">{{ $transactions->total() }} {{ __('ui.points.transactions_count') }}</span>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-zinc-50 bg-zinc-50/60">
                                <th class="px-5 py-3 text-start text-xs font-bold text-zinc-400 uppercase tracking-wider">{{ __('ui.points.col_date') }}</th>
                                <th class="px-5 py-3 text-start text-xs font-bold text-zinc-400 uppercase tracking-wider">{{ __('ui.points.col_description') }}</th>
                                <th class="px-5 py-3 text-start text-xs font-bold text-zinc-400 uppercase tracking-wider">{{ __('ui.points.col_points') }}</th>
                                <th class="px-5 py-3 text-start text-xs font-bold text-zinc-400 uppercase tracking-wider">{{ __('ui.points.col_status') }}</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-50">
                            @forelse($transactions as $transaction)
                                <tr class="hover:bg-zinc-50/50 transition-colors duration-150">

                                    {{-- Date --}}
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-zinc-400">
                                        {{ $transaction->created_at->translatedFormat('d M Y') }}
                                    </td>

                                    {{-- Description --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0
                                                        {{ $transaction->amount > 0 ? 'bg-nilex/8' : 'bg-red-50' }}">
                                                @if($transaction->amount > 0)
                                                    <svg class="w-4 h-4 text-nilex" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <p class="font-semibold text-zinc-800 text-sm">{{ $transaction->description }}</p>
                                        </div>
                                    </td>

                                    {{-- Amount --}}
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black tabular-nums
                                                     {{ $transaction->amount > 0
                                                          ? 'bg-nilex/10 text-nilex-dark'
                                                          : 'bg-red-50 text-red-600' }}">
                                            {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount) }}
                                            <span class="font-semibold opacity-70">{{ __('ui.points.unit') }}</span>
                                        </span>
                                    </td>

                                    {{-- Status --}}
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-nilex/8 text-nilex-dark border border-nilex/15">
                                            {{ __('ui.points.status_completed') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-12 h-12 rounded-2xl bg-zinc-100 flex items-center justify-center mb-3">
                                                <svg class="w-6 h-6 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                            </div>
                                            <p class="font-bold text-zinc-800 text-sm mb-1">{{ __('ui.points.empty_title') }}</p>
                                            <p class="text-xs text-zinc-400 max-w-xs mb-4">
                                                {{ __('ui.points.empty_subtitle') }}
                                            </p>
                                            <a href="{{ route('pricing') }}"
                                               class="inline-flex items-center gap-1.5 text-xs font-bold text-nilex hover:text-nilex-dark transition-colors duration-200">
                                                {{ __('ui.points.empty_cta') }}
                                                <svg class="w-3.5 h-3.5 rotate-180 rtl:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($transactions->hasPages())
                    <div class="px-5 py-4 border-t border-zinc-50 bg-zinc-50/40">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>

            {{-- ── Earn Points Guide ───────────────────── --}}
            <div class="bg-white rounded-2xl border border-zinc-100 p-6"
                 style="box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                <h3 class="font-black text-zinc-900 text-sm mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-nilex" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('ui.points.earn_title') }}
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach([
                        ['+100', __('ui.points.earn_register')],
                        ['+50',  __('ui.points.earn_verify')],
                        ['+3',   __('ui.points.earn_listing')],
                        ['+25',  __('ui.points.earn_rating')],
                    ] as [$pts, $action])
                        <div class="flex items-center gap-3 bg-zinc-50 rounded-xl px-4 py-3">
                            <span class="font-black text-nilex text-sm shrink-0">{{ $pts }}</span>
                            <span class="text-zinc-600 text-xs font-semibold">{{ $action }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
