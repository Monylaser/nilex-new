<?php

namespace App\Providers;

use App\Listeners\Auth\LogFailedLogin;
use App\Listeners\Auth\LogRoleAssigned;
use App\Listeners\Auth\LogRoleRevoked;
use App\Listeners\Auth\LogSuccessfulLogin;
use App\Listeners\Auth\LogSuccessfulLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Services\PointService;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PointService::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        \App\Models\PointTransaction::observe(\App\Observers\PointTransactionObserver::class);
        \App\Models\Listing::observe(\App\Observers\ListingObserver::class);

        // ── Auth Activity Listeners ───────────────────────────────────────────
        Event::listen(Login::class,          LogSuccessfulLogin::class);
        Event::listen(Logout::class,         LogSuccessfulLogout::class);
        Event::listen(Failed::class,         LogFailedLogin::class);
        Event::listen(RoleAttachedEvent::class, LogRoleAssigned::class);
        Event::listen(RoleDetachedEvent::class, LogRoleRevoked::class);

        // ── Footer Categories View Composer ────────────────────────────────────
        View::composer('layouts.app', function ($view) {
            try {
                $footerCategories = \App\Models\Category::where('is_active', true)
                    ->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->take(6)
                    ->get();
                $view->with('footerCategories', $footerCategories);
            } catch (\Exception $e) {
                $view->with('footerCategories', collect([]));
            }
        });
    }
}
