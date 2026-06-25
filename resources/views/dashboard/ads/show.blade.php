@php
    $isRtl = app()->getLocale() === 'ar';
    $localeLabel = fn ($row, $key) => $isRtl
        ? ($row['label_ar'] ?? $row['label'] ?? $key)
        : ($row['label_en'] ?? $row['label'] ?? $key);
@endphp
<x-app-layout>
    <div class="bg-zinc-50 min-h-screen pb-10" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-zinc-900">{{ $campaign->title }}</h1>
                    <p class="text-sm text-zinc-500 mt-1">{{ __('ui.ads_dashboard.show.subtitle') }}</p>
                </div>
                <a href="{{ route('dashboard.ads.index') }}"
                   class="inline-flex items-center gap-2 text-sm font-bold text-nilex hover:text-nilex-dark">
                    ← {{ __('ui.ads_dashboard.common.back_campaigns') }}
                </a>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-semibold">
                    {{ $errors->first() }}
                </div>
            @endif

            @php
                $placementRow = config("ad_pricing.placements.{$campaign->placement}", []);
                $placementLabel = $localeLabel($placementRow, $campaign->placement);
                $durationRow = config("ad_pricing.durations.{$campaign->duration_days}", []);
                $durationLabel = $campaign->duration_days
                    ? $localeLabel($durationRow, __('ui.ads_dashboard.common.day_fallback', ['count' => $campaign->duration_days]))
                    : '—';
                $amount = $campaign->amount_paid ?? $expectedAmount;
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
                $banner = $campaign->getFirstMedia('ad_image');
            @endphp

            <div class="bg-white rounded-2xl border border-zinc-100 overflow-hidden" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                @if ($banner)
                    <div class="border-b border-zinc-100 bg-zinc-50 p-4">
                        <img src="{{ $banner->getUrl() }}"
                             alt="{{ $campaign->title }}"
                             class="w-full max-h-48 object-contain rounded-xl">
                    </div>
                @endif

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">{{ __('ui.ads_dashboard.show.placement') }}</p>
                            <p class="font-semibold text-zinc-800">{{ $placementLabel }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">{{ __('ui.ads_dashboard.show.duration') }}</p>
                            <p class="font-semibold text-zinc-800">{{ $durationLabel }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">{{ __('ui.ads_dashboard.show.payment') }}</p>
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $payment['class'] }}">
                                {{ $payment['label'] }}
                            </span>
                        </div>
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">{{ __('ui.ads_dashboard.show.approval') }}</p>
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $approval['class'] }}">
                                {{ $approval['label'] }}
                            </span>
                        </div>
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">{{ __('ui.ads_dashboard.show.amount') }}</p>
                            <p class="font-semibold text-zinc-800">
                                @if ($amount !== null)
                                    {{ number_format((float) $amount, 2) }} {{ $currency }}
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">{{ __('ui.ads_dashboard.show.target_url') }}</p>
                            <a href="{{ $campaign->target_url }}"
                               target="_blank"
                               rel="noopener"
                               dir="ltr"
                               class="font-semibold text-nilex hover:underline break-all">
                                {{ $campaign->target_url }}
                            </a>
                        </div>
                        @if ($campaign->category)
                            <div>
                                <p class="text-zinc-500 font-bold mb-1">{{ __('ui.ads_dashboard.show.category') }}</p>
                                <p class="font-semibold text-zinc-800">{{ $campaign->category->name }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">{{ __('ui.ads_dashboard.show.starts') }}</p>
                            <p class="font-semibold text-zinc-800">{{ $campaign->starts_at?->format('Y/m/d H:i') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">{{ __('ui.ads_dashboard.show.ends') }}</p>
                            <p class="font-semibold text-zinc-800">{{ $campaign->ends_at?->format('Y/m/d H:i') ?? '—' }}</p>
                        </div>
                    </div>

                    @if ($campaign->approval_status === 'rejected' && $campaign->rejected_reason)
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
                            <p class="font-bold mb-1">{{ __('ui.ads_dashboard.show.reject_reason') }}</p>
                            <p>{{ $campaign->rejected_reason }}</p>
                        </div>
                    @endif

                    @if (in_array($campaign->payment_status, ['failed', 'pending'], true))
                        <form action="{{ route('dashboard.ads.retry-payment', $campaign) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-red-600 text-white text-sm font-bold hover:bg-red-700 transition">
                                {{ __('ui.ads_dashboard.show.retry_payment') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
