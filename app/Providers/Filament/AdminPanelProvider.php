<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Livewire\SmartAdCreator;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Admin\Widgets\BestSellingPlansChart;
use App\Filament\Admin\Widgets\CategoriesChartWidget;
use App\Filament\Admin\Widgets\ConversionMetricsWidget;
use App\Filament\Admin\Widgets\LeadFunnelWidget;
use App\Filament\Admin\Widgets\CategoryPerformanceWidget;
use App\Filament\Admin\Widgets\TopListingsWidget;
use App\Filament\Admin\Widgets\DailyRevenueWidget;
use App\Filament\Admin\Widgets\GovernoratesChartWidget;
use App\Filament\Admin\Widgets\MonetizationOverviewWidget;
use App\Filament\Admin\Widgets\MonthlyRevenueWidget;
use App\Filament\Admin\Widgets\RevenueAlertWidget;
use App\Filament\Admin\Widgets\StatsOverviewWidget;
use App\Filament\Admin\Widgets\WeeklyRevenueChart;
use JeffersonGoncalves\FilamentTranslatable\FilamentTranslatablePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->darkMode(true)
            ->colors([
                'primary' => Color::Violet,
            ])
            ->brandName('Nilex Admin')
            ->brandLogo(asset('images/logo/download.png'))
            ->brandLogoHeight('40px')
            ->favicon(asset('images/logo/download.png'))
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                MonetizationOverviewWidget::class,
                DailyRevenueWidget::class,
                RevenueAlertWidget::class,
                WeeklyRevenueChart::class,
                MonthlyRevenueWidget::class,
                ConversionMetricsWidget::class,
                LeadFunnelWidget::class,
                TopListingsWidget::class,
                CategoryPerformanceWidget::class,
                StatsOverviewWidget::class,
                Widgets\AccountWidget::class,
                \App\Filament\Admin\Widgets\ListingsChart::class,
                GovernoratesChartWidget::class,
                CategoriesChartWidget::class,
                BestSellingPlansChart::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('الحملات الإعلانية')
                    ->collapsed(false),
                NavigationGroup::make('Settings')
                    ->collapsed(false),
                NavigationGroup::make('إدارة الوصول')
                    ->collapsed(false),
                NavigationGroup::make('الإشراف')
                    ->collapsed(false),
                NavigationGroup::make('المحتوى')
                    ->collapsed(false),
                NavigationGroup::make('الإدارة')
                    ->collapsed(true),
            ])
            ->livewireComponents([
                'smart-ad-creator' => SmartAdCreator::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentTranslatablePlugin::make()->defaultLocales(['ar', 'en']),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}