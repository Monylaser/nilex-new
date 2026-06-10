<?php

namespace App\Auth\Services;

use App\Auth\Jobs\SendOtpEmailJob;
use App\Auth\Jobs\SendOtpSmsJob;
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
        ]);

        $this->dispatchDelivery($user, $otp->plain, $viaEmail);

        return $otp;
    }

    public function resend(User $user): void
    {
        $viaEmail = (bool) $user->email;
        $this->issue($user, $viaEmail);
    }

    public function verify(User $user, string $candidate): bool
    {
        $this->ensureNotLocked($user);

        return DB::transaction(function () use ($user, $candidate) {
            /** @var User $locked */
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($locked->is_phone_verified) {
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

            $locked->update([
                'is_phone_verified' => true,
                'otp_code' => null,
                'otp_expires_at' => null,
                'otp_attempts' => 0,
            ]);

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
                'otp' => "محاولات كثيرة. حاول مرة أخرى بعد {$seconds} ثانية.",
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
