<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_phone_verified' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function phoneUnverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_phone_verified' => false,
            // A truly-unconfirmed account: neither channel verified. The default
            // state sets email_verified_at=now(), which (post Phase 5.5) would make
            // the account-confirmation gate treat it as confirmed — so null it here.
            'email_verified_at' => null,
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);
    }

    public function phoneVerified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_phone_verified' => true,
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);
    }

    public function withPendingOtp(string $plain = '1234'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_phone_verified' => false,
            // Unconfirmed on both channels while the OTP is pending. The default
            // user has an email and no phone, so this is an EMAIL-channel OTP
            // (confirmation will stamp email_verified_at, not is_phone_verified).
            'email_verified_at' => null,
            'otp_channel' => 'email',
            'otp_code' => \Illuminate\Support\Facades\Hash::make($plain),
            'otp_expires_at' => now()->addMinutes(5),
            'otp_attempts' => 0,
        ]);
    }
}
