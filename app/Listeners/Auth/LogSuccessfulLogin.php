<?php

namespace App\Listeners\Auth;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;
use Spatie\Activitylog\Facades\LogBatch;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip'         => Request::ip(),
                'user_agent' => Request::userAgent(),
                'guard'      => $event->guard,
            ])
            ->log('تسجيل دخول ناجح');
    }
}
