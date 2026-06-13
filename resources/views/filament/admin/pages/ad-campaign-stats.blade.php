<x-filament-panels::page>
    @php
        $chart = $this->getImpressionsChartData();
        $topCampaigns = $this->getTopCampaigns();
    @endphp

    <div class="space-y-6">
        <x-filament::section heading="تصفية الفترة الزمنية">
            <div class="max-w-xl">
                {{ $this->filterForm }}
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-filament::section>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        إجمالي المشاهدات
                    </p>
                    <p class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ number_format($this->getTotalViews()) }}
                    </p>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        إجمالي النقرات
                    </p>
                    <p class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ number_format($this->getTotalClicks()) }}
                    </p>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        متوسط CTR
                    </p>
                    <p class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ $this->getAverageCtr() }}
                    </p>
                </div>
            </x-filament::section>
        </div>

        <x-filament::section heading="المشاهدات اليومية (آخر 30 يوم)">
            <div
                wire:ignore
                class="relative"
                style="height: 320px;"
            >
                <canvas id="ad-campaign-impressions-chart"></canvas>
            </div>
        </x-filament::section>

        <x-filament::section heading="أفضل الحملات">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-start dark:divide-white/10">
                    <thead>
                        <tr class="text-sm font-semibold text-gray-950 dark:text-white">
                            <th class="px-3 py-2">العنوان</th>
                            <th class="px-3 py-2">المشاهدات</th>
                            <th class="px-3 py-2">النقرات</th>
                            <th class="px-3 py-2">CTR%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($topCampaigns as $campaign)
                            <tr class="text-sm text-gray-700 dark:text-gray-300">
                                <td class="px-3 py-2">{{ $campaign->title }}</td>
                                <td class="px-3 py-2">{{ number_format($campaign->views_count) }}</td>
                                <td class="px-3 py-2">{{ number_format($campaign->clicks_count) }}</td>
                                <td class="px-3 py-2">{{ $campaign->ctr }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    class="px-3 py-4 text-center text-gray-500 dark:text-gray-400"
                                    colspan="4"
                                >
                                    لا توجد حملات بعد
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    @script
    <script>
        const chartLabels = @js($chart['labels']);
        const chartData = @js($chart['data']);

        function renderAdCampaignChart() {
            const canvas = document.getElementById('ad-campaign-impressions-chart');

            if (!canvas || typeof Chart === 'undefined') {
                return;
            }

            if (window.adCampaignImpressionsChart) {
                window.adCampaignImpressionsChart.destroy();
            }

            window.adCampaignImpressionsChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'المشاهدات',
                        data: chartData,
                        backgroundColor: 'rgba(139, 92, 246, 0.2)',
                        borderColor: '#8b5cf6',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                        },
                    },
                },
            });
        }

        renderAdCampaignChart();
    </script>
    @endscript
</x-filament-panels::page>
