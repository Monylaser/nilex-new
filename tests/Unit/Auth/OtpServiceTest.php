<?php

namespace Tests\Unit\Auth;

use App\Auth\Services\OtpService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_verifies_a_valid_otp_and_clears_replay_state(): void
    {
        Queue::fake();

        $user = User::factory()->withPendingOtp('4321')->create();
        $service = app(OtpService::class);

        $this->assertTrue($service->verify($user, '4321'));

        $user->refresh();
        // withPendingOtp() is an EMAIL-channel OTP, so confirmation stamps
        // email_verified_at — not is_phone_verified (Phase 5.5).
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->is_phone_verified);
        $this->assertNull($user->otp_code);
        $this->assertNull($user->otp_expires_at);
    }

    #[Test]
    public function it_rejects_expired_otp_codes(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'is_phone_verified' => false,
            // Unconfirmed on both channels, else verify() short-circuits on the
            // inherited default email_verified_at=now() (Phase 5.5).
            'email_verified_at' => null,
            'otp_code' => Hash::make('1234'),
            'otp_expires_at' => now()->subMinute(),
        ]);

        $service = app(OtpService::class);

        $this->assertFalse($service->verify($user, '1234'));
    }
}
