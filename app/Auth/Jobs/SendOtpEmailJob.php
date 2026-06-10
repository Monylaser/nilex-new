<?php

namespace App\Auth\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $userId,
        public string $otpPlain,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user?->email) {
            return;
        }

        $minutes = config('auth-security.otp.expires_minutes', 5);

        Mail::raw(
            "أهلاً بك في منصة Nilex. كود التفعيل الخاص بك هو: {$this->otpPlain}\n\nهذا الكود صالح لمدة {$minutes} دقائق.",
            fn ($message) => $message->to($user->email)->subject('كود التفعيل - Nilex')
        );
    }
}
