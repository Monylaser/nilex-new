<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Activity History') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 font-['Inter']">

                    {{-- عنوان الصفحة الجانبي --}}
                    <div class="mb-6">
                        <h3 class="text-lg font-bold">Activity History</h3>
                        <p class="text-sm text-gray-500">Track your points and rewards</p>
                    </div>

                    {{-- حاوية الجدول --}}
                    <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-50 bg-slate-50/50">
                                    <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Description</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Amount</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Status</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-50">
                                @forelse($transactions as $transaction)
                                    <tr class="transition-colors hover:bg-slate-50/50">
                                        {{-- التاريخ --}}
                                        <td class="whitespace-nowrap px-6 py-5 text-sm text-slate-400">
                                            {{ $transaction->created_at->format('M d, Y') }}
                                        </td>

                                        {{-- الوصف مع الأيقونة --}}
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-3">
                                                <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-slate-50 {{ $transaction->amount > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                    @if($transaction->amount > 0)
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19.5 12h-15" /></svg>
                                                    @endif
                                                </div>
                                                <p class="font-medium text-slate-900">{{ $transaction->description }}</p>
                                            </div>
                                        </td>

                                        {{-- القيمة (موجب/سالب) --}}
                                        <td class="whitespace-nowrap px-6 py-5">
                                            <span class="inline-flex items-center rounded-full {{ $transaction->amount > 0 ? 'bg-emerald-500/10 text-emerald-700' : 'bg-rose-500/10 text-rose-700' }} px-2.5 py-1 text-xs font-bold tabular-nums">
                                                {{ $transaction->amount > 0 ? '+' : '' }}{{ $transaction->amount }}
                                            </span>
                                        </td>

                                        {{-- الحالة --}}
                                        <td class="whitespace-nowrap px-6 py-5">
                                            <span class="inline-flex items-center rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                                Completed
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    {{-- حالة الجدول الفارغ --}}
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="p-4 bg-slate-50 rounded-full mb-4">
                                                    <svg class="w-12 h-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25-2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                                    </svg>
                                                </div>
                                                <h3 class="text-sm font-semibold text-slate-900">لا توجد عمليات بعد</h3>
                                                <p class="text-xs text-slate-500 mt-1 max-w-[200px] mx-auto">
                                                    ابدأ بالتفاعل مع المنصة واجمع نقاطك الأولى لتظهر هنا!
                                                </p>
                                                <a href="{{ route('dashboard') }}" class="mt-4 text-xs font-bold text-indigo-600 hover:text-indigo-500 transition-colors">
                                                    العودة للرئيسية ←
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        {{-- تذييل الجدول (أرقام الصفحات) --}}
                        @if($transactions->hasPages())
                            <div class="px-6 py-4 border-t border-slate-50 bg-slate-50/30">
                                {{ $transactions->links() }}
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
