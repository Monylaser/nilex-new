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
 *   - Phone OTP verification still credits +50 ON TOP, once, and that credit is
 *     recorded as a PointTransaction visible in the user's points history.
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

    expect($user->points)->toBe(50);
});

it('adds +50 phone-verification points on top and records it in the points history', function () {
    Queue::fake();

    // Simulate a user who just registered (already holds the 50 welcome points).
    $user = User::factory()->withPendingOtp('5678')->create(['points' => 50]);

    $this->actingAs($user)
        ->post(route('otp.verify'), ['otp' => '5678'])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->points)->toBe(100); // 50 welcome + 50 verification

    // The verification credit must be a real transaction visible in history.
    $this->assertDatabaseHas('point_transactions', [
        'user_id'     => $user->id,
        'amount'      => 50,
        'description' => 'مكافأة توثيق رقم الهاتف',
    ]);
});

it('does not credit phone-verification points twice if already verified', function () {
    Queue::fake();

    $user = User::factory()->withPendingOtp('4321')->create(['points' => 50]);

    // First verification → +50.
    $this->actingAs($user)->post(route('otp.verify'), ['otp' => '4321']);
    expect($user->fresh()->points)->toBe(100);

    // A repeat verify attempt must not credit again.
    $this->actingAs($user->fresh())->post(route('otp.verify'), ['otp' => '4321']);
    expect($user->fresh()->points)->toBe(100);
});

it('advertises 50 (not 100) in the earn-guide and footer teaser', function () {
    app()->setLocale('ar');

    $user = User::factory()->create(['is_phone_verified' => true]);

    $html = $this->actingAs($user)->get(route('points.history'))->assertOk()->getContent();

    // The register reward row now reads +50; no +100 promise remains on the page.
    expect($html)->toContain('+50')->not->toContain('+100');

    // The footer teaser string itself must read 50, not 100.
    expect(__('ui.footer.gift_teaser'))->toContain('50')->not->toContain('100');
    app()->setLocale('en');
    expect(__('ui.footer.gift_teaser'))->toContain('50')->not->toContain('100');
});
