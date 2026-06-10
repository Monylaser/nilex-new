<?php

namespace App\Listeners\Auth;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Request;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip'         => Request::ip(),
                'user_agent' => Request::userAgent(),
                'credentials' => [
                    'email' => $event->credentials['email'] ?? null,
                ],
                'guard' => $event->guard,
            ])
            ->log('محاولة دخول فاشلة');
    }
}
