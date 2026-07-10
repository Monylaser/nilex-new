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
        'otp_required' => 'يرجى إدخال كود التفعيل',
        'otp_digits' => 'الكود يجب أن يتكون من 4 أرقام',
        'verified_success' => 'تم تفعيل حسابك بنجاح! 🎉',
        'otp_invalid' => 'الكود غير صحيح أو انتهت صلاحيته.',
        'otp_resent' => 'تم إرسال كود جديد بنجاح.',
        'otp_throttled' => 'محاولات كثيرة. حاول مرة أخرى بعد :seconds ثانية.',
        'contact_invalid' => 'الرجاء إدخال بريد إلكتروني صحيح أو رقم هاتف مصري صالح (01XXXXXXXXX).',
        'email_taken' => 'هذا البريد الإلكتروني مسجل بالفعل.',
        'phone_taken' => 'رقم الهاتف هذا مسجل بالفعل.',
        'social_error' => 'حدث خطأ أثناء محاولة تسجيل الدخول عبر :provider',
        'device_limit' => 'عذراً، لقد وصلت للحد الأقصى لإنشاء الحسابات من هذا الجهاز.',
        'device_limit_detailed' => 'عذراً، لقد وصلت للحد الأقصى لإنشاء الحسابات من هذا الجهاز (3 حسابات كحد أقصى).',
        'login_success' => 'تم تسجيل الدخول بنجاح!',
        'default_social_name' => 'مستخدم نايلكس',
        'ban_reason_default' => 'مخالفة سياسات المنصة',
        'banned' => 'تم تعليق حسابك. السبب: :reason',
        'otp_gate' => 'يجب تأكيد حسابك أولاً للوصول لهذه الصفحة.',
    ],

    'ads' => [
        'duration_missing' => 'مدة الحملة غير محددة.',
        'category_only_category_page' => 'التصنيف متاح فقط لبانر صفحة التصنيف.',
        'self_service_disabled' => 'خدمة الإعلانات الذاتية غير مفعّلة حالياً.',
        'unknown_placement' => 'موضع الإعلان غير معروف: :placement',
        'no_price' => 'لا يوجد سعر مُعرّف لموضع الإعلان [:placement] ومدة [:days] أيام.',
        'not_owner' => 'هذه الحملة لا تخص حسابك.',
        'already_paid' => 'تم دفع هذه الحملة بالفعل.',
        'paymob_auth_failed' => 'فشل الاتصال ببوابة الدفع. حاول مرة أخرى.',
        'paymob_order_failed' => 'تعذّر إنشاء طلب الدفع. حاول مرة أخرى.',
        'paymob_key_failed' => 'تعذّر إتمام عملية الدفع. حاول مرة أخرى.',
    ],

    'payment' => [
        'refund_required' => 'يجب الموافقة على سياسة الاسترجاع والاسترداد قبل إتمام عملية الدفع.',
        'gateway_not_configured' => 'الدفع غير متاح مؤقتاً. يرجى المحاولة لاحقاً أو التواصل مع الدعم.',
    ],

    'dashboard' => [
        'offer_accepted' => 'تم قبول العرض بنجاح! ✅',
        'offer_rejected' => 'تم رفض العرض. ❌',
        'listing_deleted' => 'تم حذف الإعلان بنجاح 🗑️',
        'already_featured' => 'هذا الإعلان مميز بالفعل!',
        'featured_success' => 'تم خصم النقاط وتمييز الإعلان بنجاح! 🚀',
        'feature_limit_plan' => 'وصلت للحد الأقصى من الإعلانات المميزة في خطتك الحالية.',
        'feature_limit_monthly' => 'وصلت للحد الشهري لعمليات التمييز في خطتك الحالية.',
        'feature_insufficient_points' => 'نقاط غير كافية — المطلوب: :cost نقطة، المتاح: :available نقطة',
        'feature_unsupported_duration' => 'مدة التمييز غير مدعومة: :days أيام. القيم المتاحة: :values',
    ],

    'offer' => [
        'own_listing' => 'لا يمكنك تقديم عرض على إعلانك الخاص!',
        'duplicate' => 'لديك عرض قيد الانتظار بالفعل لهذا الإعلان.',
        'sent_success' => 'تم إرسال عرضك للبائع بنجاح! 🚀',
        'rate_limit_exceeded' => 'أرسلت عروضاً كثيرة. يرجى المحاولة مرة أخرى بعد :seconds ثانية.',
    ],

    'message' => [
        'self' => 'لا يمكنك إرسال رسالة لنفسك.',
    ],

    // توثيق رقم الهاتف بعد التسجيل (PhoneVerificationController)
    'phone' => [
        'required' => 'من فضلك أدخل رقم الموبايل.',
        'invalid' => 'رقم موبايل مصري غير صالح (يجب أن يبدأ بـ 01 ويتكون من 11 رقماً).',
        'already_taken' => 'رقم الهاتف هذا مسجل بالفعل لحساب آخر.',
        'no_pending' => 'لا يوجد رقم بانتظار التأكيد. أرسل كود التأكيد أولاً.',
        'otp_invalid' => 'الكود غير صحيح أو انتهت صلاحيته.',
    ],

    // توثيق البريد الإلكتروني بعد التسجيل (EmailVerificationProfileController)
    'email' => [
        'required' => 'من فضلك أدخل البريد الإلكتروني.',
        'invalid' => 'صيغة البريد الإلكتروني غير صالحة.',
        'already_taken' => 'هذا البريد الإلكتروني مسجل بالفعل لحساب آخر.',
        'no_pending' => 'لا يوجد بريد إلكتروني بانتظار التأكيد. أرسل كود التأكيد أولاً.',
        'otp_invalid' => 'الكود غير صحيح أو انتهت صلاحيته.',
    ],

    // تعديل الإعلان بعد النشر (HomeController::edit/update)
    'listing' => [
        'updated_success' => 'تم تحديث الإعلان وسيُراجَع مرة أخرى قبل ظهوره. ✏️',
        'edit_blocked_sale_pending' => 'لا يمكن تعديل هذا الإعلان لوجود عملية بيع قيد التأكيد.',
    ],

    'account' => [
        // اسم العرض البديل للحساب المُجمّد بعد الحذف.
        'deleted_name' => 'مستخدم محذوف',
        // يُعرض عند محاولة تسجيل الدخول لحساب تم حذفه/تجميده.
        'deleted_login_blocked' => 'تم حذف هذا الحساب ولا يمكن تسجيل الدخول إليه.',
    ],

    'sale_confirmation' => [
        'created' => 'تم تسجيل البيع وإغلاق الإعلان. بانتظار تأكيد المشتري.',
        'invalid_buyer' => 'المشتري المحدد غير صالح لهذا الإعلان.',
        'already_confirmed' => 'تم تأكيد بيع هذا الإعلان بالفعل.',
        'select_required' => 'من فضلك اختر المشتري أولاً.',
        'buyer_confirmed' => 'تم تأكيد عملية الشراء بنجاح. ✅',
        'confirm_failed' => 'تعذّر تأكيد عملية الشراء.',
    ],

    // إشعار طلب تأكيد الشراء (يُرسَل للمشتري — database + mail)
    'sale_confirmation_notif' => [
        'subject' => 'تأكيد عملية شراء على نايلكس',
        'greeting' => 'أهلاً :name',
        'line1' => 'أبلغ :seller أنك اشتريت ":title" عبر منصة نايلكس.',
        'line2' => 'من فضلك أكّد عملية الشراء حتى تتمكن من تقييم البائع ومساعدة باقي المشترين.',
        'action' => 'تأكيد الشراء',
        'line3' => 'شكراً لاستخدامك منصة نايلكس!',
        'db_message' => 'أكّد عملية شرائك لـ ":title" من :seller',
    ],

    // تقييم البائع (يكتبه المشتري بعد تأكيد الشراء)
    'review' => [
        'submitted' => 'شكراً! تم إرسال تقييمك بنجاح. ⭐',
        'rating_required' => 'من فضلك اختر تقييماً من 1 إلى 5 نجوم.',
        'not_allowed' => 'لا يمكنك تقييم هذه العملية.',
    ],

    'ai' => [
        'photo_max' => 'حجم الصورة لا يتجاوز 5 ميجابايت',
        'photo_image' => 'الملف يجب أن يكون صورة',
        'photo_mimes' => 'صيغ مدعومة: JPG, PNG, WEBP, GIF',
        'audio_max' => 'حجم الملف الصوتي لا يتجاوز 10 ميجابايت',
        'audio_mimes' => 'صيغ مدعومة: MP3, WAV, M4A, OGG, WEBM',
        'note_max' => 'الملاحظات لا تتجاوز 500 حرف',
        'too_many_photos' => 'لا يمكن رفع أكثر من :max صور',
        'need_input' => 'من فضلك ارفع صورة واحدة على الأقل أو سجل صوت',
        'no_api_key' => 'مفتاح Gemini API غير موجود في ملف .env',
        'no_response' => 'لم يتم استلام رد من الذكاء الاصطناعي',
        'unknown_error' => 'خطأ غير معروف',
        'server_error' => 'خطأ من الخادم: :message',
        'technical_error' => '⚠️ خطأ تقني: :message',
        'step_checking' => 'جاري التحقق من الإعدادات...',
        'step_preparing' => 'تجهيز البيانات للإرسال...',
        'step_processing_images' => 'جاري معالجة الصور...',
        'step_images_done' => 'تم معالجة الصور',
        'step_processing_audio' => 'جاري معالجة التسجيل الصوتي...',
        'step_audio_done' => 'تم معالجة التسجيل الصوتي',
        'step_connecting' => 'جاري الاتصال بالذكاء الاصطناعي...',
        'step_analyzing' => 'جاري تحليل النتائج...',
        'step_sending' => 'جاري إرسال البيانات...',
        'step_success' => 'تم بنجاح! ✅',
    ],

];
