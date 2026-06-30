@php
    $isRtl = app()->getLocale() === 'ar';
    $localeLabel = fn ($row, $key) => $isRtl
        ? ($row['label_ar'] ?? $row['label'] ?? $key)
        : ($row['label_en'] ?? $row['label'] ?? $key);
@endphp
<x-app-layout>
    <div class="bg-zinc-50 min-h-screen pb-10" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-zinc-900">{{ __('ui.ads_dashboard.index.title') }}</h1>
                    <p class="text-sm text-zinc-500 mt-1">{{ __('ui.ads_dashboard.index.subtitle') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-2 text-sm font-bold text-nilex hover:text-nilex-dark">
                        ← {{ __('ui.ads_dashboard.common.back_dashboard') }}
                    </a>
                    <a href="{{ route('dashboard.ads.create') }}"
                       class="btn-nilex-primary inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm">
                        + {{ __('ui.ads_dashboard.index.new_campaign') }}
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="bg-nilex/10 border border-nilex/20 text-nilex rounded-xl px-4 py-3 text-sm font-semibold">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-semibold">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-zinc-100 overflow-hidden" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                @if ($campaigns->isEmpty())
                    <div class="p-10 text-center">
                        <p class="text-zinc-500 font-semibold mb-4">{{ __('ui.ads_dashboard.index.empty') }}</p>
                        <a href="{{ route('dashboard.ads.create') }}"
                           class="btn-nilex-primary inline-flex items-center px-4 py-2 rounded-xl text-sm">
                            {{ __('ui.ads_dashboard.index.create_first') }}
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 border-b border-zinc-100">
                                <tr>
                                    <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.ads_dashboard.index.col_title') }}</th>
                                    <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.ads_dashboard.index.col_placement') }}</th>
                                    <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.ads_dashboard.index.col_payment') }}</th>
                                    <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.ads_dashboard.index.col_approval') }}</th>
                                    <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.ads_dashboard.index.col_amount') }}</th>
                                    <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.ads_dashboard.index.col_starts') }}</th>
                                    <th class="text-start px-4 py-3 font-bold text-zinc-500">{{ __('ui.ads_dashboard.index.col_ends') }}</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @foreach ($campaigns as $campaign)
                                    @php
                                        $placementRow = config("ad_pricing.placements.{$campaign->placement}", []);
                                        $placementLabel = $localeLabel($placementRow, $campaign->placement);
                                        $amount = $campaign->amount_paid;
                                        if ($amount === null && $campaign->duration_days) {
                                            try {
                                                $amount = app(\App\Services\AdCampaignPaymentService::class)
                                                    ->calculatePrice($campaign->placement, (int) $campaign->duration_days);
                                            } catch (\RuntimeException) {
                                                $amount = null;
                                            }
                                        }
                                        $paymentLabels = [
                                            'pending'  => ['label' => __('ui.ads_dashboard.payment_status.pending'), 'class' => 'bg-amber-50 text-amber-700'],
                                            'paid'     => ['label' => __('ui.ads_dashboard.payment_status.paid'), 'class' => 'bg-green-50 text-green-700'],
                                            'failed'   => ['label' => __('ui.ads_dashboard.payment_status.failed'), 'class' => 'bg-red-50 text-red-700'],
                                            'refunded' => ['label' => __('ui.ads_dashboard.payment_status.refunded'), 'class' => 'bg-zinc-100 text-zinc-700'],
                                        ];
                                        $approvalLabels = [
                                            'pending'  => ['label' => __('ui.ads_dashboard.approval_status.pending'), 'class' => 'bg-amber-50 text-amber-700'],
                                            'approved' => ['label' => __('ui.ads_dashboard.approval_status.approved'), 'class' => 'bg-green-50 text-green-700'],
                                            'rejected' => ['label' => __('ui.ads_dashboard.approval_status.rejected'), 'class' => 'bg-red-50 text-red-700'],
                                        ];
                                        $payment = $paymentLabels[$campaign->payment_status] ?? ['label' => '—', 'class' => 'bg-zinc-100 text-zinc-700'];
                                        $approval = $approvalLabels[$campaign->approval_status] ?? ['label' => '—', 'class' => 'bg-zinc-100 text-zinc-700'];
                                    @endphp
                                    <tr class="hover:bg-zinc-50/80">
                                        <td class="px-4 py-3 font-semibold text-zinc-800 max-w-[180px] truncate">
                                            {{ $campaign->title }}
                                        </td>
                                        <td class="px-4 py-3 text-zinc-600">{{ $placementLabel }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $payment['class'] }}">
                                                {{ $payment['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $approval['class'] }}">
                                                {{ $approval['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-bold text-zinc-800 whitespace-nowrap">
                                            @if ($amount !== null)
                                                {{ number_format((float) $amount, 2) }} {{ config('ad_pricing.currency', 'EGP') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-zinc-500 whitespace-nowrap">
                                            {{ $campaign->starts_at?->format('Y/m/d') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-zinc-500 whitespace-nowrap">
                                            {{ $campaign->ends_at?->format('Y/m/d') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-end whitespace-nowrap {{ $isRtl ? 'space-x-reverse' : '' }} space-x-2">
                                            <a href="{{ route('dashboard.ads.show', $campaign) }}"
                                               class="text-nilex font-bold hover:underline text-xs">
                                                {{ __('ui.ads_dashboard.index.view') }}
                                            </a>
                                            @if (in_array($campaign->payment_status, ['failed', 'pending'], true))
                                                <form action="{{ route('dashboard.ads.retry-payment', $campaign) }}"
                                                      method="POST"
                                                      class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="text-red-600 font-bold hover:underline text-xs">
                                                        {{ __('ui.ads_dashboard.index.retry_payment') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 py-3 border-t border-zinc-100">
                        {{ $campaigns->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
