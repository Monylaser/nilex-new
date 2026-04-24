<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 1. توجيه المستخدمين (الضيوف والمسجلين)
        $middleware->redirectTo(
            guests: '/admin/login',
            users: '/admin'
        );

        // 2. تسجيل الميدل وير الجديد لمنع كاش الصور (الخاص بـ DeepSeek)
        $middleware->append(\App\Http\Middleware\PreventStorageCache::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
