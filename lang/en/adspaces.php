<?php

return [

    // Public ad spaces / pricing page (frontend/ads/pricing.blade.php) — B.5.
    // Placement & duration labels resolve from config/ad_pricing.php
    // (label_ar/label_en, description_ar/description_en) by locale; only the
    // static page-chrome strings live here.

    'meta' => [
        'title'       => 'Ad Spaces — Advertise with us on Nilex',
        'description' => 'Showcase your ad on Nilex — hero banner, in-feed, category page, or login page. Reach thousands of daily active users in Egypt.',
    ],

    'hero' => [
        'eyebrow'   => 'Advertising opportunities',
        'title'     => 'Advertise with us on',
        'subtitle'  => 'Reach thousands of daily active users in Egypt',
        'cta_self'  => 'Advertise now',
        'cta_email' => 'Contact us to advertise',
    ],

    'spaces' => [
        'heading'  => 'Available ad spaces',
        'subtitle' => 'Choose the right place for your marketing message',
        'max'      => 'Max',
    ],

    'pricing' => [
        'heading'   => 'Ad pricing',
        'subtitle'  => 'All prices in Egyptian Pounds — by space and duration',
        'col_space' => 'Space',
    ],

    'how' => [
        'heading' => 'How does it work?',
        'self' => [
            'step1_title' => 'Create your campaign',
            'step1_desc'  => 'Pick the placement and duration, then upload your ad image',
            'step2_title' => 'Pay via Paymob',
            'step2_desc'  => 'Secure, instant payment through the Paymob gateway',
            'step3_title' => 'Approval & go live',
            'step3_desc'  => 'After our team reviews it, your ad starts showing',
        ],
        'email' => [
            'step1_title' => 'Contact us',
            'step1_desc'  => 'Send your request by email',
            'step2_title' => 'Upload your ad image',
            'step2_desc'  => 'We review the design and activate the campaign',
            'step3_title' => 'Go live instantly',
            'step3_desc'  => 'Your ad appears to users right away',
        ],
    ],

    'cta' => [
        'heading'   => 'Ready to start?',
        'desc_self' => 'Create your campaign from the dashboard and pay online',
        'desc_email'=> 'Our team is ready to help you pick the right space and launch your campaign',
        'btn_self'  => 'Advertise now',
        'btn_email' => 'Message us now',
    ],

];
