<?php

namespace Tests\Feature\Auth\Otp;

use App\Auth\Jobs\SendOtpEmailJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Audit #4 — OTP resend must not bypass verify lockout.
 * Cooldown / per-window cap / unified lock live in OtpService;
 * route also has throttle:5,1 as a first defense layer.
 */
class OtpResendHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    #[Test]
    public function happy_path_resend_after_cooldown_issues_new_code(): void
    {
        $user = User::factory()->withPendingOtp('1111')->create();
        $oldHash = $user->otp_code;

        $this->travel(61)->seconds();

        $this->actingAs($user)
            ->post(route('otp.resend'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $fresh = $user->fresh();
        $this->assertNotSame($oldHash, $fresh->otp_code);
        $this->assertSame(1, (int) $fresh->otp_resend_count);
        $this->assertSame(0, (int) $fresh->otp_attempts);
        Queue::assertPushed(SendOtpEmailJob::class);
    }

    #[Test]
    public function resend_before_cooldown_is_rejected_with_remaining_seconds(): void
    {
        $user = User::factory()->withPendingOtp('1111')->create();

        $this->actingAs($user)
            ->from(route('otp.notice'))
            ->post(route('otp.resend'))
            ->assertRedirect(route('otp.notice'))
            ->assertSessionHasErrors('otp');

        $message = session('errors')->get('otp')[0];
        $this->assertMatchesRegularExpression('/\d+/', $message);
        $this->assertSame(0, (int) $user->fresh()->otp_resend_count);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function resend_while_verify_locked_is_rejected_with_remaining_duration(): void
    {
        $user = User::factory()->withPendingOtp('1111')->create();

        $until = now()->addMinutes(30);
        Cache::put('otp-lock:'.$user->id, $until, 30 * 60);

        $this->travel(61)->seconds();

        $this->actingAs($user)
            ->from(route('otp.notice'))
            ->post(route('otp.resend'))
            ->assertRedirect(route('otp.notice'))
            ->assertSessionHasErrors('otp');

        $message = session('errors')->get('otp')[0];
        $this->assertMatchesRegularExpression('/\d+/', $message);
        $this->assertTrue(
            str_contains($message, 'دقيقة') || str_contains(strtolower($message), 'minute'),
            "Expected lock message with remaining minutes, got: {$message}"
        );
        $this->assertSame(0, (int) $user->fresh()->otp_resend_count);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function exceeding_resend_cap_applies_unified_thirty_minute_lock(): void
    {
        $user = User::factory()->withPendingOtp('1111')->create([
            'otp_resend_count' => 5,
        ]);

        $this->travel(61)->seconds();

        $this->actingAs($user)
            ->from(route('otp.notice'))
            ->post(route('otp.resend'))
            ->assertRedirect(route('otp.notice'))
            ->assertSessionHasErrors('otp');

        $message = session('errors')->get('otp')[0];
        $this->assertMatchesRegularExpression('/\d+/', $message);
        $this->assertTrue(
            str_contains($message, 'دقيقة') || str_contains(strtolower($message), 'minute'),
            "Expected lock message with remaining minutes, got: {$message}"
        );

        $lockUntil = Cache::get('otp-lock:'.$user->id);
        $this->assertNotNull($lockUntil);
        $this->assertTrue(now()->diffInMinutes($lockUntil) >= 29);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function route_throttle_rejects_sixth_resend_in_one_minute_when_cooldown_disabled(): void
    {
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
    public function phone_profile_reissue_to_same_pending_number_respects_cooldown(): void
    {
        $user = User::factory()->create([
            'is_phone_verified' => false,
            'email_verified_at' => now(),
            'phone' => null,
            'pending_phone' => '01012345678',
            'otp_channel' => 'phone',
            'otp_code' => Hash::make('2222'),
            'otp_expires_at' => now()->addMinutes(5),
            'otp_attempts' => 0,
            'otp_resend_count' => 0,
        ]);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.phone.send'), ['phone' => '01012345678'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('otp');
    }
}
