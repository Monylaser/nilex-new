<?php

/**
 * Pricing matrix compliance tests — audit-aligned feature states only.
 *
 * Validates config/pricing.php feature_matrix against audit findings.
 * (UI matrix removed from pricing page — config tests only.)
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

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
