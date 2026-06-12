<?php

/**
 * Pricing matrix compliance tests — audit-aligned feature states only.
 *
 * Validates config/pricing.php and rendered UI against
 * docs/reports/pricing_page_feature_audit.md findings.
 */

use App\Models\PointPlan;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedPricingMatrixPlans(): void
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

function matrixRowByKey(string $key): ?array
{
    return collect(config('pricing.feature_matrix'))
        ->first(fn (array $row) => ($row['key'] ?? null) === $key);
}

describe('Pricing Matrix Config Compliance', function () {

    it('marks audit-flagged analytics features as coming soon', function () {
        $comingSoonKeys = [
            'basic_ctr',
            'business_dashboard',
            'lead_funnel',
            'advanced_ctr',
            'monthly_reports',
        ];

        foreach ($comingSoonKeys as $key) {
            $row = matrixRowByKey($key);

            expect($row)->not->toBeNull("Missing matrix row: {$key}")
                ->and($row['status'] ?? null)->toBe('coming_soon');
        }
    });

    it('marks admin-only BI features as admin only', function () {
        $adminOnlyKeys = [
            'top_listings',
            'category_performance',
            'revenue_analytics',
        ];

        foreach ($adminOnlyKeys as $key) {
            $row = matrixRowByKey($key);

            expect($row)->not->toBeNull("Missing matrix row: {$key}")
                ->and($row['status'] ?? null)->toBe('admin_only');
        }
    });

    it('does not advertise coming soon features as tier-included in config', function () {
        $comingSoonKeys = [
            'basic_ctr',
            'business_dashboard',
            'lead_funnel',
            'advanced_ctr',
            'monthly_reports',
        ];

        foreach ($comingSoonKeys as $key) {
            $row = matrixRowByKey($key);

            foreach (config('pricing.plan_column_keys') as $planKey) {
                expect($row[$planKey] ?? null)->not->toBeTrue("{$key} must not be true for {$planKey}");
            }
        }
    });

    it('does not advertise admin-only features as tier-included in config', function () {
        $adminOnlyKeys = [
            'top_listings',
            'category_performance',
            'revenue_analytics',
        ];

        foreach ($adminOnlyKeys as $key) {
            $row = matrixRowByKey($key);

            foreach (config('pricing.plan_column_keys') as $planKey) {
                expect($row[$planKey] ?? null)->not->toBeTrue("{$key} must not be true for {$planKey}");
            }
        }
    });

    it('preserves seller-verified tier features as boolean availability', function () {
        $tieredKeys = [
            'credits',
            'featured_listings',
            'event_views',
            'phone_clicks',
            'whatsapp_clicks',
            'analytics_charts',
        ];

        foreach ($tieredKeys as $key) {
            $row = matrixRowByKey($key);

            expect($row)->not->toBeNull()
                ->and($row['status'] ?? null)->toBeNull()
                ->and($row)->toHaveKeys(config('pricing.plan_column_keys'));
        }
    });
});

describe('Pricing Matrix UI Compliance', function () {

    beforeEach(function () {
        seedPricingMatrixPlans();
    });

    it('renders compliance note below the matrix', function () {
        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee(__('ui.pricing.matrix_compliance_note'));
    });

    it('renders coming soon badges for unimplemented seller analytics', function () {
        $response = $this->get(route('pricing'));

        $response
            ->assertSee(__('ui.pricing.features.basic_ctr'))
            ->assertSee(__('ui.pricing.features.lead_funnel'))
            ->assertSee(__('ui.pricing.features.advanced_ctr'))
            ->assertSee(__('ui.pricing.features.business_dashboard'))
            ->assertSee(__('ui.pricing.features.monthly_reports'))
            ->assertSee(__('ui.pricing.status_coming_soon'));
    });

    it('renders admin only badges for platform BI features', function () {
        $response = $this->get(route('pricing'));

        $response
            ->assertSee(__('ui.pricing.features.top_listings'))
            ->assertSee(__('ui.pricing.features.category_performance'))
            ->assertSee(__('ui.pricing.features.revenue_analytics'))
            ->assertSee(__('ui.pricing.status_admin_only'));
    });

    it('does not render available checkmarks for coming soon feature rows', function () {
        $response = $this->get(route('pricing'));
        $html = $response->getContent();

        $comingSoonLabels = [
            __('ui.pricing.features.basic_ctr'),
            __('ui.pricing.features.lead_funnel'),
            __('ui.pricing.features.advanced_ctr'),
            __('ui.pricing.features.business_dashboard'),
            __('ui.pricing.features.monthly_reports'),
        ];

        foreach ($comingSoonLabels as $label) {
            $labelPos = strpos($html, $label);
            expect($labelPos)->not->toBeFalse("Missing label: {$label}");

            $rowSlice = substr($html, $labelPos, 1200);

            expect($rowSlice)
                ->toContain(__('ui.pricing.status_coming_soon'))
                ->not->toContain('title="' . __('ui.pricing.status_available') . '"');
        }
    });

    it('does not render available checkmarks for admin only feature rows', function () {
        $response = $this->get(route('pricing'));
        $html = $response->getContent();

        $adminOnlyLabels = [
            __('ui.pricing.features.top_listings'),
            __('ui.pricing.features.category_performance'),
            __('ui.pricing.features.revenue_analytics'),
        ];

        foreach ($adminOnlyLabels as $label) {
            $labelPos = strpos($html, $label);
            expect($labelPos)->not->toBeFalse("Missing label: {$label}");

            $rowSlice = substr($html, $labelPos, 1200);

            expect($rowSlice)
                ->toContain(__('ui.pricing.status_admin_only'))
                ->not->toContain('title="' . __('ui.pricing.status_available') . '"');
        }
    });

    it('still renders available status for tier-included features', function () {
        $this->get(route('pricing'))
            ->assertSee(__('ui.pricing.features.credits'))
            ->assertSee(__('ui.pricing.status_available'));
    });
});
