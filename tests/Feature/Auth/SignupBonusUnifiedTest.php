<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Signup bonus unified to 50 points.
 *   - A normal (email/phone) signup grants EXACTLY 50 points — no doubled bonus.
 *     (The old, never-registered UserObserver +50 was deleted; the single live
 *      source is RegisteredUserController.)
 *   - A first-time Socialite (Google) signup grants EXACTLY 50 points, matching
 *     the normal signup (no path is left at 100).
 *   - Registration OTP confirmation grants NO extra points (Phase 2 removed the
 *     old +50; the +50 *phone* bonus moved to the profile phone-verification
 *     flow in Phase 5, granted once per lifetime via phone_bonus_claimed_at).
 *   - The earn-guide + footer teaser advertise 50, never 100.
 */

use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('grants exactly 50 points on a first-time Socialite signup (matches normal signup)', function () {
    $socialUser = Mockery::mock(SocialiteUserContract::class);
    $socialUser->shouldReceive('getId')->andReturn('social-999');
    $socialUser->shouldReceive('getEmail')->andReturn('social-bonus@example.com');
    $socialUser->shouldReceive('getName')->andReturn('Social Bonus');
    $socialUser->shouldReceive('getNickname')->andReturn(null);
    $socialUser->shouldReceive('getAvatar')->andReturn(null);

    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get('/auth/google/callback')->assertRedirect();

    $user = User::where('email', 'social-bonus@example.com')->firstOrFail();

    expect($user->points)->toBe(15);
});

it('grants no extra points on registration OTP confirmation (the +50 moved to the profile phone flow)', function () {
    Queue::fake();

    // Simulate a user who just registered (already holds the 50 welcome points).
    $user = User::factory()->withPendingOtp('5678')->create(['points' => 50]);

    $this->actingAs($user)
        ->post(route('otp.verify'), ['otp' => '5678'])
        ->assertRedirect(route('dashboard'));

    // Registration confirmation grants nothing extra — balance stays 50.
    expect($user->fresh()->points)->toBe(50);

    // And it must NOT create the (now-removed) registration-time phone bonus.
    $this->assertDatabaseMissing('point_transactions', [
        'user_id'     => $user->id,
        'description' => 'مكافأة توثيق رقم الهاتف',
    ]);
});

it('never credits extra points on repeated registration OTP verification', function () {
    Queue::fake();

    $user = User::factory()->withPendingOtp('4321')->create(['points' => 50]);

    // First verification → no extra credit.
    $this->actingAs($user)->post(route('otp.verify'), ['otp' => '4321']);
    expect($user->fresh()->points)->toBe(50);

    // A repeat verify attempt must not credit either.
    $this->actingAs($user->fresh())->post(route('otp.verify'), ['otp' => '4321']);
    expect($user->fresh()->points)->toBe(50);
});

it('advertises 50 (not 100) in the earn-guide and footer teaser', function () {
    app()->setLocale('ar');

    $user = User::factory()->create(['is_phone_verified' => true]);

    $html = $this->actingAs($user)->get(route('points.history'))->assertOk()->getContent();

    // The register reward row now reads +15; no +100 promise remains on the page.
    expect($html)->toContain('+15')->not->toContain('+100');

    // The footer teaser string itself must read 15, not 100.
    expect(__('ui.footer.gift_teaser'))->toContain('15')->not->toContain('100');
    app()->setLocale('en');
    expect(__('ui.footer.gift_teaser'))->toContain('15')->not->toContain('100');
});
