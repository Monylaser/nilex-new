{{-- resources/views/livewire/frontend/business-dashboard.blade.php --}}
<div class="bg-zinc-50 min-h-screen pb-10" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- Header --}}
        <div class="bg-white rounded-2xl border border-zinc-100 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
             style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
            <div>
                <h1 class="text-xl font-black text-zinc-900">{{ __('ui.analytics.business_title') }}</h1>
                <p class="text-sm text-zinc-500 mt-1">{{ __('ui.analytics.business_subtitle') }}</p>
            </div>
            <div class="flex gap-2 shrink-0">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center justify-center gap-2 bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 font-bold py-3 px-5 rounded-xl transition-all text-sm">
                    {{ __('ui.leads.back_dashboard') }}
                </a>
                <button wire:click="exportCsv"
                        class="flex items-center justify-center gap-2 bg-[#1D9E75] hover:bg-[#178a66] text-white font-bold py-3 px-5 rounded-xl transition-all text-sm active:scale-95"
                        style="box-shadow:0 4px 14px rgba(29,158,117,0.22);">
                    {{ __('ui.analytics.export_csv') }}
                </button>
            </div>
        </div>

        {{-- Revenue Summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white rounded-2xl border border-zinc-100 p-5 text-center" style="box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                <p class="text-xs text-zinc-400 font-semibold mb-1">{{ __('ui.analytics.revenue_summary') }}</p>
                <p class="text-2xl font-black text-zinc-900">{{ number_format($pointsSpentOnBoosts) }}</p>
                <p class="text-xs text-zinc-500 mt-1">{{ __('ui.analytics.points_spent_boosts') }}</p>
            </div>
            <div class="bg-nilex/5 rounded-2xl border border-nilex/15 p-5 text-center">
                <p class="text-xs text-nilex font-semibold mb-1">{{ __('ui.analytics.top_listings') }}</p>
                <p class="text-2xl font-black text-nilex">{{ $topListings->count() }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-zinc-100 p-5 text-center" style="box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                <p class="text-xs text-zinc-400 font-semibold mb-1">{{ __('ui.pricing.features.credits') }}</p>
                <p class="text-2xl font-black text-zinc-900">{{ number_format($user->points ?? 0) }}</p>
            </div>
        </div>

        {{-- Monthly Performance Chart --}}
        <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
            <h3 class="text-base font-black text-zinc-900 mb-4">{{ __('ui.pricing.features.monthly_reports') }}</h3>
            <div wire:ignore class="relative h-64 w-full"
                 id="businessMonthlyChartContainer"
                 data-labels="{{ json_encode($monthlyPerformance['labels'] ?? []) }}"
                 data-values="{{ json_encode($monthlyPerformance['values'] ?? []) }}">
                <canvas id="businessMonthlyChart"></canvas>
            </div>
        </div>

        {{-- Top Performing Listings --}}
        <div class="bg-white rounded-2xl border border-zinc-100 overflow-hidden" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
            <div class="px-5 py-4 border-b border-zinc-100">
                <h3 class="text-base font-black text-zinc-900">{{ __('ui.analytics.top_listings') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead class="bg-zinc-50 text-zinc-400 text-xs font-bold">
                        <tr>
                            <th class="px-5 py-3">{{ __('ui.leads.col_listing') }}</th>
                            <th class="px-5 py-3">👁</th>
                            <th class="px-5 py-3">💬</th>
                            <th class="px-5 py-3">{{ __('ui.analytics.your_price') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-50">
                        @forelse($topListings as $listing)
                            <tr class="hover:bg-zinc-50/60">
                                <td class="px-5 py-3 font-bold text-zinc-800 truncate max-w-[200px]">{{ $listing->title }}</td>
                                <td class="px-5 py-3">{{ number_format($listing->views_count) }}</td>
                                <td class="px-5 py-3">{{ number_format($listing->whatsapp_clicks) }}</td>
                                <td class="px-5 py-3 font-black text-nilex">{{ number_format($listing->price) }} {{ __('ui.sections.currency') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-zinc-400">{{ __('ui.leads.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Competitor Comparison --}}
        @if(count($competitorComparison) > 0)
            <div class="bg-white rounded-2xl border border-zinc-100 overflow-hidden" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                <div class="px-5 py-4 border-b border-zinc-100">
                    <h3 class="text-base font-black text-zinc-900">{{ __('ui.analytics.competitor_comparison') }}</h3>
                </div>
                <div class="divide-y divide-zinc-50">
                    @foreach($competitorComparison as $row)
                        <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-zinc-800 truncate">{{ $row['title'] }}</p>
                                <p class="text-xs text-zinc-400">{{ $row['category'] }}</p>
                            </div>
                            <div class="flex items-center gap-4 text-sm shrink-0">
                                <span>{{ __('ui.analytics.your_price') }}: <strong>{{ number_format($row['price']) }}</strong></span>
                                @if($row['avg_category'])
                                    <span>{{ __('ui.analytics.avg_category_price') }}: <strong>{{ number_format($row['avg_category']) }}</strong></span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ ($row['diff_percent'] ?? 0) > 0 ? 'bg-red-50 text-red-600' : 'bg-nilex/8 text-nilex' }}">
                                        {{ ($row['diff_percent'] ?? 0) > 0 ? '+' : '' }}{{ $row['diff_percent'] }}%
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- All Listings Analytics --}}
        <div class="bg-white rounded-2xl border border-zinc-100 overflow-hidden" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
            <div class="px-5 py-4 border-b border-zinc-100 flex justify-between items-center">
                <h3 class="text-base font-black text-zinc-900">{{ __('ui.pricing.features.event_views') }}</h3>
                <span class="text-sm font-bold text-zinc-500">{{ $listings->count() }} {{ __('ui.dashboard.listing_count_suffix') }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead class="bg-zinc-50 text-zinc-400 text-xs font-bold">
                        <tr>
                            <th class="px-5 py-3">{{ __('ui.leads.col_listing') }}</th>
                            <th class="px-5 py-3">{{ __('ui.leads.col_status') }}</th>
                            <th class="px-5 py-3">👁</th>
                            <th class="px-5 py-3">💬</th>
                            <th class="px-5 py-3">{{ __('ui.analytics.your_price') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-50">
                        @foreach($listings as $listing)
                            <tr class="hover:bg-zinc-50/60">
                                <td class="px-5 py-3 font-bold text-zinc-800 truncate max-w-[180px]">{{ $listing->title }}</td>
                                <td class="px-5 py-3">
                                    <span class="text-xs font-bold bg-zinc-100 text-zinc-600 px-2 py-0.5 rounded-full">{{ $listing->status }}</span>
                                </td>
                                <td class="px-5 py-3">{{ number_format($listing->views_count) }}</td>
                                <td class="px-5 py-3">{{ number_format($listing->whatsapp_clicks) }}</td>
                                <td class="px-5 py-3 font-black">{{ number_format($listing->price) }} {{ __('ui.sections.currency') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
    (function () {
        let businessChart = null;

        function initBusinessChart() {
            const container = document.getElementById('businessMonthlyChartContainer');
            const canvas    = document.getElementById('businessMonthlyChart');
            if (!container || !canvas) return;

            if (businessChart) {
                businessChart.destroy();
                businessChart = null;
            }

            businessChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: JSON.parse(container.getAttribute('data-labels') || '[]'),
                    datasets: [{
                        label: '{{ __('ui.dashboard.chart_views') }}',
                        data: JSON.parse(container.getAttribute('data-values') || '[]'),
                        borderColor: '#1D9E75',
                        backgroundColor: 'rgba(29,158,117,0.1)',
                        fill: true,
                        tension: 0.3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    rtl: true,
                    scales: { y: { beginAtZero: true } },
                }
            });
        }

        document.addEventListener('DOMContentLoaded', initBusinessChart);
        document.addEventListener('livewire:navigated', initBusinessChart);
    })();
</script>
@endsection
