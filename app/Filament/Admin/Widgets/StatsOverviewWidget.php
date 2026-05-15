<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Listing;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pendingCount = Listing::pending()->count();
        $totalUsers   = User::count();
        $activeAds    = Listing::where('status', 'published')->count();
        $bannedUsers  = User::where('is_banned', true)->count();
        $flaggedAds   = Listing::flagged()->count();

        return [
            Stat::make('الإعلانات قيد المراجعة', $pendingCount)
                ->description('تحتاج إلى قرار')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCount > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.moderation.index')),

            Stat::make('إجمالي المستخدمين', $totalUsers)
                ->description('مسجلون في المنصة')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('الإعلانات النشطة', $activeAds)
                ->description('منشورة على المنصة')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('المستخدمون المحظورون', $bannedUsers)
                ->description('تم إيقاف حساباتهم')
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color($bannedUsers > 0 ? 'danger' : 'success'),

            Stat::make('الإعلانات المُبلَّغ عنها', $flaggedAds)
                ->description('تحتاج مراجعة أمنية')
                ->descriptionIcon('heroicon-m-flag')
                ->color($flaggedAds > 0 ? 'danger' : 'success'),
        ];
    }
}