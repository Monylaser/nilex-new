<?php

use App\Events\PointsPurchased;
use App\Models\Category;
use App\Models\Listing;
use App\Models\PlanEntitlement;
use App\Models\PointPlan;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\PlanEntitlementSeeder;

function seedPlanEntitlementCatalog(): void
{
    (new PlanEntitlementSeeder)->run();
}

function createTierPlan(string $tier): PointPlan
{
    $definitions = [
        PlanEntitlement::TIER_STARTER => [
            'name_ar' => 'مبتدئ',
            'name_en' => 'Starter',
            'points'  => 100,
            'price'   => 49,
        ],
        PlanEntitlement::TIER_GROWTH => [
            'name_ar' => 'نمو',
            'name_en' => 'Growth',
            'points'  => 300,
            'price'   => 99,
        ],
        PlanEntitlement::TIER_PRO_SELLER => [
            'name_ar' => 'بائع محترف',
            'name_en' => 'Pro Seller',
            'points'  => 850,
            'price'   => 249,
        ],
        PlanEntitlement::TIER_BUSINESS => [
            'name_ar' => 'أعمال',
            'name_en' => 'Business',
            'points'  => 2500,
            'price'   => 499,
        ],
    ];

    $definition = $definitions[$tier];

    return PointPlan::query()->create([
        ...$definition,
        'tier_key'  => $tier,
        'is_active' => true,
    ]);
}

function assignPlanToUser(User $user, PointPlan $plan): Transaction
{
    $transaction = Transaction::query()->create([
        'user_id'      => $user->id,
        'plan_id'      => $plan->id,
        'amount'       => $plan->price,
        'status'       => 'completed',
        'is_processed' => true,
        'processed_at' => now(),
    ]);

    event(new PointsPurchased($user, $plan, $transaction));

    return $transaction;
}

function createPublishedListing(User $user, ?Category $category = null): Listing
{
    $category ??= Category::query()->create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-' . uniqid(),
        'is_active' => true,
    ]);

    return Listing::query()->create([
        'title'       => 'إعلان تجريبي ' . uniqid(),
        'slug'        => 'listing-' . uniqid(),
        'description' => 'وصف تجريبي.',
        'price'       => 1000,
        'category_id' => $category->id,
        'user_id'     => $user->id,
        'status'      => Listing::STATUS_PUBLISHED,
    ]);
}
