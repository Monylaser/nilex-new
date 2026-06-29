<?php

namespace App\Auth\Services;

use App\Auth\Jobs\SendOtpEmailJob;
use App\Auth\Jobs\SendOtpSmsJob;
use App\Auth\Jobs\SendPhoneVerificationSmsJob;
use App\Auth\ValueObjects\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function issue(User $user, bool $viaEmail): OtpCode
    {
        $length = config('auth-security.otp.length', 4);
        $otp = OtpCode::generate($length);
        $minutes = config('auth-security.otp.expires_minutes', 5);

        $user->update([
            'otp_code' => $otp->hash,
            'otp_expires_at' => now()->addMinutes($minutes),
            'otp_attempts' => 0,
            'otp_channel' => $viaEmail ? 'email' : 'phone',
        ]);

        $this->dispatchDelivery($user, $otp->plain, $viaEmail);

        return $otp;
    }

    /**
     * Issue an OTP for the post-registration phone-verification flow.
     *
     * Stages the new number in `pending_phone` (the live verified `phone` is left
     * untouched until confirmed) and sends the code via SMS to THAT number.
     */
    public function issueForPhone(User $user, string $phone): OtpCode
    {
        $length = config('auth-security.otp.length', 4);
        $otp = OtpCode::generate($length);
        $minutes = config('auth-security.otp.expires_minutes', 5);

        $user->forceFill([
            'otp_code' => $otp->hash,
            'otp_expires_at' => now()->addMinutes($minutes),
            'otp_attempts' => 0,
            'otp_channel' => 'phone',
            'pending_phone' => $phone,
        ])->save();

        SendPhoneVerificationSmsJob::dispatch($user->id, $phone, $otp->plain)
            ->onQueue(config('auth-security.queues.sms'))
            ->afterCommit();

        return $otp;
    }

    /**
     * Verify an OTP for the post-registration phone flow.
     *
     * Validates the active code (expiry / match / attempts / lock) and clears the
     * OTP fields on success. It deliberately does NOT mutate phone /
     * is_phone_verified / phone_verified_at / points — those phone-confirmation
     * side effects are the caller's responsibility (PhoneVerificationController),
     * keeping this method independent of the registration-gate verify().
     */
    public function verifyPhone(User $user, string $candidate): bool
    {
        $this->ensureNotLocked($user);

        return DB::transaction(function () use ($user, $candidate) {
            /** @var User $locked */
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            if (! $locked->otp_code || ! $locked->otp_expires_at) {
                $this->recordFailedAttempt($locked);

                return false;
            }

            if (now()->greaterThan($locked->otp_expires_at)) {
                $this->recordFailedAttempt($locked);

                return false;
            }

            if (! OtpCode::verify($candidate, $locked->otp_code)) {
                $this->recordFailedAttempt($locked);

                return false;
            }

            $locked->update([
                'otp_code' => null,
                'otp_expires_at' => null,
                'otp_attempts' => 0,
            ]);

            Cache::forget($this->lockKey($locked));

            return true;
        });
    }

    public function resend(User $user): void
    {
        $viaEmail = (bool) $user->email;
        $this->issue($user, $viaEmail);
    }

    /**
     * Verify the registration OTP — CHANNEL-AWARE (Phase 5.5).
     *
     * The registration code is sent over email (when the user signed up with an
     * email, phone = null) or SMS (phone signup). The successful confirmation now
     * stamps the matching column instead of always flipping is_phone_verified:
     *   - email channel  -> email_verified_at = now()  (is_phone_verified stays false)
     *   - phone channel  -> is_phone_verified = true + phone_verified_at = now()
     *
     * This stops email registrants from being mis-flagged as "phone verified"
     * (the root bug Phase 4's backfill cleaned). The account-confirmation gate
     * accepts either channel (EnsureOtpIsVerified + the 3 redirect gates), so
     * email users still reach the dashboard. The dedicated +50 *phone* bonus now
     * lives only in the profile phone-verification flow (Phase 5).
     */
    public function verify(User $user, string $candidate): bool
    {
        $this->ensureNotLocked($user);

        return DB::transaction(function () use ($user, $candidate) {
            /** @var User $locked */
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            // Already confirmed by EITHER channel — nothing to do.
            if ($locked->is_phone_verified || $locked->email_verified_at !== null) {
                return true;
            }

            if (! $locked->otp_code || ! $locked->otp_expires_at) {
                $this->recordFailedAttempt($locked);

                return false;
            }

            if (now()->greaterThan($locked->otp_expires_at)) {
                $this->recordFailedAttempt($locked);

                return false;
            }

            if (! OtpCode::verify($candidate, $locked->otp_code)) {
                $this->recordFailedAttempt($locked);

                return false;
            }

            // Resolve the channel; fall back to inferring from the contact on
            // file for any legacy in-flight OTP issued before otp_channel existed.
            $channel = $locked->otp_channel ?? ($locked->phone ? 'phone' : 'email');

            $updates = [
                'otp_code' => null,
                'otp_expires_at' => null,
                'otp_attempts' => 0,
                'otp_channel' => null,
            ];

            if ($channel === 'phone') {
                $updates['is_phone_verified'] = true;
                $updates['phone_verified_at'] = now();
            } else {
                // email_verified_at is not in $fillable, so write via forceFill.
                $updates['email_verified_at'] = now();
            }

            $locked->forceFill($updates)->save();

            Cache::forget($this->lockKey($locked));

            return true;
        });
    }

    public function ensureNotLocked(User $user): void
    {
        $until = Cache::get($this->lockKey($user));

        if ($until && now()->lt($until)) {
            $seconds = now()->diffInSeconds($until);

            throw ValidationException::withMessages([
                'otp' => __('server.auth.otp_throttled', ['seconds' => $seconds]),
            ]);
        }
    }

    private function recordFailedAttempt(User $user): void
    {
        $attempts = (int) $user->otp_attempts + 1;
        $max = config('auth-security.otp.max_verify_attempts', 5);

        $user->update(['otp_attempts' => $attempts]);

        if ($attempts >= $max) {
            $delays = config('auth-security.otp.progressive_delays', [30, 60, 120]);
            $index = min($attempts - $max, count($delays) - 1);
            $seconds = $delays[$index] ?? end($delays);
            Cache::put($this->lockKey($user), now()->addSeconds($seconds), $seconds);
        }
    }

    private function lockKey(User $user): string
    {
        return 'otp-lock:'.$user->id;
    }

    private function dispatchDelivery(User $user, string $plain, bool $viaEmail): void
    {
        if ($viaEmail && $user->email) {
            SendOtpEmailJob::dispatch($user->id, $plain)
                ->onQueue(config('auth-security.queues.email'))
                ->afterCommit();

            return;
        }

        if ($user->phone) {
            SendOtpSmsJob::dispatch($user->id, $plain)
                ->onQueue(config('auth-security.queues.sms'))
                ->afterCommit();
        }
    }
}
