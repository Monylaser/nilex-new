<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOtpIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 🛡️ الإضافة: لو اليوزر واقف بالفعل في صفحة الـ OTP أو بيحاول يبعت الكود، خليه يكمل ومتحولوش تاني
        if ($request->routeIs('otp.notice') || $request->routeIs('otp.verify') || $request->routeIs('otp.resend')) {
            return $next($request);
        }

        // كودك الأصلي زي ما هو بالملي بدون حذف أي حرف:
        // لو اليوزر مسجل دخول، بس حقل is_phone_verified لسه false
        if ($request->user() && !$request->user()->is_phone_verified) {
            // نرجعه فوراً لصفحة إدخال الكود
            return redirect()->route('otp.notice')->with('error', 'يجب تأكيد حسابك أولاً للوصول لهذه الصفحة.');
        }

        // لو متفعل، خليه يكمل طريقه عادي
        return $next($request);
    }
}