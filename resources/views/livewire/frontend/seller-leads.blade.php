{{-- resources/views/livewire/frontend/seller-leads.blade.php --}}
<div class="bg-zinc-50 min-h-screen pb-10" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-zinc-900">{{ __('ui.leads.title') }}</h1>
                <p class="text-sm text-zinc-500 mt-1">{{ __('ui.leads.subtitle') }}</p>
            </div>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-2 text-sm font-bold text-nilex hover:text-nilex-dark">
                ← {{ __('ui.leads.back_dashboard') }}
            </a>
        </div>

        {{-- Period filters --}}
        <div class="flex flex-wrap gap-2">
            @foreach(['today' => __('ui.leads.filter_today'), '7days' => __('ui.leads.filter_7days'), '30days' => __('ui.leads.filter_30days'), 'all' => __('ui.leads.filter_all')] as $key => $label)
                <button type="button"
                        wire:click="setPeriod('{{ $key }}')"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $period === $key ? 'bg-nilex text-white' : 'bg-white border border-zinc-200 text-zinc-600 hover:border-nilex/40' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if (session('success'))
            <div class="bg-nilex/10 border border-nilex/20 text-nilex rounded-xl px-4 py-3 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-zinc-100 overflow-hidden" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
            @if ($leads->isEmpty())
                <div class="p-10 text-center">
                    <p class="text-zinc-500 font-semibold">{{ __('ui.leads.empty') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 border-b border-zinc-100">
                            <tr>
                                <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.leads.col_type') }}</th>
                                <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.leads.col_listing') }}</th>
                                <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.leads.col_buyer') }}</th>
                                <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.leads.col_source') }}</th>
                                <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.leads.col_status') }}</th>
                                <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.leads.col_time') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($leads as $lead)
                                <tr class="hover:bg-zinc-50/80">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                            @if($lead->source_type === 'offer') bg-amber-50 text-amber-700
                                            @elseif($lead->source_type === 'phone_reveal') bg-blue-50 text-blue-700
                                            @else bg-green-50 text-green-700 @endif">
                                            {{ __('ui.leads.source_' . $lead->source_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-zinc-800 max-w-[200px] truncate">
                                        {{ $lead->listing?->title ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600">
                                        {{ $lead->buyer?->name ?? __('ui.leads.anonymous') }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500">
                                        {{ __('ui.leads.source_' . $lead->source_type) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-zinc-100 text-zinc-700">
                                            {{ __('ui.leads.status_' . $lead->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500 whitespace-nowrap">
                                        {{ $lead->created_at->diffForHumans() }}
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('dashboard.leads.show', $lead) }}"
                                           class="text-nilex font-bold hover:underline text-xs">
                                            {{ __('ui.leads.view') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-zinc-100">
                    {{ $leads->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
