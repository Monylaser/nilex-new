<?php

namespace Database\Seeders;

use App\Models\PlanEntitlement;
use App\Models\PointPlan;
use Illuminate\Database\Seeder;

class PlanEntitlementSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            PlanEntitlement::TIER_STARTER => [
                'featured_listings_limit' => ['type' => PlanEntitlement::TYPE_INTEGER, 'value' => '1'],
                'monthly_boost_limit'     => ['type' => PlanEntitlement::TYPE_INTEGER, 'value' => '2'],
                'search_priority'         => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'home_promotion'          => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'business_badge'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'priority_support'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'analytics_access'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'analytics_charts'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'phone_clicks_access'     => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'whatsapp_clicks_access'  => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'event_views_access'      => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'business_dashboard'      => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'monthly_reports'         => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
            ],
            PlanEntitlement::TIER_GROWTH => [
                'featured_listings_limit' => ['type' => PlanEntitlement::TYPE_INTEGER, 'value' => '3'],
                'monthly_boost_limit'     => ['type' => PlanEntitlement::TYPE_INTEGER, 'value' => '5'],
                'search_priority'         => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'home_promotion'          => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'business_badge'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'priority_support'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'analytics_access'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'analytics_charts'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'phone_clicks_access'     => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'whatsapp_clicks_access'  => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'event_views_access'      => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'business_dashboard'      => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'monthly_reports'         => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
            ],
            PlanEntitlement::TIER_PRO_SELLER => [
                'featured_listings_limit' => ['type' => PlanEntitlement::TYPE_INTEGER, 'value' => '5'],
                'monthly_boost_limit'     => ['type' => PlanEntitlement::TYPE_INTEGER, 'value' => '10'],
                'search_priority'         => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'home_promotion'          => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'business_badge'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'priority_support'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'analytics_access'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'analytics_charts'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'phone_clicks_access'     => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'whatsapp_clicks_access'  => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'event_views_access'      => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'business_dashboard'      => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'false'],
                'monthly_reports'         => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
            ],
            PlanEntitlement::TIER_BUSINESS => [
                'featured_listings_limit' => ['type' => PlanEntitlement::TYPE_INTEGER, 'value' => '10'],
                'monthly_boost_limit'     => ['type' => PlanEntitlement::TYPE_INTEGER, 'value' => '20'],
                'search_priority'         => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'home_promotion'          => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'business_badge'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'priority_support'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'analytics_access'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'analytics_charts'        => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'phone_clicks_access'     => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'whatsapp_clicks_access'  => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'event_views_access'      => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'business_dashboard'      => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
                'monthly_reports'         => ['type' => PlanEntitlement::TYPE_BOOLEAN, 'value' => 'true'],
            ],
        ];

        foreach ($catalog as $tier => $features) {
            foreach ($features as $featureKey => $definition) {
                PlanEntitlement::query()->updateOrCreate(
                    [
                        'plan_tier'   => $tier,
                        'feature_key' => $featureKey,
                    ],
                    [
                        'value_type'  => $definition['type'],
                        'value'       => $definition['value'],
                        'description' => null,
                    ],
                );
            }
        }

        PointPlan::query()->each(function (PointPlan $plan): void {
            $tierKey = $plan->resolveTierKey();

            if ($tierKey !== null && $plan->tier_key !== $tierKey) {
                $plan->update(['tier_key' => $tierKey]);
            }
        });
    }
}
