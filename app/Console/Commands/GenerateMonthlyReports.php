<?php

namespace App\Console\Commands;

use App\Models\PlanEntitlement;
use App\Models\User;
use App\Models\UserEntitlement;
use App\Notifications\MonthlyPerformanceReportNotification;
use App\Services\EntitlementService;
use App\Services\MonthlyReportService;
use Illuminate\Console\Command;

class GenerateMonthlyReports extends Command
{
    protected $signature = 'nilex:monthly-reports';

    protected $description = 'Generate and email monthly performance reports for pro_seller+ users';

    public function handle(MonthlyReportService $reportService, EntitlementService $entitlements): int
    {
        $eligibleTiers = [
            PlanEntitlement::TIER_PRO_SELLER,
            PlanEntitlement::TIER_BUSINESS,
        ];

        $userIds = UserEntitlement::query()
            ->where('feature_key', EntitlementService::FEATURE_MONTHLY_REPORTS)
            ->where('value_type', PlanEntitlement::TYPE_BOOLEAN)
            ->where('value', 'true')
            ->pluck('user_id')
            ->unique();

        $users = User::query()
            ->whereIn('id', $userIds)
            ->whereIn('plan_tier', $eligibleTiers)
            ->get();

        $count = 0;

        foreach ($users as $user) {
            if (! $entitlements->hasFeature($user, EntitlementService::FEATURE_MONTHLY_REPORTS)) {
                continue;
            }

            $path = $reportService->generateForUser($user);
            $periodLabel = now()->subMonth()->translatedFormat('F Y');

            $user->notify(new MonthlyPerformanceReportNotification($path, $periodLabel));

            $count++;
            $this->info("Report generated for user #{$user->id}: {$path}");
        }

        $this->info("Monthly reports complete: {$count} user(s) processed.");

        return self::SUCCESS;
    }
}
