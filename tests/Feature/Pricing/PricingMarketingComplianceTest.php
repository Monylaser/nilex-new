<?php

/**
 * Final marketing compliance pass — pricing page seller-facing copy only.
 *
 * Ensures no unavailable analytics are advertised outside matrix
 * Coming Soon / Admin Only labels.
 */

use App\Models\PointPlan;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedMarketingCompliancePlans(): void
{
    $plans = [
        ['name_ar' => 'مبتدئ',      'name_en' => 'Starter',    'points' => 100,  'price' => 49,  'description' => 'باقة البداية'],
        ['name_ar' => 'نمو',        'name_en' => 'Growth',     'points' => 250,  'price' => 99,  'description' => 'باقة النمو'],
        ['name_ar' => 'بائع محترف', 'name_en' => 'Pro Seller', 'points' => 700,  'price' => 249, 'description' => 'باقة المحترف'],
        ['name_ar' => 'أعمال',      'name_en' => 'Business',   'points' => 1500, 'price' => 499, 'description' => 'باقة الشركات'],
    ];

    foreach ($plans as $plan) {
        PointPlan::query()->create([
            ...$plan,
            'is_active' => true,
        ]);
    }
}

describe('Pricing Marketing Compliance', function () {

    beforeEach(function () {
        seedMarketingCompliancePlans();
        app()->setLocale('en');
    });

    it('does not advertise forbidden analytics phrases as available seller features', function () {
        $html = $this->get(route('pricing'))->getContent();

        $forbiddenAsAvailable = [
            'CTR Analytics',
            'Lead Funnel Analytics',
            'Revenue Analytics',
            'Business Analytics Dashboard',
            'Advanced Analytics Dashboard',
            'Category Performance Analytics',
            'Top Listings Analytics',
            'Conversion Analytics',
            'Performance Reports available',
        ];

        foreach ($forbiddenAsAvailable as $phrase) {
            expect($html)->not->toContain($phrase, "Forbidden phrase found: {$phrase}");
        }
    });

    it('allows matrix labels only with compliance badges for restricted features', function () {
        $response = $this->get(route('pricing'));
        $html = $response->getContent();

        $restrictedLabels = [
            __('ui.pricing.features.basic_ctr'),
            __('ui.pricing.features.lead_funnel'),
            __('ui.pricing.features.revenue_analytics'),
            __('ui.pricing.features.top_listings'),
        ];

        foreach ($restrictedLabels as $label) {
            $pos = strpos($html, $label);
            expect($pos)->not->toBeFalse("Missing matrix label: {$label}");

            $slice = substr($html, $pos, 1500);

            expect($slice)->toMatch(
                '/(' . preg_quote(__('ui.pricing.status_coming_soon'), '/') . '|' . preg_quote(__('ui.pricing.status_admin_only'), '/') . ')/'
            );
        }
    });

    it('uses verified seller capabilities in business plan description', function () {
        $description = __('ui.pricing.business_description');

        expect($description)
            ->toContain('verified listing analytics')
            ->toContain('phone')
            ->toContain('WhatsApp')
            ->not->toContain('CTR monitoring')
            ->not->toContain('advanced analytics')
            ->not->toContain('lead tracking');
    });

    it('renders dashboard section with all eight verified seller features', function () {
        $this->get(route('pricing'))
            ->assertSee(__('ui.pricing.dashboard_title'))
            ->assertSee(__('ui.pricing.dashboard_listing_views'))
            ->assertSee(__('ui.pricing.dashboard_event_views'))
            ->assertSee(__('ui.pricing.dashboard_phone_clicks'))
            ->assertSee(__('ui.pricing.dashboard_whatsapp_clicks'))
            ->assertSee(__('ui.pricing.dashboard_listing_status'))
            ->assertSee(__('ui.pricing.dashboard_performance'))
            ->assertSee(__('ui.pricing.dashboard_points_history'))
            ->assertSee(__('ui.pricing.dashboard_offers'));
    });
});
