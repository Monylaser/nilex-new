<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class RevenueAlertWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int $dailyRevenueThresholdEgp = 500;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected function getStats(): array
    {
        $todayRevenue = (float) Transaction::query()
            ->completed()
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');

        $isHealthy = $todayRevenue >= $this->dailyRevenueThresholdEgp;
        $shortfall   = max(0, $this->dailyRevenueThresholdEgp - $todayRevenue);
        $progress    = min(100, ($todayRevenue / $this->dailyRevenueThresholdEgp) * 100);

        return [
            Stat::make('إيرادات اليوم', number_format($todayRevenue, 2) . ' ج.م')
                ->description(
                    $isHealthy
                        ? 'الإيرادات ضمن الهدف اليومي — أداء صحي'
                        : 'تحذير: الإيرادات اليومية أقل من الهدف',
                )
                ->descriptionIcon($isHealthy ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($isHealthy ? 'success' : 'danger')
                ->chart(array_map(
                    fn (int $i) => (int) round($todayRevenue * (($i + 1) / 7)),
                    range(0, 6),
                )),

            Stat::make('حالة الهدف اليومي', number_format($this->dailyRevenueThresholdEgp) . ' ج.م')
                ->description(
                    $isHealthy
                        ? 'تم تجاوز الهدف بمقدار ' . number_format($todayRevenue - $this->dailyRevenueThresholdEgp, 2) . ' ج.م'
                        : 'متبقٍ ' . number_format($shortfall, 2) . ' ج.م للوصول للهدف (' . number_format($progress, 0) . '%)',
                )
                ->descriptionIcon($isHealthy ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($isHealthy ? 'success' : 'warning'),
        ];
    }
}
