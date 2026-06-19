<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/ads.php'));

            Route::middleware('web')
                ->group(base_path('routes/advertising.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ✅ guests يروحوا لـ login عادي، users يروحوا لـ dashboard
        $middleware->redirectTo(
            guests: '/login',
            users: '/dashboard'
        );

        $middleware->append([
            \App\Http\Middleware\PreventStorageCache::class,
            \App\Auth\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'otp.verified'      => \App\Http\Middleware\EnsureOtpIsVerified::class,
            'not.banned'        => \App\Auth\Middleware\EnsureUserIsNotBanned::class,
            'self_service_ads'  => \App\Http\Middleware\EnsureSelfServiceAdsEnabled::class,
        ]);

        $middleware->appendToGroup('web', \App\Auth\Middleware\EnsureUserIsNotBanned::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\TrackCampaign::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);

        $middleware->validateCsrfTokens(except: [
            'payments/callback',
            'payment/webhook',
            'webhooks/paymob/ads',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();