<?php

/*
|--------------------------------------------------------------------------
| Phase 5.5 — registration OTP channel separation
|--------------------------------------------------------------------------
|
| Verifies that the registration OTP confirmation stamps the column matching
| the channel it was sent through:
|   - email channel -> email_verified_at  (is_phone_verified stays false)
|   - phone channel -> is_phone_verified + phone_verified_at
| and that the account-confirmation gate / redirects accept EITHER channel
| (so email registrants are no longer mis-flagged as "phone verified", nor
| locked out of the dashboard).
|
| Users are built with EXPLICIT verification attributes (not the factory
| defaults, which set both flags) so the assertions are unambiguous.
|
*/

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('email-channel OTP verification stamps email_verified_at, not is_phone_verified', function () {
    $user = User::factory()->create([
        'phone' => null,
        'is_phone_verified' => false,
        'email_verified_at' => null,
        'otp_channel' => 'email',
        'otp_code' => Hash::make('1234'),
        'otp_expires_at' => now()->addMinutes(5),
        'otp_attempts' => 0,
    ]);

    $this->actingAs($user)
        ->post(route('otp.verify'), ['otp' => '1234'])
        ->assertRedirect(route('dashboard'));

    $fresh = $user->fresh();
    expect($fresh->email_verified_at)->not->toBeNull();
    expect($fresh->is_phone_verified)->toBeFalse();
    expect($fresh->phone)->toBeNull();
    expect($fresh->otp_channel)->toBeNull();
});

it('phone-channel OTP verification stamps is_phone_verified + phone_verified_at', function () {
    $user = User::factory()->create([
        'phone' => '01000000001',
        'is_phone_verified' => false,
        'email_verified_at' => null,
        'otp_channel' => 'phone',
        'otp_code' => Hash::make('5678'),
        'otp_expires_at' => now()->addMinutes(5),
        'otp_attempts' => 0,
    ]);

    $this->actingAs($user)
        ->post(route('otp.verify'), ['otp' => '5678'])
        ->assertRedirect(route('dashboard'));

    $fresh = $user->fresh();
    expect($fresh->is_phone_verified)->toBeTrue();
    expect($fresh->phone_verified_at)->not->toBeNull();
    expect($fresh->email_verified_at)->toBeNull();
});

it('falls back to the phone channel when otp_channel is null but a phone exists', function () {
    $user = User::factory()->create([
        'phone' => '01000000002',
        'is_phone_verified' => false,
        'email_verified_at' => null,
        'otp_channel' => null,
        'otp_code' => Hash::make('4321'),
        'otp_expires_at' => now()->addMinutes(5),
        'otp_attempts' => 0,
    ]);

    $this->actingAs($user)
        ->post(route('otp.verify'), ['otp' => '4321'])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->is_phone_verified)->toBeTrue();
});

it('falls back to the email channel when otp_channel is null and there is no phone', function () {
    $user = User::factory()->create([
        'phone' => null,
        'is_phone_verified' => false,
        'email_verified_at' => null,
        'otp_channel' => null,
        'otp_code' => Hash::make('8765'),
        'otp_expires_at' => now()->addMinutes(5),
        'otp_attempts' => 0,
    ]);

    $this->actingAs($user)
        ->post(route('otp.verify'), ['otp' => '8765'])
        ->assertRedirect(route('dashboard'));

    $fresh = $user->fresh();
    expect($fresh->email_verified_at)->not->toBeNull();
    expect($fresh->is_phone_verified)->toBeFalse();
});

it('lets an email-confirmed user (no phone verification) through the account gate', function () {
    $user = User::factory()->create([
        'phone' => null,
        'is_phone_verified' => false,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('redirects an email-confirmed user away from the OTP notice screen', function () {
    $user = User::factory()->create([
        'phone' => null,
        'is_phone_verified' => false,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('otp.notice'))
        ->assertRedirect(route('dashboard'));
});

it('still gates a fully-unconfirmed user (no channel) to the OTP notice', function () {
    $user = User::factory()->create([
        'phone' => null,
        'is_phone_verified' => false,
        'email_verified_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('otp.notice'));
});
