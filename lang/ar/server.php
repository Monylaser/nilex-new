<?php

/*
|--------------------------------------------------------------------------
| Server response messages (Phase C.7)
|--------------------------------------------------------------------------
|
| Centralized, locale-aware server-side flash / error / validation messages
| that controllers, form requests, services, middleware and Livewire
| components return directly. Grouped by feature for auditability.
|
| NOTE: SmartAdCreator (the `ai` group) is an admin/Filament component;
| its translations only take visual effect once Phase D enables the
| SetLocale middleware on the /admin panel.
|
*/

return [

    'auth' => [
        'otp_required'        => 'يرجى إدخال كود التفعيل',
        'otp_digits'          => 'الكود يجب أن يتكون من 4 أرقام',
        'verified_success'    => 'تم تفعيل حسابك بنجاح! 🎉',
        'otp_invalid'         => 'الكود غير صحيح أو انتهت صلاحيته.',
        'otp_resent'          => 'تم إرسال كود جديد بنجاح.',
        'otp_throttled'       => 'محاولات كثيرة. حاول مرة أخرى بعد :seconds ثانية.',
        'contact_invalid'     => 'الرجاء إدخال بريد إلكتروني صحيح أو رقم هاتف مصري صالح (01XXXXXXXXX).',
        'email_taken'         => 'هذا البريد الإلكتروني مسجل بالفعل.',
        'phone_taken'         => 'رقم الهاتف هذا مسجل بالفعل.',
        'social_error'        => 'حدث خطأ أثناء محاولة تسجيل الدخول عبر :provider',
        'device_limit'        => 'عذراً، لقد وصلت للحد الأقصى لإنشاء الحسابات من هذا الجهاز.',
        'device_limit_detailed' => 'عذراً، لقد وصلت للحد الأقصى لإنشاء الحسابات من هذا الجهاز (3 حسابات كحد أقصى).',
        'login_success'       => 'تم تسجيل الدخول بنجاح!',
        'ban_reason_default'  => 'مخالفة سياسات المنصة',
        'banned'              => 'تم تعليق حسابك. السبب: :reason',
        'otp_gate'            => 'يجب تأكيد حسابك أولاً للوصول لهذه الصفحة.',
    ],

    'ads' => [
        'duration_missing'             => 'مدة الحملة غير محددة.',
        'category_only_category_page'  => 'التصنيف متاح فقط لبانر صفحة التصنيف.',
        'self_service_disabled'        => 'خدمة الإعلانات الذاتية غير مفعّلة حالياً.',
        'unknown_placement'            => 'موضع الإعلان غير معروف: :placement',
        'no_price'                     => 'لا يوجد سعر مُعرّف لموضع الإعلان [:placement] ومدة [:days] أيام.',
        'not_owner'                    => 'هذه الحملة لا تخص حسابك.',
        'already_paid'                 => 'تم دفع هذه الحملة بالفعل.',
        'paymob_auth_failed'           => 'فشل الاتصال ببوابة الدفع. حاول مرة أخرى.',
        'paymob_order_failed'          => 'تعذّر إنشاء طلب الدفع. حاول مرة أخرى.',
        'paymob_key_failed'            => 'تعذّر إتمام عملية الدفع. حاول مرة أخرى.',
    ],

    'payment' => [
        'refund_required' => 'يجب الموافقة على سياسة الاسترجاع والاسترداد قبل إتمام عملية الدفع.',
    ],

    'dashboard' => [
        'offer_accepted'   => 'تم قبول العرض بنجاح! ✅',
        'offer_rejected'   => 'تم رفض العرض. ❌',
        'listing_deleted'  => 'تم حذف الإعلان بنجاح 🗑️',
        'already_featured' => 'هذا الإعلان مميز بالفعل!',
        'featured_success' => 'تم خصم النقاط وتمييز الإعلان بنجاح! 🚀',
        'feature_limit_plan'           => 'وصلت للحد الأقصى من الإعلانات المميزة في خطتك الحالية.',
        'feature_limit_monthly'        => 'وصلت للحد الشهري لعمليات التمييز في خطتك الحالية.',
        'feature_insufficient_points'  => 'نقاط غير كافية — المطلوب: :cost نقطة، المتاح: :available نقطة',
        'feature_unsupported_duration' => 'مدة التمييز غير مدعومة: :days أيام. القيم المتاحة: :values',
    ],

    'offer' => [
        'own_listing'  => 'لا يمكنك تقديم عرض على إعلانك الخاص!',
        'duplicate'    => 'لديك عرض قيد الانتظار بالفعل لهذا الإعلان.',
        'sent_success' => 'تم إرسال عرضك للبائع بنجاح! 🚀',
    ],

    'message' => [
        'self' => 'لا يمكنك إرسال رسالة لنفسك.',
    ],

    'account' => [
        'delete_blocked' => 'لا يمكن حذف الحساب لوجود عمليات بيع أو تقييمات مرتبطة به. هذه السجلات تُحفظ بشكل دائم.',
    ],

    'ai' => [
        'photo_max'              => 'حجم الصورة لا يتجاوز 5 ميجابايت',
        'photo_image'            => 'الملف يجب أن يكون صورة',
        'photo_mimes'            => 'صيغ مدعومة: JPG, PNG, WEBP, GIF',
        'audio_max'              => 'حجم الملف الصوتي لا يتجاوز 10 ميجابايت',
        'audio_mimes'            => 'صيغ مدعومة: MP3, WAV, M4A, OGG, WEBM',
        'note_max'              => 'الملاحظات لا تتجاوز 500 حرف',
        'too_many_photos'        => 'لا يمكن رفع أكثر من :max صور',
        'need_input'             => 'من فضلك ارفع صورة واحدة على الأقل أو سجل صوت',
        'no_api_key'             => 'مفتاح Gemini API غير موجود في ملف .env',
        'no_response'            => 'لم يتم استلام رد من الذكاء الاصطناعي',
        'unknown_error'          => 'خطأ غير معروف',
        'server_error'           => 'خطأ من الخادم: :message',
        'technical_error'        => '⚠️ خطأ تقني: :message',
        'step_checking'          => 'جاري التحقق من الإعدادات...',
        'step_preparing'         => 'تجهيز البيانات للإرسال...',
        'step_processing_images' => 'جاري معالجة الصور...',
        'step_images_done'       => 'تم معالجة الصور',
        'step_processing_audio'  => 'جاري معالجة التسجيل الصوتي...',
        'step_audio_done'        => 'تم معالجة التسجيل الصوتي',
        'step_connecting'        => 'جاري الاتصال بالذكاء الاصطناعي...',
        'step_analyzing'         => 'جاري تحليل النتائج...',
        'step_sending'           => 'جاري إرسال البيانات...',
        'step_success'           => 'تم بنجاح! ✅',
    ],

];
