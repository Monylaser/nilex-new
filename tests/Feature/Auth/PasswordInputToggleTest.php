<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordInputToggleTest extends TestCase
{
    use RefreshDatabase;

    private function assertPasswordToggleButtons(string $html, int $expectedCount): void
    {
        $label = __('ui.auth.toggle_password');
        $this->assertSame(
            $expectedCount,
            substr_count($html, 'aria-label="'.$label.'"'),
            "Expected {$expectedCount} password toggle button(s) with aria-label."
        );
        $this->assertSame(
            $expectedCount,
            substr_count($html, 'absolute end-3'),
            "Expected {$expectedCount} toggle button(s) using logical end-* positioning."
        );
        $this->assertStringNotContainsString('togglePassword()', $html);
        $this->assertStringNotContainsString('toggleRegPassword()', $html);
    }

    public function test_register_renders_eye_toggle_on_both_password_fields(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        $this->assertPasswordToggleButtons($html, 2);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('name="password_confirmation"', $html);
        $this->assertStringContainsString('autocomplete="new-password"', $html);
    }

    public function test_login_renders_eye_toggle_on_password_field(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertPasswordToggleButtons($html, 1);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('autocomplete="current-password"', $html);
        $this->assertStringNotContainsString('padding-left:44px', $html);
    }

    public function test_reset_password_renders_eye_toggle_on_both_fields(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $html = $this->get('/reset-password/'.$notification->token)
                ->assertOk()
                ->getContent();

            $this->assertPasswordToggleButtons($html, 2);
            $this->assertStringContainsString('name="password"', $html);
            $this->assertStringContainsString('name="password_confirmation"', $html);
            $this->assertStringContainsString('autocomplete="new-password"', $html);

            return true;
        });
    }

    public function test_confirm_password_renders_eye_toggle(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)
            ->get(route('password.confirm'))
            ->assertOk()
            ->getContent();

        $this->assertPasswordToggleButtons($html, 1);
        $this->assertStringContainsString('autocomplete="current-password"', $html);
    }

    public function test_profile_renders_eye_toggle_on_all_four_password_fields(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->getContent();

        $this->assertPasswordToggleButtons($html, 4);
        $this->assertStringContainsString('name="current_password"', $html);
        $this->assertStringContainsString('autocomplete="current-password"', $html);
        $this->assertStringContainsString('autocomplete="new-password"', $html);
    }
}
