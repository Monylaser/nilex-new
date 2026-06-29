<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                // لو الحساب غير مؤكَّد بأيّ قناة (لا هاتف ولا بريد) يروح لصفحة الـ OTP
                if (! $user->is_phone_verified && $user->email_verified_at === null) {
                    return redirect()->route('otp.notice');
                }

                // لو Admin يروح للـ Admin Panel
                if ($user->role === 'super_admin' || $user->role === 'admin') {
                    return redirect('/admin');
                }

                // باقي المستخدمين يروحوا للـ dashboard
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}