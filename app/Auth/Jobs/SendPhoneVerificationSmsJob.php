<?php

namespace App\Auth\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sends an OTP via SMS to an EXPLICIT phone number (the user's pending_phone),
 * not the user's currently stored phone. Used by the post-registration phone
 * verification flow (OtpService::issueForPhone), where the number being verified
 * is not yet committed to users.phone.
 */
class SendPhoneVerificationSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $userId,
        public string $phone,
        public string $otpPlain,
    ) {}

    public function handle(SmsService $sms): void
    {
        $sms->sendOtp($this->phone, $this->otpPlain);
    }
}
