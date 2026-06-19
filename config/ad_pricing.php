<?php

/**
 * Self-service advertising pricing (placement × duration).
 *
 * All ad campaign charges MUST be calculated from this file only.
 * Do not hardcode prices elsewhere.
 */
return [

    'currency' => 'EGP',

    'durations' => [
        7  => ['label' => '7 days',  'label_ar' => '7 أيام'],
        15 => ['label' => '15 days', 'label_ar' => '15 يوم'],
        30 => ['label' => '30 days', 'label_ar' => '30 يوم'],
        60 => ['label' => '60 days', 'label_ar' => '60 يوم'],
    ],

    'placements' => [
        'hero_top' => [
            'label'         => 'Hero Top Banner',
            'label_ar'      => 'البانر الرئيسي',
            'description_ar'=> 'أعلى الصفحة الرئيسية مباشرةً، أعلى معدل مشاهدة',
            'dimensions'    => '1200×400px',
            'formats'       => 'JPG, PNG, GIF',
            'max_size'      => '2MB',
            'prices'        => [
                7  => 500.00,
                15 => 900.00,
                30 => 1500.00,
                60 => 2500.00,
            ],
        ],
        'home_feed' => [
            'label'         => 'Home Feed Banner',
            'label_ar'      => 'داخل القائمة',
            'description_ar'=> 'يظهر كل 8 إعلانات في الصفحة الرئيسية',
            'dimensions'    => '768×256px',
            'formats'       => 'JPG, PNG',
            'max_size'      => '2MB',
            'prices'        => [
                7  => 400.00,
                15 => 750.00,
                30 => 1200.00,
                60 => 2000.00,
            ],
        ],
        'category_page' => [
            'label'         => 'Category Page Banner',
            'label_ar'      => 'صفحة القسم',
            'description_ar'=> 'استهداف دقيق لجمهور قسم معين',
            'dimensions'    => '768×256px',
            'formats'       => 'JPG, PNG',
            'max_size'      => '2MB',
            'prices'        => [
                7  => 350.00,
                15 => 650.00,
                30 => 1000.00,
                60 => 1700.00,
            ],
        ],
        'login_page' => [
            'label'         => 'Login Page Banner',
            'label_ar'      => 'بانر صفحة تسجيل الدخول',
            'description_ar'=> 'يظهر لزوار صفحة تسجيل الدخول',
            'dimensions'    => '768×256px',
            'formats'       => 'JPG, PNG',
            'max_size'      => '2MB',
            'prices'        => [
                7  => 250.00,
                15 => 450.00,
                30 => 750.00,
                60 => 1200.00,
            ],
        ],
        'popup' => [
            'label'         => 'Popup Campaign',
            'label_ar'      => 'نافذة منبثقة',
            'description_ar'=> 'تظهر مرة واحدة لكل زيارة في الصفحات العامة',
            'dimensions'    => '768×512px',
            'formats'       => 'JPG, PNG',
            'max_size'      => '2MB',
            'prices'        => [
                7  => 600.00,
                15 => 1100.00,
                30 => 1800.00,
                60 => 3000.00,
            ],
        ],
        'listing_detail' => [
            'label'    => 'Listing Detail Banner',
            'label_ar' => 'بانر صفحة الإعلان',
            'prices'   => [
                7  => 300.00,
                15 => 550.00,
                30 => 900.00,
                60 => 1500.00,
            ],
        ],
        'search_results' => [
            'label'    => 'Search Results Banner',
            'label_ar' => 'بانر نتائج البحث',
            'prices'   => [
                7  => 300.00,
                15 => 550.00,
                30 => 900.00,
                60 => 1500.00,
            ],
        ],
    ],

];
