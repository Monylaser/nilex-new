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

        // 🔑 بوابة "تأكيد الحساب": تقبل أيّ قناة تأكيد —
        // توثيق هاتف فعلي (is_phone_verified) أو تأكيد بريد (email_verified_at).
        // هذا يفصل مفهوم "تأكيد الحساب" (المطلوب للوصول) عن "توثيق هاتف حقيقي"،
        // ويمنع حبس المستخدمين المسجَّلين بإيميل في حلقة إعادة توجيه لصفحة الـ OTP.
        $user = $request->user();
        if ($user && ! $user->is_phone_verified && $user->email_verified_at === null) {
            // غير مؤكَّد بأيّ قناة → نرجعه فوراً لصفحة إدخال الكود
            return redirect()->route('otp.notice')->with('error', __('server.auth.otp_gate'));
        }

        // لو متفعل، خليه يكمل طريقه عادي
        return $next($request);
    }
}