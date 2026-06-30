{{-- resources/views/livewire/frontend/seller-lead-detail.blade.php --}}
<div class="bg-zinc-50 min-h-screen pb-10" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard.leads') }}"
               class="text-sm font-bold text-nilex hover:text-nilex-dark">
                ← {{ __('ui.leads.back_list') }}
            </a>
        </div>

        @if (session('success'))
            <div class="bg-nilex/10 border border-nilex/20 text-nilex rounded-xl px-4 py-3 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-zinc-100 p-6 space-y-6" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold mb-2
                        @if($lead->source_type === 'offer') bg-amber-50 text-amber-700
                        @elseif($lead->source_type === 'phone_reveal') bg-blue-50 text-blue-700
                        @else bg-green-50 text-green-700 @endif">
                        {{ __('ui.leads.source_' . $lead->source_type) }}
                    </span>
                    <h1 class="text-xl font-black text-zinc-900">{{ __('ui.leads.detail_title') }}</h1>
                    <p class="text-sm text-zinc-500 mt-1">{{ $lead->created_at->format('Y-m-d H:i') }}</p>
                </div>
            </div>

            {{-- Listing --}}
            <section class="border-t border-zinc-100 pt-5">
                <h2 class="text-sm font-black text-zinc-500 uppercase tracking-wide mb-3">{{ __('ui.leads.section_listing') }}</h2>
                <div class="bg-zinc-50 rounded-xl p-4">
                    <p class="font-bold text-zinc-900">{{ $lead->listing?->title ?? '—' }}</p>
                    @if($lead->listing)
                        <a href="{{ route('listings.show', $lead->listing) }}"
                           target="_blank"
                           class="text-sm text-nilex font-semibold mt-1 inline-block hover:underline">
                            {{ __('ui.leads.view_listing') }}
                        </a>
                    @endif
                </div>
            </section>

            {{-- Buyer --}}
            <section class="border-t border-zinc-100 pt-5">
                <h2 class="text-sm font-black text-zinc-500 uppercase tracking-wide mb-3">{{ __('ui.leads.section_buyer') }}</h2>
                <p class="font-semibold text-zinc-800">{{ $lead->buyer?->name ?? __('ui.leads.anonymous') }}</p>
            </section>

            {{-- Source --}}
            <section class="border-t border-zinc-100 pt-5">
                <h2 class="text-sm font-black text-zinc-500 uppercase tracking-wide mb-3">{{ __('ui.leads.section_source') }}</h2>
                <p class="font-bold text-zinc-900">{{ $sourceDetails['label'] }}</p>
                @if(!empty($sourceDetails['description']))
                    <p class="text-sm text-zinc-500 mt-1">{{ $sourceDetails['description'] }}</p>
                @endif
                @if(isset($sourceDetails['amount']))
                    <p class="text-sm font-semibold text-zinc-700 mt-2">
                        {{ __('ui.leads.offer_amount') }}: {{ number_format((float) $sourceDetails['amount'], 2) }} {{ __('ui.sections.currency') }}
                    </p>
                @endif
                @if(!empty($sourceDetails['message']))
                    <p class="text-sm text-zinc-600 mt-2 bg-zinc-50 rounded-lg p-3">{{ $sourceDetails['message'] }}</p>
                @endif
                @if(!empty($sourceDetails['offer_status']))
                    <p class="text-xs text-zinc-400 mt-2">{{ __('ui.leads.offer_status') }}: {{ $sourceDetails['offer_status'] }}</p>
                @endif
            </section>

            {{-- Status update --}}
            <section class="border-t border-zinc-100 pt-5">
                <h2 class="text-sm font-black text-zinc-500 uppercase tracking-wide mb-3">{{ __('ui.leads.section_status') }}</h2>
                <form wire:submit="updateStatus" class="flex flex-wrap items-end gap-3">
                    <div>
                        <select wire:model="status"
                                class="rounded-xl border-zinc-200 text-sm font-semibold focus:border-nilex focus:ring-nilex">
                            @foreach(\App\Models\SellerLead::statuses() as $statusOption)
                                <option value="{{ $statusOption }}">{{ __('ui.leads.status_' . $statusOption) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="btn-nilex-primary py-2.5 px-5 rounded-xl text-sm">
                        {{ __('ui.leads.save_status') }}
                    </button>
                </form>
            </section>

            {{-- Timeline --}}
            <section class="border-t border-zinc-100 pt-5">
                <h2 class="text-sm font-black text-zinc-500 uppercase tracking-wide mb-4">{{ __('ui.leads.section_timeline') }}</h2>
                <ol class="relative border-s border-zinc-200 ms-3 space-y-4">
                    @forelse ($lead->activities as $activity)
                        <li class="ms-6">
                            <span class="absolute -start-1.5 mt-1.5 w-3 h-3 rounded-full bg-nilex border-2 border-white"></span>
                            <p class="text-sm font-bold text-zinc-800">
                                @if($activity->type === 'created')
                                    {{ __('ui.leads.timeline_created') }}
                                @elseif($activity->type === 'status_changed')
                                    {{ __('ui.leads.timeline_status_changed', [
                                        'from' => __('ui.leads.status_' . ($activity->metadata['from'] ?? '')),
                                        'to'   => __('ui.leads.status_' . ($activity->metadata['to'] ?? '')),
                                    ]) }}
                                @else
                                    {{ $activity->type }}
                                @endif
                            </p>
                            <p class="text-xs text-zinc-400 mt-0.5">{{ $activity->created_at->format('Y-m-d H:i') }}</p>
                        </li>
                    @empty
                        <li class="ms-6 text-sm text-zinc-500">{{ __('ui.leads.timeline_empty') }}</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>
</div>
