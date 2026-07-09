<?php

/**
 * Point balance mutations must stay consistent under lockForUpdate + transactions.
 */

use App\Exceptions\InsufficientPointsException;
use App\Models\CampaignLink;
use App\Models\Category;
use App\Models\Listing;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->pointService = app(PointService::class);
});

it('deduct rejects a second debit when balance is insufficient under row lock', function () {
    $user = User::factory()->create(['points' => 50, 'points_balance' => 50]);

    $this->pointService->deduct($user, 40, 'first debit');

    expect(fn () => $this->pointService->deduct($user, 40, 'second debit'))
        ->toThrow(InsufficientPointsException::class);

    $fresh = $user->fresh();
    expect($fresh->points)->toBe(10)
        ->and($fresh->points_balance)->toBe(10);
});

it('transfer keeps both users points and points_balance in sync', function () {
    $from = User::factory()->create(['points' => 100, 'points_balance' => 100]);
    $to = User::factory()->create(['points' => 10, 'points_balance' => 10]);

    $this->pointService->transfer($from, $to, 30, 'peer transfer');

    expect($from->fresh()->points)->toBe(70)
        ->and($from->fresh()->points_balance)->toBe(70)
        ->and($to->fresh()->points)->toBe(40)
        ->and($to->fresh()->points_balance)->toBe(40);
});

it('referral signup credits spendable points via PointService', function () {
    Queue::fake();

    CampaignLink::create([
        'code' => 'REF-TEST',
        'points_reward' => 25,
        'usage_limit' => 10,
        'used_count' => 0,
        'is_active' => true,
    ]);

    session(['campaign_code' => 'REF-TEST']);

    $this->post('/register', [
        'name' => 'Referral User',
        'contact' => 'referral@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertRedirect(route('otp.notice'));

    $user = User::where('email', 'referral@example.com')->firstOrFail();

    // 20 welcome + 25 referral
    expect($user->points)->toBe(45)
        ->and($user->points_balance)->toBe(45);

    expect(PointTransaction::query()
        ->where('user_id', $user->id)
        ->where('description', 'like', '%REF-TEST%')
        ->exists())->toBeTrue();
});

it('featureWithPoints deducts via PointService and syncs points_balance', function () {
    Category::create([
        'name_ar' => 'إلكترونيات',
        'name_en' => 'Electronics',
        'slug' => 'electronics',
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'points' => 300,
        'points_balance' => 300,
        'is_phone_verified' => true,
    ]);

    $listing = Listing::create([
        'title' => 'Race-safe feature test',
        'slug' => 'race-safe-feature-test',
        'description' => 'وصف.',
        'price' => 1_000,
        'category_id' => 1,
        'user_id' => $user->id,
        'status' => Listing::STATUS_PUBLISHED,
    ]);

    $listing->featureWithPoints(3);

    $fresh = $user->fresh();
    expect($fresh->points)->toBe(210)
        ->and($fresh->points_balance)->toBe(210)
        ->and($listing->fresh()->is_featured)->toBeTrue();
});
