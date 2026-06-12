<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class MonthlyRevenueWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected function getStats(): array
    {
        $currentMonthKey  = Carbon::now()->format('Y-m');
        $previousMonthKey = Carbon::now()->subMonth()->format('Y-m');

        $revenues = Transaction::query()
            ->completed()
            ->where('created_at', '>=', Carbon::now()->subMonth()->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw('COALESCE(SUM(amount), 0) as revenue')
            ->groupBy('month')
            ->pluck('revenue', 'month');

        $currentMonthRevenue  = (float) ($revenues[$currentMonthKey] ?? 0);
        $previousMonthRevenue = (float) ($revenues[$previousMonthKey] ?? 0);
        $growthPercent        = $this->calculateGrowthPercent($currentMonthRevenue, $previousMonthRevenue);
        $isPositive           = $growthPercent >= 0;

        return [
            Stat::make('إيرادات الشهر الحالي', number_format($currentMonthRevenue, 2) . ' ج.م')
                ->description(Carbon::now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),

            Stat::make('إيرادات الشهر السابق', number_format($previousMonthRevenue, 2) . ' ج.م')
                ->description(Carbon::now()->subMonth()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('نمو شهري', $this->formatGrowthPercent($growthPercent))
                ->description(
                    $previousMonthRevenue > 0
                        ? 'مقارنةً بإيرادات الشهر السابق'
                        : ($currentMonthRevenue > 0 ? 'لا توجد إيرادات الشهر السابق للمقارنة' : 'لا توجد إيرادات للمقارنة'),
                )
                ->descriptionIcon($isPositive ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($isPositive ? 'success' : 'danger'),
        ];
    }

    private function calculateGrowthPercent(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    private function formatGrowthPercent(float $percent): string
    {
        $sign = $percent > 0 ? '+' : '';

        return $sign . number_format($percent, 1) . '%';
    }
}
