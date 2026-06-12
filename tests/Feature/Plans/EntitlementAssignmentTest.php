<?php

use App\Events\PointsPurchased;
use App\Models\PlanEntitlement;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserEntitlement;
use App\Services\EntitlementService;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    seedPlanEntitlementCatalog();
});

describe('Entitlement Assignment', function () {

    it('assigns entitlements when PointsPurchased event is dispatched', function () {
        $user = User::factory()->create();
        $plan = createTierPlan(PlanEntitlement::TIER_GROWTH);

        assignPlanToUser($user, $plan);

        expect(UserEntitlement::query()->where('user_id', $user->id)->count())->toBe(13)
            ->and($user->fresh()->plan_tier)->toBe(PlanEntitlement::TIER_GROWTH);
    });

    it('executes AssignPlanEntitlementsListener on PointsPurchased', function () {
        Event::fake([PointsPurchased::class]);

        $user = User::factory()->create();
        $plan = createTierPlan(PlanEntitlement::TIER_BUSINESS);
        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount'  => $plan->price,
            'status'  => 'completed',
        ]);

        Event::assertListening(PointsPurchased::class, \App\Listeners\AssignPlanEntitlementsListener::class);

        event(new PointsPurchased($user, $plan, $transaction));

        Event::assertDispatched(PointsPurchased::class);
    });

    it('stores purchase source metadata on user entitlements', function () {
        $user = User::factory()->create();
        $plan = createTierPlan(PlanEntitlement::TIER_BUSINESS);
        $transaction = assignPlanToUser($user, $plan);

        $entitlement = UserEntitlement::query()
            ->where('user_id', $user->id)
            ->where('feature_key', EntitlementService::FEATURE_BUSINESS_BADGE)
            ->first();

        expect($entitlement)->not->toBeNull()
            ->and($entitlement->source)->toBe(UserEntitlement::SOURCE_PURCHASE)
            ->and($entitlement->source_plan_id)->toBe($plan->id)
            ->and($entitlement->source_transaction_id)->toBe($transaction->id);
    });

});
