<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DailyRevenueWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected function getStats(): array
    {
        $today     = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        $revenues = Transaction::query()
            ->completed()
            ->whereDate('created_at', '>=', Carbon::yesterday())
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COALESCE(SUM(amount), 0) as revenue')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        $todayRevenue     = (float) ($revenues[$today] ?? 0);
        $yesterdayRevenue = (float) ($revenues[$yesterday] ?? 0);
        $growthPercent    = $this->calculateGrowthPercent($todayRevenue, $yesterdayRevenue);
        $isPositive       = $growthPercent >= 0;

        return [
            Stat::make('إيرادات اليوم', number_format($todayRevenue, 2) . ' ج.م')
                ->description('معاملات مكتملة اليوم')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('إيرادات أمس', number_format($yesterdayRevenue, 2) . ' ج.م')
                ->description('معاملات مكتملة أمس')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('نمو يومي', $this->formatGrowthPercent($growthPercent))
                ->description(
                    $yesterdayRevenue > 0
                        ? 'مقارنةً بإيرادات أمس'
                        : ($todayRevenue > 0 ? 'لا توجد إيرادات أمس للمقارنة' : 'لا توجد إيرادات للمقارنة'),
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
