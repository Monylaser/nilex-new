<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MonetizationOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected function getStats(): array
    {
        $revenueStats = Transaction::query()
            ->completed()
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_revenue')
            ->first();

        $totalRevenue         = (float) $revenueStats->total_revenue;
        $totalTransactions    = (int) $revenueStats->total_transactions;
        $averageOrderValue    = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0.0;

        $totalPointsSold = (int) Transaction::query()
            ->completed()
            ->join('point_plans', 'transactions.plan_id', '=', 'point_plans.id')
            ->sum('point_plans.points');

        return [
            Stat::make('إجمالي الإيرادات', number_format($totalRevenue, 2) . ' ج.م')
                ->description('من المعاملات المكتملة')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('المعاملات المكتملة', number_format($totalTransactions))
                ->description('إجمالي عمليات الدفع الناجحة')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info'),

            Stat::make('متوسط قيمة الطلب', number_format($averageOrderValue, 2) . ' ج.م')
                ->description('إيرادات ÷ عدد المعاملات')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),

            Stat::make('إجمالي النقاط المباعة', number_format($totalPointsSold) . ' نقطة')
                ->description('من الباقات المشتراة')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('success'),
        ];
    }
}
