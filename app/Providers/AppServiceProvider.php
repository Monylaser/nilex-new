<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PointService; // لازم الـ use تكون هنا قبل الـ class

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // تسجيل الـ Service كـ Singleton
        $this->app->singleton(PointService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       // سجل المراقب هنا عشان يراقب كل عملية تسجيل نقاط
    \App\Models\PointTransaction::observe(\App\Observers\PointTransactionObserver::class);
    }
}
