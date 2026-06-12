<?php

use App\Models\PlanEntitlement;
use App\Models\User;
use App\Notifications\MonthlyPerformanceReportNotification;
use App\Services\EntitlementService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    seedPlanEntitlementCatalog();
    Storage::fake('local');
    Notification::fake();
});

describe('Monthly Reports Command', function () {

    it('generates report for eligible user', function () {
        $user = User::factory()->create(['is_phone_verified' => true, 'email' => 'pro@test.com']);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_PRO_SELLER));

        Artisan::call('nilex:monthly-reports');

        $expectedPath = 'reports/' . $user->id . '/' . now()->subMonth()->format('Y-m') . '-report.pdf';
        Storage::disk('local')->assertExists($expectedPath);
    });

    it('dispatches email notification', function () {
        $user = User::factory()->create(['is_phone_verified' => true, 'email' => 'pro2@test.com']);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_PRO_SELLER));

        Artisan::call('nilex:monthly-reports');

        Notification::assertSentTo($user, MonthlyPerformanceReportNotification::class);
    });

    it('does not generate report for starter tier', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_STARTER));

        Artisan::call('nilex:monthly-reports');

        Storage::disk('local')->assertMissing('reports/' . $user->id . '/' . now()->subMonth()->format('Y-m') . '-report.pdf');
        Notification::assertNothingSent();
    });

    it('stores html report alongside pdf', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);
        assignPlanToUser($user, createTierPlan(PlanEntitlement::TIER_BUSINESS));

        Artisan::call('nilex:monthly-reports');

        $htmlPath = 'reports/' . $user->id . '/' . now()->subMonth()->format('Y-m') . '-report.html';
        Storage::disk('local')->assertExists($htmlPath);
    });
});
