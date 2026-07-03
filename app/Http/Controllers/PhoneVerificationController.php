<?php

namespace App\Http\Controllers;

use App\Auth\Services\OtpService;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Post-registration phone verification (from the profile page).
 *
 * Flow:
 *   POST /profile/phone        -> validate + stage pending_phone + send OTP via SMS
 *   POST /profile/phone/verify -> confirm code -> commit phone + is_phone_verified
 *                                 + phone_verified_at + (one-time) +50 bonus
 *
 * The +50 bonus is granted at most ONCE per account lifetime, guarded by the
 * permanent users.phone_bonus_claimed_at marker — never re-granted even if the
 * user later changes and re-verifies a different number.
 */
class PhoneVerificationController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private PointService $pointService,
    ) {}

    /** Stage a new phone number and send an OTP to it via SMS. */
    public function send(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/', 'unique:users,phone,'.$user->id],
        ], [
            'phone.required' => __('server.phone.required'),
            'phone.regex'    => __('server.phone.invalid'),
            'phone.unique'   => __('server.phone.already_taken'),
        ]);

        $this->otpService->issueForPhone($user, $validated['phone']);

        return redirect()->route('profile.edit')->with('status', 'phone-otp-sent');
    }

    /** Confirm the OTP, commit the pending phone, and grant the one-time bonus. */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'numeric', 'digits:4'],
        ], [
            'otp.required' => __('server.auth.otp_required'),
            'otp.digits'   => __('server.auth.otp_digits'),
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user->pending_phone) {
            return back()->withErrors(['otp' => __('server.phone.no_pending')]);
        }

        // ensureNotLocked throws a ValidationException (with a localized message) when throttled.
        $this->otpService->ensureNotLocked($user);

        if (! $this->otpService->verifyPhone($user, $request->string('otp')->toString())) {
            return back()->withErrors(['otp' => __('server.phone.otp_invalid')]);
        }

        $pending = $user->pending_phone;

        // TOCTOU guard: another account may have taken this number since `send()`.
        if (User::where('phone', $pending)->where('id', '!=', $user->id)->exists()) {
            $user->forceFill(['pending_phone' => null, 'otp_channel' => null])->save();

            return back()->withErrors(['otp' => __('server.phone.already_taken')]);
        }

        DB::transaction(function () use ($user, $pending) {
            // One-time-per-lifetime bonus, gated by the permanent marker.
            $grantBonus = is_null($user->phone_bonus_claimed_at);

            $user->forceFill([
                'phone'                  => $pending,
                'is_phone_verified'      => true,
                'phone_verified_at'      => now(),
                'pending_phone'          => null,
                'otp_channel'            => null,
                'phone_bonus_claimed_at' => $grantBonus ? now() : $user->phone_bonus_claimed_at,
            ])->save();

            if ($grantBonus) {
                $this->pointService->credit($user, 15, 'مكافأة توثيق رقم الهاتف');
            }
        });

        return redirect()->route('profile.edit')->with('status', 'phone-verified');
    }
}
