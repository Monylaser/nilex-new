<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Pricing page UI + feature matrix (additive UI only).
 */

use App\Models\PointPlan;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedPricingPlans(): void
{
    $plans = [
        ['name_ar' => 'مبتدئ',     'name_en' => 'Starter',    'points' => 100,  'price' => 49,  'description' => 'باقة البداية'],
        ['name_ar' => 'نمو',       'name_en' => 'Growth',     'points' => 250,  'price' => 99,  'description' => 'باقة النمو'],
        ['name_ar' => 'بائع محترف','name_en' => 'Pro Seller', 'points' => 700,  'price' => 249, 'description' => 'باقة المحترف'],
        ['name_ar' => 'أعمال',     'name_en' => 'Business',   'points' => 1500, 'price' => 499, 'description' => 'باقة الشركات'],
    ];

    foreach ($plans as $plan) {
        PointPlan::query()->create([
            ...$plan,
            'is_active' => true,
        ]);
    }
}

describe('Pricing Page UI', function () {

    it('loads successfully', function () {
        seedPricingPlans();

        $this->get(route('pricing'))->assertOk();
    });

    it('renders all active plans with credits and prices', function () {
        seedPricingPlans();

        $response = $this->get(route('pricing'));

        $response
            ->assertSee('Starter')
            ->assertSee('Growth')
            ->assertSee('Pro Seller')
            ->assertSee('Business')
            ->assertSee('100')
            ->assertSee('250')
            ->assertSee('700')
            ->assertSee('1,500')
            ->assertSee('49')
            ->assertSee('99')
            ->assertSee('249')
            ->assertSee('499');
    });

    it('renders the feature comparison matrix', function () {
        seedPricingPlans();

        $response = $this->get(route('pricing'));

        $response
            ->assertSee('id="feature-matrix"', false)
            ->assertSee(__('ui.pricing.matrix_title'))
            ->assertSee(__('ui.pricing.features.credits'))
            ->assertSee(__('ui.pricing.features.lead_funnel'))
            ->assertSee(__('ui.pricing.features.business_badge'));
    });

    it('renders the Growth most popular badge on plan card and matrix', function () {
        seedPricingPlans();

        $response = $this->get(route('pricing'));

        $response->assertSee(__('ui.pricing.most_popular'));
    });

    it('shows registration welcome gift when configured', function () {
        seedPricingPlans();

        $this->get(route('pricing'))
            ->assertSee(__('ui.pricing.welcome_gift', ['points' => number_format(100)]));
    });

    it('renders analytics section with seller-verified capabilities only', function () {
        seedPricingPlans();

        $response = $this->get(route('pricing'));

        $response
            ->assertSee(__('ui.pricing.analytics_title'))
            ->assertSee(__('ui.pricing.analytics_views'))
            ->assertSee(__('ui.pricing.analytics_phone'))
            ->assertSee(__('ui.pricing.analytics_whatsapp'))
            ->assertDontSee('Lead Funnel Analytics');
    });

    it('renders business value funnel section', function () {
        seedPricingPlans();

        $this->get(route('pricing'))
            ->assertSee(__('ui.pricing.value_step_visibility'))
            ->assertSee(__('ui.pricing.value_step_sales'));
    });

    it('uses business positioning copy without changing database description field', function () {
        seedPricingPlans();

        $business = PointPlan::query()->where('name_en', 'Business')->firstOrFail();
        expect($business->description)->toBe('باقة الشركات');

        $this->get(route('pricing'))
            ->assertSee(__('ui.pricing.business_description'));
    });

    it('preserves checkout functionality for authenticated users', function () {
        seedPricingPlans();

        $user = User::factory()->create(['is_phone_verified' => true]);
        $plan = PointPlan::query()->where('name_en', 'Growth')->firstOrFail();

        $this->actingAs($user)
            ->get(route('pricing'))
            ->assertOk()
            ->assertSee('checkout-' . $plan->id, false)
            ->assertSee(route('payment.checkout'), false);
    });

    it('preserves register CTA for guests', function () {
        seedPricingPlans();

        $this->get(route('pricing'))
            ->assertSee(route('register'), false)
            ->assertSee(__('ui.pricing.cta_register'));
    });

    it('preserves how-it-works and trust strip sections', function () {
        seedPricingPlans();

        $this->get(route('pricing'))
            ->assertSee('كيف تكسب النقاط ببطء؟')
            ->assertSee('كيف تستثمر النقاط لسرعة البيع؟')
            ->assertSee(__('ui.pricing.trust_first_feature'))
            ->assertSee(__('ui.pricing.trust_points_validity'));
    });

    it('renders seller dashboard capabilities section with verified features only', function () {
        seedPricingPlans();

        $response = $this->get(route('pricing'));

        $response
            ->assertSee('id="seller-dashboard"', false)
            ->assertSee(__('ui.pricing.dashboard_title'))
            ->assertSee(__('ui.pricing.dashboard_listing_views'))
            ->assertSee(__('ui.pricing.dashboard_event_views'))
            ->assertSee(__('ui.pricing.dashboard_phone_clicks'))
            ->assertSee(__('ui.pricing.dashboard_whatsapp_clicks'))
            ->assertSee(__('ui.pricing.dashboard_points_history'))
            ->assertSee(__('ui.pricing.dashboard_offers'))
            ->assertDontSee('CTR Analytics')
            ->assertDontSee('Lead Funnel')
            ->assertDontSee('Revenue Analytics')
            ->assertDontSee('Business Analytics Dashboard');
    });

    it('does not advertise unavailable analytics in business plan copy', function () {
        seedPricingPlans();

        $description = __('ui.pricing.business_description');

        expect($description)
            ->not->toContain('CTR monitoring')
            ->not->toContain('advanced analytics')
            ->not->toContain('lead tracking');

        $this->get(route('pricing'))
            ->assertSee($description)
            ->assertDontSee('CTR monitoring')
            ->assertDontSee('advanced analytics, lead tracking');
    });

    it('shows empty state when no active plans exist', function () {
        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee(__('ui.pricing.empty_title'));
    });
});
