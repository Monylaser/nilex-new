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
        7  => ['label' => '7 days',  'label_ar' => '7 أيام',  'label_en' => '7 days'],
        15 => ['label' => '15 days', 'label_ar' => '15 يوم',  'label_en' => '15 days'],
        30 => ['label' => '30 days', 'label_ar' => '30 يوم',  'label_en' => '30 days'],
        60 => ['label' => '60 days', 'label_ar' => '60 يوم',  'label_en' => '60 days'],
    ],

    'placements' => [
        'hero_top' => [
            'label'         => 'Hero Top Banner',
            'label_ar'      => 'البانر الرئيسي',
            'label_en'      => 'Hero Top Banner',
            'description_ar'=> 'أعلى الصفحة الرئيسية مباشرةً، أعلى معدل مشاهدة',
            'description_en'=> 'Directly atop the homepage — highest view rate',
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
            'label_en'      => 'Home Feed Banner',
            'description_ar'=> 'يظهر كل 8 إعلانات في الصفحة الرئيسية',
            'description_en'=> 'Appears every 8 listings on the homepage',
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
            'label_en'      => 'Category Page Banner',
            'description_ar'=> 'استهداف دقيق لجمهور قسم معين',
            'description_en'=> 'Precise targeting of a specific category audience',
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
            'label_en'      => 'Login Page Banner',
            'description_ar'=> 'يظهر لزوار صفحة تسجيل الدخول',
            'description_en'=> 'Shown to visitors of the login page',
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
            'label_en'      => 'Popup Campaign',
            'description_ar'=> 'تظهر مرة واحدة لكل زيارة في الصفحات العامة',
            'description_en'=> 'Shown once per visit across public pages',
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
            'label_en' => 'Listing Detail Banner',
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
            'label_en' => 'Search Results Banner',
            'prices'   => [
                7  => 300.00,
                15 => 550.00,
                30 => 900.00,
                60 => 1500.00,
            ],
        ],
    ],

];
