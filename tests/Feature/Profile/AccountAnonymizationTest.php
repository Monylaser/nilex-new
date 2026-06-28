<?php

/**
 * Nilex Platform — Account deletion = Anonymization policy.
 *
 * The old hasSalesOrReviews() block was removed entirely: ALL users may now
 * delete their account. A "delete" no longer hard-deletes the row (which broke
 * restrictOnDelete on sale_confirmations/reviews and cascade-wiped listings);
 * instead the row survives with the same ID (protecting the FKs) while every
 * sensitive field is replaced with anonymized values, the user's listings are
 * soft-deleted (hidden from the public, kept for the record), and login is
 * explicitly blocked.
 *
 * Covers:
 *   - anonymization succeeds for a user who is party to a sale + review
 *   - the anonymized email is unique (deleted-{id}-{ts}@nilex.local)
 *   - sensitive fields (phone/password/provider/avatar) are wiped
 *   - normal login is impossible after anonymization
 *   - Socialite re-login does NOT re-enter the anonymized account
 *   - the user's listings disappear from the public after anonymization
 *   - an old sale_confirmation renders "Deleted User" gracefully (no break)
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\Review;
use App\Models\SaleConfirmation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function anonCategory(): Category
{
    return Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-anon-' . uniqid(),
        'is_active' => true,
    ]);
}

function anonListing(User $seller, Category $category): Listing
{
    static $counter = 0;
    $counter++;

    return Listing::create([
        'title'           => 'Anon listing ' . $counter,
        'slug'            => 'anon-listing-' . $counter . '-' . uniqid(),
        'description'     => 'desc',
        'price'           => 1000,
        'category_id'     => $category->id,
        'user_id'         => $seller->id,
        'status'          => Listing::STATUS_PUBLISHED,
        'views_count'     => 0,
        'whatsapp_clicks' => 0,
    ]);
}

it('anonymizes a user who is party to a sale and a review (old guard removed)', function () {
    $category = anonCategory();
    $seller   = User::factory()->create();
    $buyer    = User::factory()->create();
    $listing  = anonListing($seller, $category);

    $sc = SaleConfirmation::create([
        'listing_id'          => $listing->id,
        'seller_id'           => $seller->id,
        'buyer_id'            => $buyer->id,
        'status'              => SaleConfirmation::STATUS_PENDING,
        'seller_confirmed_at' => now(),
    ]);
    $sc->confirmByBuyer();
    Review::create([
        'sale_confirmation_id' => $sc->id,
        'reviewer_id'          => $buyer->id,
        'reviewee_id'          => $seller->id,
        'listing_id'           => $listing->id,
        'rating'               => 5,
    ]);

    $this->actingAs($seller)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $fresh = User::find($seller->id);
    expect($fresh)->not->toBeNull()
        ->and($fresh->isAnonymized())->toBeTrue();

    // The sale + review survive and still point at the (now anonymized) seller.
    expect(SaleConfirmation::where('seller_id', $seller->id)->exists())->toBeTrue()
        ->and(Review::where('reviewee_id', $seller->id)->exists())->toBeTrue();
});

it('wipes sensitive fields and writes a unique anonymized email', function () {
    $user = User::factory()->create([
        'phone'         => '01000000001',
        'provider_name' => 'google',
        'provider_id'   => 'g-anon-1',
        'avatar'        => null,
    ]);

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $fresh = User::find($user->id);

    expect($fresh->name)->toBe(__('server.account.deleted_name'))
        ->and($fresh->phone)->toBeNull()
        ->and($fresh->provider_name)->toBeNull()
        ->and($fresh->provider_id)->toBeNull()
        ->and($fresh->avatar)->toBeNull()
        ->and($fresh->is_phone_verified)->toBeFalse()
        ->and($fresh->email)->toContain('deleted-' . $user->id . '-')
        ->and($fresh->email)->toEndWith('@nilex.local');
});

it('produces a different anonymized email for each anonymized account', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $this->actingAs($a)->delete(route('profile.destroy'), ['password' => 'password']);
    $this->actingAs($b)->delete(route('profile.destroy'), ['password' => 'password']);

    $emailA = User::find($a->id)->email;
    $emailB = User::find($b->id)->email;

    expect($emailA)->not->toBe($emailB);
});

it('makes normal email/password login impossible after anonymization', function () {
    $user = User::factory()->create([
        'email'    => 'real-user@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password']);

    // The original email no longer exists, and the password is a random hash.
    $this->post(route('login'), [
        'email'    => 'real-user@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('blocks an already-authenticated anonymized session via middleware', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password']);

    // Even if a stale session somehow remained, the EnsureUserIsNotBanned
    // middleware logs the anonymized user out on the next authenticated request.
    $this->actingAs(User::find($user->id))
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('does not re-enter the anonymized account on a Socialite re-login', function () {
    $user = User::factory()->create([
        'email'         => 'social-anon@example.com',
        'provider_name' => 'google',
        'provider_id'   => 'g-social-anon',
    ]);

    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password']);

    // Same Google identity tries to log back in.
    $socialUser = Mockery::mock(SocialiteUserContract::class);
    $socialUser->shouldReceive('getId')->andReturn('g-social-anon');
    $socialUser->shouldReceive('getEmail')->andReturn('social-anon@example.com');
    $socialUser->shouldReceive('getName')->andReturn('Social Anon');
    $socialUser->shouldReceive('getNickname')->andReturn(null);
    $socialUser->shouldReceive('getAvatar')->andReturn(null);

    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get('/auth/google/callback');

    // The anonymized account stays anonymized (its provider id was wiped, so the
    // callback could not match it). Any session created is for a brand-new user.
    $anon = User::find($user->id);
    expect($anon->isAnonymized())->toBeTrue()
        ->and((int) auth()->id())->not->toBe($user->id);
});

it('removes the user listings from the public after anonymization', function () {
    $category = anonCategory();
    $user     = User::factory()->create();
    $listing  = anonListing($user, $category);

    // Visible before.
    $this->get(route('listings.show', $listing->id))->assertOk();

    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password']);

    // Soft-deleted: row trashed, public detail page 404s.
    expect(Listing::withTrashed()->find($listing->id)->trashed())->toBeTrue();
    $this->get(route('listings.show', $listing->id))->assertNotFound();
});

it('renders an old sale gracefully as "Deleted User" without breaking', function () {
    $category = anonCategory();
    $seller   = User::factory()->create();
    $buyer    = User::factory()->create();
    $listing  = anonListing($seller, $category);

    $sc = SaleConfirmation::create([
        'listing_id'          => $listing->id,
        'seller_id'           => $seller->id,
        'buyer_id'            => $buyer->id,
        'status'              => SaleConfirmation::STATUS_PENDING,
        'seller_confirmed_at' => now(),
    ]);
    $sc->confirmByBuyer();

    // Seller deletes (anonymizes) their account.
    $this->actingAs($seller)->delete(route('profile.destroy'), ['password' => 'password']);

    // The buyer's purchases page (which reads $sc->seller->name and the
    // soft-deleted listing via withTrashed) still renders, showing the
    // anonymized name instead of crashing on a null/removed relation.
    $this->actingAs($buyer)
        ->get(route('dashboard.purchases'))
        ->assertOk()
        ->assertSee(__('server.account.deleted_name'));
});
