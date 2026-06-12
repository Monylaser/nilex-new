<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConversionMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected function getStats(): array
    {
        $registeredUsers = User::count();

        $payingUsers = (int) Transaction::query()
            ->completed()
            ->distinct()
            ->count('user_id');

        $conversionRate = $registeredUsers > 0
            ? ($payingUsers / $registeredUsers) * 100
            : 0.0;

        return [
            Stat::make('المستخدمون المسجلون', number_format($registeredUsers))
                ->description('إجمالي الحسابات على المنصة')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('المستخدمون الدافعون', number_format($payingUsers))
                ->description('أكملوا معاملة دفع واحدة على الأقل')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),

            Stat::make('معدل التحويل', number_format($conversionRate, 2) . '%')
                ->description('دافعون ÷ مسجلون × 100')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($conversionRate >= 5 ? 'success' : 'warning'),
        ];
    }
}
