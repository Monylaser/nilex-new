<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Services\OtpService;
use App\Http\Controllers\Controller;
use App\Services\PointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private PointService $pointService,
    ) {}

    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->is_phone_verified) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-otp');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric|digits:4',
        ], [
            'otp.required' => __('server.auth.otp_required'),
            'otp.digits' => __('server.auth.otp_digits'),
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->otpService->ensureNotLocked($user);

        if ($this->otpService->verify($user, $request->string('otp')->toString())) {
            // ملاحظة: تأكيد الحساب عبر OTP (إيميل أو هاتف وقت التسجيل) لم يعد يمنح
            // أي مكافأة منفصلة — المكافأة الترحيبية (+50) عند إنشاء الحساب تغطّيه
            // ضمنياً. مكافأة "توثيق الهاتف +50" انتقلت إلى مسار توثيق الهاتف المنفصل
            // من البروفايل، وتُمنح مرة واحدة فقط في عمر الحساب عبر flag دائم.
            return redirect()->route('dashboard')->with('success', __('server.auth.verified_success'));
        }

        return back()->withErrors(['otp' => __('server.auth.otp_invalid')]);
    }

    public function resend()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->otpService->resend($user);

        return back()->with('status', __('server.auth.otp_resent'));
    }
}
