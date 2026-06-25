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

        $wasAlreadyVerified = $user->is_phone_verified;

        if ($this->otpService->verify($user, $request->string('otp')->toString())) {
            // Credit +50 points once per user on first successful phone verification
            if (! $wasAlreadyVerified) {
                $this->pointService->credit($user, 50, 'مكافأة توثيق رقم الهاتف');
            }

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
