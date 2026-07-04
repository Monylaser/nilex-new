<?php

namespace App\Http\Controllers;

use App\Auth\Services\OtpService;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Post-registration email verification flow (from the profile page).
 *
 * Mirrors PhoneVerificationController's pattern exactly:
 *   POST /profile/email        -> validate + stage pending_email + send OTP via email
 *   POST /profile/email/verify -> confirm code -> commit email + email_verified_at
 *                                 + (one-time) +20 bonus
 *
 * The +20 bonus is granted at most ONCE per account lifetime, guarded by the
 * permanent users.email_bonus_claimed_at marker.
 */
class EmailVerificationProfileController extends Controller
{
    public function __construct(
        private OtpService   $otpService,
        private PointService $pointService,
    ) {}

    /** Stage a new email address and send an OTP to it. */
    public function send(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
        ], [
            'email.required' => __('server.email.required'),
            'email.email'    => __('server.email.invalid'),
            'email.unique'   => __('server.email.already_taken'),
        ]);

        $this->otpService->issueForEmail($user, $validated['email']);

        return redirect()->route('profile.edit')->with('status', 'email-otp-sent');
    }

    /** Confirm the OTP, commit the pending email, and grant the one-time bonus. */
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

        if (! $user->pending_email) {
            return back()->withErrors(['otp' => __('server.email.no_pending')]);
        }

        // Throws a ValidationException (localized) when the user is rate-limited.
        $this->otpService->ensureNotLocked($user);

        if (! $this->otpService->verifyEmail($user, $request->string('otp')->toString())) {
            return back()->withErrors(['otp' => __('server.email.otp_invalid')]);
        }

        $pending = $user->pending_email;

        // TOCTOU guard: another account may have taken this address since send().
        if (User::where('email', $pending)->where('id', '!=', $user->id)->exists()) {
            $user->forceFill(['pending_email' => null, 'otp_channel' => null])->save();

            return back()->withErrors(['otp' => __('server.email.already_taken')]);
        }

        DB::transaction(function () use ($user, $pending) {
            $grantBonus = is_null($user->email_bonus_claimed_at);

            $user->forceFill([
                'email'                  => $pending,
                'email_verified_at'      => now(),
                'pending_email'          => null,
                'otp_channel'            => null,
                'email_bonus_claimed_at' => $grantBonus ? now() : $user->email_bonus_claimed_at,
            ])->save();

            if ($grantBonus) {
                $this->pointService->credit($user, 20, 'مكافأة توثيق البريد الإلكتروني');
            }
        });

        return redirect()->route('profile.edit')->with('status', 'email-verified');
    }
}
