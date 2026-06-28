<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Phone-verified badge display fix.
 *
 * The "phone verified" badge (profile page + public seller-trust-card) used to
 * render whenever is_phone_verified === true, even for users who registered by
 * EMAIL and have phone = NULL (the OTP system sets is_phone_verified for an
 * email OTP too). That showed a misleading "الهاتف موثق" badge for a user with
 * no phone at all. The badge now requires BOTH a real phone AND is_phone_verified.
 *
 * NOTE: this is a display-only fix — is_phone_verified, OtpService, the OTP gate,
 * and the +50 reward are all unchanged.
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ═══════════════════════════════════════════════════════════════════════════
// Profile page (profile/edit.blade.php) — trust-card badge
// ═══════════════════════════════════════════════════════════════════════════

it('hides the phone-verified badge on the profile page when the user has no phone', function () {
    // Same shape as the live test user: phone = NULL, is_phone_verified = true.
    $user = User::factory()->create([
        'phone'             => null,
        'is_phone_verified' => true,
    ]);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertDontSee(__('ui.profile.trust.phone_verified'))
        ->assertSee(__('ui.profile.trust.phone_unverified'));
});

it('shows the phone-verified badge on the profile page when the user has a real verified phone', function () {
    $user = User::factory()->create([
        'phone'             => '01012345678',
        'is_phone_verified' => true,
    ]);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee(__('ui.profile.trust.phone_verified'));
});

// ═══════════════════════════════════════════════════════════════════════════
// Public seller-trust-card (components/seller-trust-card.blade.php) on detail page
// ═══════════════════════════════════════════════════════════════════════════

it('hides the phone-verified badge on the seller trust card when the seller has no phone', function () {
    $category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-badge-' . uniqid(),
        'is_active' => true,
    ]);

    $seller = User::factory()->create([
        'phone'             => null,
        'is_phone_verified' => true,
    ]);

    $listing = Listing::create([
        'title'       => 'إعلان بطاقة الثقة بدون هاتف',
        'slug'        => 'trust-badge-nophone-' . uniqid(),
        'description' => 'وصف تجريبي.',
        'price'       => 10_000,
        'category_id' => $category->id,
        'user_id'     => $seller->id,
        'status'      => Listing::STATUS_PUBLISHED,
    ]);

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertDontSee(__('ui.seller_trust.phone_verified'))
        ->assertSee(__('ui.seller_trust.phone_unverified'));
});

it('shows the phone-verified badge on the seller trust card when the seller has a real verified phone', function () {
    $category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-badge-' . uniqid(),
        'is_active' => true,
    ]);

    $seller = User::factory()->create([
        'phone'             => '01087654321',
        'is_phone_verified' => true,
    ]);

    $listing = Listing::create([
        'title'       => 'إعلان بطاقة الثقة بهاتف موثق',
        'slug'        => 'trust-badge-phone-' . uniqid(),
        'description' => 'وصف تجريبي.',
        'price'       => 10_000,
        'category_id' => $category->id,
        'user_id'     => $seller->id,
        'status'      => Listing::STATUS_PUBLISHED,
    ]);

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee(__('ui.seller_trust.phone_verified'));
});
