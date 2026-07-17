<?php

namespace App\Auth\Services;

use App\Auth\Jobs\SendEmailVerificationOtpJob;
use App\Auth\Jobs\SendOtpEmailJob;
use App\Auth\Jobs\SendOtpSmsJob;
use App\Auth\Jobs\SendPhoneVerificationSmsJob;
use App\Auth\ValueObjects\OtpCode;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function issue(User $user, bool $viaEmail): OtpCode
    {
        $otp = $this->generateCode();
        $minutes = $this->expiresMinutes();

        $user->update([
            'otp_code' => $otp->hash,
            'otp_expires_at' => now()->addMinutes($minutes),
            'otp_attempts' => 0,
            'otp_resend_count' => 0,
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
     *
     * Re-POSTing the same pending number is treated as a resend and goes through
     * the same lock / cooldown / per-window caps as registration resend.
     */
    public function issueForPhone(User $user, string $phone): OtpCode
    {
        if ($this->isActivePhoneResend($user, $phone)) {
            $this->assertAllowedToResend($user);

            return $this->writePhoneOtp($user, $phone, (int) $user->otp_resend_count + 1);
        }

        return $this->writePhoneOtp($user, $phone, 0);
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
                'otp_resend_count' => 0,
            ]);

            Cache::forget($this->lockKey($locked));

            return true;
        });
    }

    /**
     * Issue an OTP for the post-registration email-verification flow.
     *
     * Stages the new address in `pending_email` (the live `email` is left
     * untouched until confirmed) and dispatches a verification email to THAT
     * pending address — not to $user->email (which may be null for phone users).
     *
     * Re-POSTing the same pending address is treated as a resend (same gates).
     */
    public function issueForEmail(User $user, string $email): OtpCode
    {
        if ($this->isActiveEmailResend($user, $email)) {
            $this->assertAllowedToResend($user);

            return $this->writeEmailOtp($user, $email, (int) $user->otp_resend_count + 1);
        }

        return $this->writeEmailOtp($user, $email, 0);
    }

    /**
     * Verify an OTP for the post-registration email flow.
     *
     * Validates the active code and clears OTP fields on success.
     * Does NOT mutate email / email_verified_at / points — those side
     * effects are the caller's responsibility (EmailVerificationProfileController).
     */
    public function verifyEmail(User $user, string $candidate): bool
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
                'otp_resend_count' => 0,
            ]);

            Cache::forget($this->lockKey($locked));

            return true;
        });
    }

    /**
     * Registration-gate resend: new code, resets verify attempts, increments
     * otp_resend_count. Blocked while locked, during cooldown, or past the
     * per-window resend cap (then applies a unified 30-minute lock).
     */
    public function resend(User $user): void
    {
        $this->assertAllowedToResend($user);

        $viaEmail = (bool) $user->email;
        $otp = $this->generateCode();
        $minutes = $this->expiresMinutes();
        $nextCount = (int) $user->otp_resend_count + 1;

        $user->update([
            'otp_code' => $otp->hash,
            'otp_expires_at' => now()->addMinutes($minutes),
            'otp_attempts' => 0,
            'otp_resend_count' => $nextCount,
            'otp_channel' => $viaEmail ? 'email' : 'phone',
        ]);

        $this->dispatchDelivery($user, $otp->plain, $viaEmail);
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
                'otp_resend_count' => 0,
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
            throw ValidationException::withMessages([
                'otp' => $this->remainingLockMessage($until),
            ]);
        }
    }

    /**
     * Shared gates for registration resend and profile phone/email re-issue.
     */
    public function assertAllowedToResend(User $user): void
    {
        $this->ensureNotLocked($user);
        $this->assertResendCooldownElapsed($user);
        $this->assertUnderResendCap($user);
    }

    private function assertResendCooldownElapsed(User $user): void
    {
        if (! $user->otp_expires_at) {
            return;
        }

        $cooldown = (int) config('auth-security.otp.resend_cooldown_seconds', 60);
        // Last send ≈ when the current expiry window was opened (no second DB column).
        $lastSentAt = $user->otp_expires_at->copy()->subMinutes($this->expiresMinutes());
        $availableAt = $lastSentAt->copy()->addSeconds($cooldown);

        if (now()->lt($availableAt)) {
            $seconds = (int) max(1, $availableAt->getTimestamp() - now()->getTimestamp());

            throw ValidationException::withMessages([
                'otp' => __('server.auth.otp_resend_cooldown', ['seconds' => $seconds]),
            ]);
        }
    }

    private function assertUnderResendCap(User $user): void
    {
        $max = (int) config('auth-security.otp.max_resends_per_window', 5);

        if ((int) $user->otp_resend_count < $max) {
            return;
        }

        $lockMinutes = (int) config('auth-security.otp.resend_lock_minutes', 30);
        $until = now()->addMinutes($lockMinutes);
        Cache::put($this->lockKey($user), $until, $lockMinutes * 60);

        throw ValidationException::withMessages([
            'otp' => $this->remainingLockMessage($until),
        ]);
    }

    private function remainingLockMessage(CarbonInterface|string $until): string
    {
        $untilAt = $until instanceof CarbonInterface ? $until : now()->parse($until);
        $seconds = (int) max(1, $untilAt->getTimestamp() - now()->getTimestamp());

        if ($seconds >= 60) {
            $minutes = (int) max(1, (int) ceil($seconds / 60));

            return __('server.auth.otp_locked_minutes', ['minutes' => $minutes]);
        }

        return __('server.auth.otp_throttled', ['seconds' => $seconds]);
    }

    private function isActivePhoneResend(User $user, string $phone): bool
    {
        return $user->pending_phone === $phone
            && filled($user->otp_code)
            && $user->otp_channel === 'phone';
    }

    private function isActiveEmailResend(User $user, string $email): bool
    {
        return $user->pending_email === $email
            && filled($user->otp_code)
            && $user->otp_channel === 'email';
    }

    private function writePhoneOtp(User $user, string $phone, int $resendCount): OtpCode
    {
        $otp = $this->generateCode();
        $minutes = $this->expiresMinutes();

        $user->forceFill([
            'otp_code' => $otp->hash,
            'otp_expires_at' => now()->addMinutes($minutes),
            'otp_attempts' => 0,
            'otp_resend_count' => $resendCount,
            'otp_channel' => 'phone',
            'pending_phone' => $phone,
        ])->save();

        SendPhoneVerificationSmsJob::dispatch($user->id, $phone, $otp->plain)
            ->onQueue(config('auth-security.queues.sms'))
            ->afterCommit();

        return $otp;
    }

    private function writeEmailOtp(User $user, string $email, int $resendCount): OtpCode
    {
        $otp = $this->generateCode();
        $minutes = $this->expiresMinutes();

        $user->forceFill([
            'otp_code' => $otp->hash,
            'otp_expires_at' => now()->addMinutes($minutes),
            'otp_attempts' => 0,
            'otp_resend_count' => $resendCount,
            'otp_channel' => 'email',
            'pending_email' => $email,
        ])->save();

        SendEmailVerificationOtpJob::dispatch($user->id, $email, $otp->plain)
            ->onQueue(config('auth-security.queues.email'))
            ->afterCommit();

        return $otp;
    }

    private function generateCode(): OtpCode
    {
        $length = config('auth-security.otp.length', 4);

        return OtpCode::generate($length);
    }

    private function expiresMinutes(): int
    {
        return (int) config('auth-security.otp.expires_minutes', 5);
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
