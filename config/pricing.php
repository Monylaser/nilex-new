<?php

/**
 * Pricing page feature matrix (UI/marketing positioning only).
 *
 * Cell values: true/false for tier availability, or row-level status:
 *   coming_soon — feature not yet available to sellers
 *   admin_only  — platform admin tooling only (no seller path)
 *
 * See docs/reports/pricing_page_feature_audit.md for compliance rules.
 */
return [

    'registration_welcome_points' => 20,

    /** Points granted when a seller publishes a new listing (HomeController::store). */
    'listing_creation_points' => 3,

    /** One-time profile phone or email verification bonus (PhoneVerificationController / EmailVerificationProfileController). */
    'profile_verification_bonus_points' => 20,

    'plan_column_keys' => [
        'starter',
        'growth',
        'pro_seller',
        'business',
    ],

    'feature_matrix' => [
        [
            'key' => 'credits',
            'starter' => true,
            'growth' => true,
            'pro_seller' => true,
            'business' => true,
        ],
        [
            'key' => 'featured_listings',
            'starter' => true,
            'growth' => true,
            'pro_seller' => true,
            'business' => true,
        ],
        [
            'key' => 'home_promotion',
            'starter' => true,
            'growth' => true,
            'pro_seller' => true,
            'business' => true,
        ],
        [
            'key' => 'search_priority',
            'starter' => false,
            'growth' => true,
            'pro_seller' => true,
            'business' => true,
        ],
        [
            'key' => 'event_views',
            'starter' => false,
            'growth' => true,
            'pro_seller' => true,
            'business' => true,
        ],
        [
            'key' => 'phone_clicks',
            'starter' => false,
            'growth' => true,
            'pro_seller' => true,
            'business' => true,
        ],
        [
            'key' => 'whatsapp_clicks',
            'starter' => false,
            'growth' => true,
            'pro_seller' => true,
            'business' => true,
        ],
        [
            'key' => 'basic_ctr',
            'status' => 'coming_soon',
        ],
        [
            'key' => 'analytics_charts',
            'starter' => false,
            'growth' => false,
            'pro_seller' => true,
            'business' => true,
        ],
        [
            'key' => 'top_listings',
            'status' => 'admin_only',
        ],
        [
            'key' => 'category_performance',
            'status' => 'admin_only',
        ],
        [
            'key' => 'revenue_analytics',
            'status' => 'admin_only',
        ],
        [
            'key' => 'business_dashboard',
            'status' => 'coming_soon',
        ],
        [
            'key' => 'lead_funnel',
            'status' => 'coming_soon',
        ],
        [
            'key' => 'advanced_ctr',
            'status' => 'coming_soon',
        ],
        [
            'key' => 'monthly_reports',
            'status' => 'coming_soon',
        ],
        [
            'key' => 'priority_support',
            'starter' => false,
            'growth' => false,
            'pro_seller' => false,
            'business' => true,
        ],
        [
            'key' => 'business_badge',
            'starter' => false,
            'growth' => false,
            'pro_seller' => false,
            'business' => true,
        ],
    ],

];
