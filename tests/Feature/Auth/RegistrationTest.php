<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    #[Test]
    public function new_users_register_with_email_and_redirect_to_otp(): void
    {
        Queue::fake();

        $response = $this->post('/register', [
            'name'                  => 'Test User',
            'contact'               => 'test@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('otp.notice'));

        $this->assertFalse(User::where('email', 'test@example.com')->first()->is_phone_verified);
    }
}
