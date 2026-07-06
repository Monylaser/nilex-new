<?php

namespace App\Auth\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AuthSecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Auth\Services\OtpService::class);
        $this->app->singleton(\App\Auth\Services\DeviceFingerprintService::class);
        $this->app->singleton(\App\Auth\Services\DeviceLimitService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('otp-resend', function (Request $request) {
            $user = $request->user();
            $identity = $user?->email ?? $user?->phone ?? 'guest';

            return [
                Limit::perMinute(3)->by($request->ip().'|'.$identity),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();

            return [
                Limit::perMinute(10)->by('otp-verify|'.$key),
            ];
        });

        RateLimiter::for('registration', function (Request $request) {
            return [
                Limit::perHour(10)->by($request->ip()),
            ];
        });

        // حماية نقاط توثيق الهاتف/البريد في الملف الشخصي (إرسال + تأكيد):
        // 5 محاولات كل 60 ثانية لكل (IP + مستخدم) لمنع القصف وتخمين الكود.
        RateLimiter::for('otp-profile', function (Request $request) {
            $identity = $request->user()?->id ?? 'guest';

            return [
                Limit::perMinute(5)->by('otp-profile|'.$request->ip().'|'.$identity),
            ];
        });
    }
}
