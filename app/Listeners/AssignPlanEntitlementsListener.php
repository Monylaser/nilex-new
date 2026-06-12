<?php

namespace App\Listeners;

use App\Events\PointsPurchased;
use App\Services\EntitlementService;

class AssignPlanEntitlementsListener
{
    public function __construct(
        protected EntitlementService $entitlementService,
    ) {}

    public function handle(PointsPurchased $event): void
    {
        $this->entitlementService->assignFromPlan(
            $event->user,
            $event->plan,
            $event->transaction,
        );
    }
}
