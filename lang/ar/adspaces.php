<?php

return [

    // Public ad spaces / pricing page (frontend/ads/pricing.blade.php) — B.5.
    // Placement & duration labels resolve from config/ad_pricing.php
    // (label_ar/label_en, description_ar/description_en) by locale; only the
    // static page-chrome strings live here.

    'meta' => [
        'title'       => 'المساحات الإعلانية — أعلن معنا على Nilex',
        'description' => 'اعرض إعلانك على منصة نايلكس — بانر رئيسي، داخل القائمة، صفحة القسم، أو صفحة تسجيل الدخول. وصل لآلاف المستخدمين النشطين يومياً في مصر.',
    ],

    'hero' => [
        'eyebrow'   => 'فرص إعلانية',
        'title'     => 'أعلن معنا على',
        'subtitle'  => 'وصل لآلاف المستخدمين النشطين يومياً في مصر',
        'cta_self'  => 'أعلن معنا الآن',
        'cta_email' => 'تواصل معنا للإعلان',
    ],

    'spaces' => [
        'heading'  => 'مساحات الإعلان المتاحة',
        'subtitle' => 'اختر المكان المناسب لرسالتك التسويقية',
        'max'      => 'Max',
    ],

    'pricing' => [
        'heading'   => 'أسعار الإعلان',
        'subtitle'  => 'جميع الأسعار بالجنيه المصري — حسب المساحة والمدة',
        'col_space' => 'المساحة',
    ],

    'how' => [
        'heading' => 'كيف يعمل؟',
        'self' => [
            'step1_title' => 'أنشئ حملتك',
            'step1_desc'  => 'اختر الموضع والمدة وارفع صورة إعلانك',
            'step2_title' => 'ادفع عبر Paymob',
            'step2_desc'  => 'دفع آمن وفوري عبر بوابة Paymob',
            'step3_title' => 'موافقة وظهور',
            'step3_desc'  => 'بعد مراجعة الفريق يبدأ إعلانك بالظهور',
        ],
        'email' => [
            'step1_title' => 'تواصل معنا',
            'step1_desc'  => 'أرسل طلبك عبر البريد الإلكتروني',
            'step2_title' => 'ارفع صورة إعلانك',
            'step2_desc'  => 'نراجع التصميم ونفعّل الحملة',
            'step3_title' => 'ابدأ الظهور فوراً',
            'step3_desc'  => 'إعلانك يظهر للمستخدمين مباشرة',
        ],
    ],

    'cta' => [
        'heading'   => 'مستعد تبدأ؟',
        'desc_self' => 'أنشئ حملتك من لوحة التحكم وادفع إلكترونياً',
        'desc_email'=> 'فريقنا جاهز يساعدك تختار المساحة المناسبة وتطلق حملتك',
        'btn_self'  => 'أعلن معنا الآن',
        'btn_email' => 'راسلنا الآن',
    ],

];
