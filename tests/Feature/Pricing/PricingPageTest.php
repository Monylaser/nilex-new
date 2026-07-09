<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Pricing page UI (plan cards + marketing sections).
 */

use App\Models\CampaignLink;
use App\Models\Listing;
use App\Models\PointPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedPricingPlans(): void
{
    $plans = [
        ['name_ar' => 'مبتدئ',     'name_en' => 'Starter',    'points' => 100,  'price' => 49,  'description' => 'باقة البداية'],
        ['name_ar' => 'نمو',       'name_en' => 'Growth',     'points' => 300,  'price' => 99,  'description' => 'باقة النمو'],
        ['name_ar' => 'بائع محترف', 'name_en' => 'Pro Seller', 'points' => 850,  'price' => 249, 'description' => 'باقة المحترف'],
        ['name_ar' => 'أعمال',     'name_en' => 'Business',   'points' => 2500, 'price' => 499, 'description' => 'باقة الشركات'],
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
            ->assertSee('300')
            ->assertSee('850')
            ->assertSee('2,500')
            ->assertSee('49')
            ->assertSee('99')
            ->assertSee('249')
            ->assertSee('499');
    });

    it('renders the Growth most popular badge on plan card', function () {
        seedPricingPlans();

        $response = $this->get(route('pricing'));

        $response->assertSee(__('ui.pricing.most_popular'));
    });

    it('shows registration welcome gift when configured', function () {
        seedPricingPlans();

        $this->get(route('pricing'))
            ->assertSee(__('ui.pricing.welcome_gift', ['points' => number_format(config('pricing.registration_welcome_points'))]));
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

    it('renders plan card feature bullets without changing database description field', function () {
        seedPricingPlans();

        $business = PointPlan::query()->where('name_en', 'Business')->firstOrFail();
        expect($business->description)->toBe('باقة الشركات');

        $this->get(route('pricing'))
            ->assertSee(__('ui.pricing.plan_cards.starter.concurrent_featured'))
            ->assertSee(__('ui.pricing.plan_cards.growth.search_priority'))
            ->assertSee(__('ui.pricing.plan_cards.pro_seller.analytics_charts'))
            ->assertSee(__('ui.pricing.plan_cards.business.business_badge'));
    });

    it('preserves checkout functionality for authenticated users', function () {
        seedPricingPlans();

        $user = User::factory()->create(['is_phone_verified' => true]);
        $plan = PointPlan::query()->where('name_en', 'Growth')->firstOrFail();

        $this->actingAs($user)
            ->get(route('pricing'))
            ->assertOk()
            ->assertSee('checkout-'.$plan->id, false)
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
            ->assertSee(__('ui.pricing.earn.title'))
            ->assertSee(__('ui.pricing.spend.title'))
            ->assertSee(__('ui.pricing.earn.verify_email'))
            ->assertDontSee('تسجيل يومي')
            ->assertDontSee('Daily login')
            ->assertSee(__('ui.pricing.trust_first_feature'))
            ->assertSee(__('ui.pricing.trust_points_validity'));
    });

    it('section 6 earn guide uses config-backed point values', function () {
        seedPricingPlans();

        $welcome = (int) config('pricing.registration_welcome_points');
        $verify = (int) config('pricing.profile_verification_bonus_points');
        $listing = (int) config('pricing.listing_creation_points');

        $this->get(route('pricing'))
            ->assertSee(__('ui.pricing.earn.points_positive', ['points' => number_format($welcome)]))
            ->assertSee(__('ui.pricing.earn.points_positive', ['points' => number_format($verify)]))
            ->assertSee(__('ui.pricing.earn.points_positive', ['points' => number_format($listing)]));
    });

    it('section 6 spend guide uses Listing::FEATURE_COSTS dynamically', function () {
        seedPricingPlans();

        foreach (Listing::FEATURE_COSTS as $days => $cost) {
            $label = $days === 1
                ? __('ui.pricing.spend.feature_listing_one')
                : __('ui.pricing.spend.feature_listing', ['days' => $days]);

            $this->get(route('pricing'))
                ->assertSee($label)
                ->assertSee(__('ui.pricing.spend.points_cost', ['points' => number_format($cost)]));
        }
    });

    it('section 6 shows referral reward from active campaign links', function () {
        seedPricingPlans();

        CampaignLink::query()->create([
            'code' => 'REF25',
            'points_reward' => 25,
            'is_active' => true,
        ]);

        $this->get(route('pricing'))
            ->assertSee(__('ui.pricing.earn.referral'))
            ->assertSee(__('ui.pricing.earn.points_positive', ['points' => number_format(25)]));
    });

    it('section 6 omits referral row when no active campaign links exist', function () {
        seedPricingPlans();

        $this->get(route('pricing'))
            ->assertDontSee(__('ui.pricing.earn.referral'));
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

    it('does not advertise unavailable analytics in plan card copy', function () {
        seedPricingPlans();

        $businessFeatures = __('ui.pricing.plan_cards.business');

        foreach ($businessFeatures as $line) {
            expect($line)
                ->not->toContain('CTR monitoring')
                ->not->toContain('advanced analytics')
                ->not->toContain('lead tracking');
        }

        $this->get(route('pricing'))
            ->assertSee(__('ui.pricing.plan_cards.business.business_badge'))
            ->assertDontSee('CTR monitoring')
            ->assertDontSee('advanced analytics, lead tracking');
    });

    it('shows empty state when no active plans exist', function () {
        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee(__('ui.pricing.empty_title'));
    });
});
