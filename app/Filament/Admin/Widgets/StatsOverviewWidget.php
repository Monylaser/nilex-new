<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Listing;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected function getStats(): array
    {
        $pendingCount  = Listing::pending()->count();
        $totalUsers    = User::count();
        $activeUsers   = User::where('is_banned', false)->count();
        $activeAds     = Listing::where('status', 'published')->count();
        $bannedUsers   = User::where('is_banned', true)->count();
        $flaggedAds    = Listing::where('is_flagged', true)->count();

        return [
            Stat::make('المستخدمون النشطون', number_format($activeUsers))
                ->description('غير محظورين على المنصة')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('الإعلانات المنشورة', number_format($activeAds))
                ->description('منشورة على المنصة حالياً')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('الإعلانات قيد المراجعة', $pendingCount)
                ->description('تحتاج إلى قرار')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCount > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.moderation.index')),

            Stat::make('إجمالي المستخدمين', number_format($totalUsers))
                ->description('مسجلون في المنصة')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('الإعلانات المُبلَّغ عنها تلقائياً', $flaggedAds)
                ->description('رُصدت بواسطة نظام الكشف التلقائي')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($flaggedAds > 0 ? 'danger' : 'success'),

            Stat::make('المستخدمون المحظورون', $bannedUsers)
                ->description('تم إيقاف حساباتهم')
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color($bannedUsers > 0 ? 'danger' : 'success'),
        ];
    }
}
