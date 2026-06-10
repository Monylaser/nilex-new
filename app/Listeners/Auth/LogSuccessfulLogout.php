<?php

namespace App\Listeners\Auth;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip'         => Request::ip(),
                'user_agent' => Request::userAgent(),
                'guard'      => $event->guard,
            ])
            ->log('تسجيل خروج');
    }
}
