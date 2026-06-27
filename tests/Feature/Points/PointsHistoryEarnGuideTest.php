<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Points history "How to earn free points" guide.
 *
 * The "+25 positive rating" row was REMOVED because granting points for a
 * positive seller review is NOT implemented anywhere (no PointService::credit
 * tied to Review/rating). The page must never promise a reward that the user
 * does not actually receive. This test guards against the misleading row
 * coming back before the real feature is built.
 */

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('does not advertise a positive-rating reward in the earn-points guide (ar)', function () {
    app()->setLocale('ar');

    $user = User::factory()->create(['is_phone_verified' => true]);

    $this->actingAs($user)
        ->get(route('points.history'))
        ->assertOk()
        ->assertSee(__('ui.points.earn_title'))
        ->assertSee(__('ui.points.earn_register'))
        ->assertSee(__('ui.points.earn_verify'))
        ->assertSee(__('ui.points.earn_listing'))
        ->assertDontSee('تقييم إيجابي');
});

it('does not advertise a positive-rating reward in the earn-points guide (en)', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['is_phone_verified' => true]);

    $this->actingAs($user)
        ->get(route('points.history'))
        ->assertOk()
        ->assertDontSee('positive rating');
});
