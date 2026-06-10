<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Services\OtpService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService,
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
            'otp.required' => 'يرجى إدخال كود التفعيل',
            'otp.digits' => 'الكود يجب أن يتكون من 4 أرقام',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->otpService->ensureNotLocked($user);

        if ($this->otpService->verify($user, $request->string('otp')->toString())) {
            return redirect()->route('dashboard')->with('success', 'تم تفعيل حسابك بنجاح! 🎉');
        }

        return back()->withErrors(['otp' => 'الكود غير صحيح أو انتهت صلاحيته.']);
    }

    public function resend()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->otpService->resend($user);

        return back()->with('status', 'تم إرسال كود جديد بنجاح.');
    }
}
