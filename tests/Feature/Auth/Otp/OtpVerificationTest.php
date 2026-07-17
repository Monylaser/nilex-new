<?php

namespace Tests\Feature\Auth\Otp;

use App\Auth\Jobs\SendOtpEmailJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    #[Test]
    public function it_renders_otp_verification_screen_for_unverified_users(): void
    {
        $user = User::factory()->phoneUnverified()->create();

        $this->actingAs($user)
            ->get(route('otp.notice'))
            ->assertOk();
    }

    #[Test]
    public function it_verifies_otp_and_grants_dashboard_access(): void
    {
        $user = User::factory()->withPendingOtp('5678')->create();

        $this->actingAs($user)
            ->post(route('otp.verify'), ['otp' => '5678'])
            ->assertRedirect(route('dashboard'));

        // withPendingOtp() is an EMAIL-channel OTP (factory user has an email,
        // no phone), so confirmation stamps email_verified_at — NOT
        // is_phone_verified (Phase 5.5 channel separation).
        $fresh = $user->fresh();
        $this->assertNotNull($fresh->email_verified_at);
        $this->assertFalse($fresh->is_phone_verified);
    }

    #[Test]
    public function it_blocks_protected_routes_until_otp_is_verified(): void
    {
        $user = User::factory()->phoneUnverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('otp.notice'));
    }

    #[Test]
    public function it_rate_limits_otp_resend_attempts(): void
    {
        // Cooldown would otherwise block rapid resends; isolate the HTTP throttle layer.
        config(['auth-security.otp.resend_cooldown_seconds' => 0]);
        config(['auth-security.otp.max_resends_per_window' => 100]);

        $user = User::factory()->withPendingOtp('1111')->create();

        foreach (range(1, 5) as $i) {
            $this->actingAs($user)->post(route('otp.resend'))->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('otp.resend'))
            ->assertStatus(429);
    }

    #[Test]
    public function it_dispatches_queued_email_job_on_registration(): void
    {
        $this->post('/register', [
            'name'                  => 'Nilex User',
            'contact'               => 'newuser@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('otp.notice'));

        Queue::assertPushed(SendOtpEmailJob::class);
    }

    #[Test]
    public function it_rejects_wrong_otp(): void
    {
        $user = User::factory()->create([
            'is_phone_verified' => false,
            // Must be unconfirmed on both channels, else verify() short-circuits
            // on the inherited default email_verified_at=now() (Phase 5.5).
            'email_verified_at' => null,
            'otp_code' => Hash::make('1234'),
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $this->actingAs($user)
            ->post(route('otp.verify'), ['otp' => '0000'])
            ->assertSessionHasErrors('otp');
    }
}
