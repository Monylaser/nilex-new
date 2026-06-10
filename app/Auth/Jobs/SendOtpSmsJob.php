<?php

namespace App\Auth\Jobs;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOtpSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $userId,
        public string $otpPlain,
    ) {}

    public function handle(SmsService $sms): void
    {
        $user = User::find($this->userId);

        if (! $user?->phone) {
            return;
        }

        $sms->sendOtp($user->phone, $this->otpPlain);
    }
}
