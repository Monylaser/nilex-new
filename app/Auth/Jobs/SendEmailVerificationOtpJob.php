<?php

namespace App\Auth\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Sends an OTP verification code to an explicit target email address.
 *
 * Unlike SendOtpEmailJob (which reads $user->email), this job accepts the
 * target address as a constructor argument — required for the profile
 * email-verification flow where the pending address differs from users.email.
 */
class SendEmailVerificationOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public int    $userId,
        public string $targetEmail,
        public string $otpPlain,
    ) {}

    public function handle(): void
    {
        $minutes = config('auth-security.otp.expires_minutes', 5);

        Mail::raw(
            "أهلاً بك في منصة Nilex.\n\nكود تأكيد بريدك الإلكتروني هو: {$this->otpPlain}\n\nهذا الكود صالح لمدة {$minutes} دقائق.",
            fn ($message) => $message
                ->to($this->targetEmail)
                ->subject('تأكيد البريد الإلكتروني - Nilex')
        );
    }
}
