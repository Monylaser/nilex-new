<?php

namespace Database\Seeders;

use App\Models\PlanEntitlement;
use App\Models\PointPlan;
use Illuminate\Database\Seeder;

class PointPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name_ar'   => 'البداية',
                'name_en'   => 'Starter',
                'points'    => 100,
                'price'     => 49,
                'tier_key'  => PlanEntitlement::TIER_STARTER,
                'plan_type' => PointPlan::PLAN_TYPE_INDIVIDUAL,
                'description' => 'باقة البداية',
            ],
            [
                'name_ar'   => 'النمو',
                'name_en'   => 'Growth',
                'points'    => 300,
                'price'     => 99,
                'tier_key'  => PlanEntitlement::TIER_GROWTH,
                'plan_type' => PointPlan::PLAN_TYPE_INDIVIDUAL,
                'description' => 'باقة النمو',
            ],
            [
                'name_ar'   => 'البائع المحترف',
                'name_en'   => 'Pro Seller',
                'points'    => 850,
                'price'     => 249,
                'tier_key'  => PlanEntitlement::TIER_PRO_SELLER,
                'plan_type' => PointPlan::PLAN_TYPE_INDIVIDUAL,
                'description' => 'باقة المحترف',
            ],
            [
                'name_ar'   => 'الشركات',
                'name_en'   => 'Business',
                'points'    => 2500,
                'price'     => 499,
                'tier_key'  => PlanEntitlement::TIER_BUSINESS,
                'plan_type' => PointPlan::PLAN_TYPE_COMPANY,
                'description' => 'باقة الشركات',
            ],
        ];

        foreach ($plans as $plan) {
            PointPlan::query()->updateOrCreate(
                ['name_en' => $plan['name_en']],
                [
                    ...$plan,
                    'is_active' => true,
                ],
            );
        }
    }
}
