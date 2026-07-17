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
        // Kept for reference / possible reuse; registration resend route uses throttle:5,1.
        RateLimiter::for('otp-resend', function (Request $request) {
            $user = $request->user();
            $identity = $user?->email ?? $user?->phone ?? 'guest';

            return [
                Limit::perMinute(5)->by($request->ip().'|'.$identity),
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

        // كشف رقم الهاتف في الإعلانات: 15 كشفاً في الدقيقة لكل مستخدم
        // (أو IP للزوّار — يصلهم 401 أصلاً لكن نحدّ الطرق قبل الوصول للمنطق)
        // يمنع رشقات الحصاد الآلي مع السماح بتصفّح شرعي لعدة إعلانات.
        RateLimiter::for('phone-reveal', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();

            return [
                Limit::perMinute(15)->by('phone-reveal|'.$key),
            ];
        });

        // توليد إعلان بالذكاء الاصطناعي (Gemini): حدّان — رشقة قصيرة + سقف ساعي للتكلفة.
        RateLimiter::for('ai-generate', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();

            return [
                Limit::perMinute(3)->by('ai-generate-min|'.$key),
                Limit::perHour(20)->by('ai-generate-hour|'.$key),
            ];
        });
    }
}
