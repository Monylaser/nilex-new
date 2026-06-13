<?php

namespace App\Models\Concerns;

trait HasPlanType
{
    public const PLAN_TYPE_INDIVIDUAL = 'individual';

    public const PLAN_TYPE_COMPANY = 'company';

    public function hasCompanyPlan(): bool
    {
        return $this->plan_type === self::PLAN_TYPE_COMPANY;
    }

    public function hasIndividualPlan(): bool
    {
        return $this->plan_type === self::PLAN_TYPE_INDIVIDUAL;
    }

    public function isCompanyPlan(): bool
    {
        return $this->hasCompanyPlan();
    }
}
