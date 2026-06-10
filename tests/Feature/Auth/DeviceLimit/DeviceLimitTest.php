<?php

namespace Tests\Feature\Auth\DeviceLimit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceLimitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_blocks_registration_after_device_account_limit(): void
    {
        $deviceId = '550e8400-e29b-41d4-a716-446655440000';

        User::factory()->count(3)->create([
            'device_id' => $deviceId,
            'ip_address' => '127.0.0.1',
        ]);

        $this->withCookie('device_id', $deviceId)
            ->post('/register', [
                'name'                  => 'Fourth User',
                'contact'               => 'fourth@example.com',
                'password'              => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertSessionHasErrors('contact');

        $this->assertDatabaseMissing('users', ['email' => 'fourth@example.com']);
    }

    #[Test]
    public function it_stores_hashed_fingerprint_not_raw_user_agent_in_device_id(): void
    {
        $this->post('/register', [
            'name'                  => 'Fingerprint User',
            'contact'               => 'fpuser@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('otp.notice'));

        $user = User::where('email', 'fpuser@example.com')->first();

        $this->assertSame(64, strlen($user->fingerprint_hash));
        $this->assertStringNotContainsString('Mozilla', (string) $user->device_id);
    }
}
