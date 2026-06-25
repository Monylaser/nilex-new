<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => 'يجب قبول حقل :attribute.',
    'accepted_if'          => 'يجب قبول حقل :attribute عندما يكون :other هو :value.',
    'active_url'           => 'يجب أن يكون حقل :attribute رابطاً صحيحاً.',
    'after'                => 'يجب أن يكون حقل :attribute تاريخاً بعد :date.',
    'after_or_equal'       => 'يجب أن يكون حقل :attribute تاريخاً بعد أو يساوي :date.',
    'alpha'                => 'يجب أن يحتوي حقل :attribute على حروف فقط.',
    'alpha_dash'           => 'يجب أن يحتوي حقل :attribute على حروف وأرقام وشرطات وشرطات سفلية فقط.',
    'alpha_num'            => 'يجب أن يحتوي حقل :attribute على حروف وأرقام فقط.',
    'array'                => 'يجب أن يكون حقل :attribute مصفوفة.',
    'ascii'                => 'يجب أن يحتوي حقل :attribute على أحرف ورموز أحادية البايت فقط.',
    'before'               => 'يجب أن يكون حقل :attribute تاريخاً قبل :date.',
    'before_or_equal'      => 'يجب أن يكون حقل :attribute تاريخاً قبل أو يساوي :date.',
    'between'              => [
        'array'   => 'يجب أن يحتوي حقل :attribute على عدد عناصر بين :min و :max.',
        'file'    => 'يجب أن يكون حجم حقل :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute بين :min و :max.',
        'string'  => 'يجب أن يكون طول حقل :attribute بين :min و :max حرفاً.',
    ],
    'boolean'              => 'يجب أن تكون قيمة حقل :attribute صحيحة أو خاطئة.',
    'can'                  => 'يحتوي حقل :attribute على قيمة غير مصرّح بها.',
    'confirmed'            => 'تأكيد حقل :attribute غير متطابق.',
    'contains'             => 'حقل :attribute يفتقد إلى قيمة مطلوبة.',
    'current_password'     => 'كلمة المرور غير صحيحة.',
    'date'                 => 'يجب أن يكون حقل :attribute تاريخاً صحيحاً.',
    'date_equals'          => 'يجب أن يكون حقل :attribute تاريخاً يساوي :date.',
    'date_format'          => 'يجب أن يطابق حقل :attribute الصيغة :format.',
    'decimal'              => 'يجب أن يحتوي حقل :attribute على :decimal منزلة عشرية.',
    'declined'             => 'يجب رفض حقل :attribute.',
    'declined_if'          => 'يجب رفض حقل :attribute عندما يكون :other هو :value.',
    'different'            => 'يجب أن يكون حقل :attribute و :other مختلفين.',
    'digits'               => 'يجب أن يتكون حقل :attribute من :digits أرقام.',
    'digits_between'       => 'يجب أن يتكون حقل :attribute من عدد أرقام بين :min و :max.',
    'dimensions'           => 'أبعاد صورة حقل :attribute غير صالحة.',
    'distinct'             => 'حقل :attribute يحتوي على قيمة مكررة.',
    'doesnt_end_with'      => 'يجب ألا ينتهي حقل :attribute بأحد القيم التالية: :values.',
    'doesnt_start_with'    => 'يجب ألا يبدأ حقل :attribute بأحد القيم التالية: :values.',
    'email'                => 'يجب أن يكون حقل :attribute بريداً إلكترونياً صحيحاً.',
    'ends_with'            => 'يجب أن ينتهي حقل :attribute بأحد القيم التالية: :values.',
    'enum'                 => 'القيمة المحددة لحقل :attribute غير صالحة.',
    'exists'               => 'القيمة المحددة لحقل :attribute غير صالحة.',
    'extensions'           => 'يجب أن يكون لحقل :attribute أحد الامتدادات التالية: :values.',
    'file'                 => 'يجب أن يكون حقل :attribute ملفاً.',
    'filled'               => 'يجب أن يحتوي حقل :attribute على قيمة.',
    'gt'                   => [
        'array'   => 'يجب أن يحتوي حقل :attribute على أكثر من :value عنصراً.',
        'file'    => 'يجب أن يكون حجم حقل :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أكبر من :value.',
        'string'  => 'يجب أن يكون طول حقل :attribute أكبر من :value حرفاً.',
    ],
    'gte'                  => [
        'array'   => 'يجب أن يحتوي حقل :attribute على :value عنصراً أو أكثر.',
        'file'    => 'يجب أن يكون حجم حقل :attribute أكبر من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أكبر من أو تساوي :value.',
        'string'  => 'يجب أن يكون طول حقل :attribute أكبر من أو يساوي :value حرفاً.',
    ],
    'hex_color'            => 'يجب أن يكون حقل :attribute لوناً سداسياً عشرياً صحيحاً.',
    'image'                => 'يجب أن يكون حقل :attribute صورة.',
    'in'                   => 'القيمة المحددة لحقل :attribute غير صالحة.',
    'in_array'             => 'يجب أن يوجد حقل :attribute ضمن :other.',
    'integer'              => 'يجب أن يكون حقل :attribute عدداً صحيحاً.',
    'ip'                   => 'يجب أن يكون حقل :attribute عنوان IP صحيحاً.',
    'ipv4'                 => 'يجب أن يكون حقل :attribute عنوان IPv4 صحيحاً.',
    'ipv6'                 => 'يجب أن يكون حقل :attribute عنوان IPv6 صحيحاً.',
    'json'                 => 'يجب أن يكون حقل :attribute نص JSON صحيحاً.',
    'list'                 => 'يجب أن يكون حقل :attribute قائمة.',
    'lowercase'            => 'يجب أن يكون حقل :attribute بأحرف صغيرة.',
    'lt'                   => [
        'array'   => 'يجب أن يحتوي حقل :attribute على أقل من :value عنصراً.',
        'file'    => 'يجب أن يكون حجم حقل :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أقل من :value.',
        'string'  => 'يجب أن يكون طول حقل :attribute أقل من :value حرفاً.',
    ],
    'lte'                  => [
        'array'   => 'يجب ألا يحتوي حقل :attribute على أكثر من :value عنصراً.',
        'file'    => 'يجب أن يكون حجم حقل :attribute أقل من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute أقل من أو تساوي :value.',
        'string'  => 'يجب أن يكون طول حقل :attribute أقل من أو يساوي :value حرفاً.',
    ],
    'mac_address'          => 'يجب أن يكون حقل :attribute عنوان MAC صحيحاً.',
    'max'                  => [
        'array'   => 'يجب ألا يحتوي حقل :attribute على أكثر من :max عنصراً.',
        'file'    => 'يجب ألا يتجاوز حجم حقل :attribute :max كيلوبايت.',
        'numeric' => 'يجب ألا تتجاوز قيمة حقل :attribute :max.',
        'string'  => 'يجب ألا يتجاوز طول حقل :attribute :max حرفاً.',
    ],
    'max_digits'           => 'يجب ألا يحتوي حقل :attribute على أكثر من :max رقماً.',
    'mimes'                => 'يجب أن يكون حقل :attribute ملفاً من نوع: :values.',
    'mimetypes'            => 'يجب أن يكون حقل :attribute ملفاً من نوع: :values.',
    'min'                  => [
        'array'   => 'يجب أن يحتوي حقل :attribute على :min عنصراً على الأقل.',
        'file'    => 'يجب أن يكون حجم حقل :attribute :min كيلوبايت على الأقل.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute :min على الأقل.',
        'string'  => 'يجب أن يكون طول حقل :attribute :min حرفاً على الأقل.',
    ],
    'min_digits'           => 'يجب أن يحتوي حقل :attribute على :min رقماً على الأقل.',
    'missing'              => 'يجب أن يكون حقل :attribute غير موجود.',
    'missing_if'           => 'يجب أن يكون حقل :attribute غير موجود عندما يكون :other هو :value.',
    'missing_unless'       => 'يجب أن يكون حقل :attribute غير موجود ما لم يكن :other هو :value.',
    'missing_with'         => 'يجب أن يكون حقل :attribute غير موجود عند وجود :values.',
    'missing_with_all'     => 'يجب أن يكون حقل :attribute غير موجود عند وجود :values.',
    'multiple_of'          => 'يجب أن تكون قيمة حقل :attribute من مضاعفات :value.',
    'not_in'               => 'القيمة المحددة لحقل :attribute غير صالحة.',
    'not_regex'            => 'صيغة حقل :attribute غير صالحة.',
    'numeric'              => 'يجب أن يكون حقل :attribute رقماً.',
    'password'             => [
        'letters'       => 'يجب أن يحتوي حقل :attribute على حرف واحد على الأقل.',
        'mixed'         => 'يجب أن يحتوي حقل :attribute على حرف كبير وحرف صغير على الأقل.',
        'numbers'       => 'يجب أن يحتوي حقل :attribute على رقم واحد على الأقل.',
        'symbols'       => 'يجب أن يحتوي حقل :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'ظهر حقل :attribute المُدخل في تسريب للبيانات. يرجى اختيار :attribute مختلف.',
    ],
    'present'              => 'يجب أن يكون حقل :attribute موجوداً.',
    'present_if'           => 'يجب أن يكون حقل :attribute موجوداً عندما يكون :other هو :value.',
    'present_unless'       => 'يجب أن يكون حقل :attribute موجوداً ما لم يكن :other هو :value.',
    'present_with'         => 'يجب أن يكون حقل :attribute موجوداً عند وجود :values.',
    'present_with_all'     => 'يجب أن يكون حقل :attribute موجوداً عند وجود :values.',
    'prohibited'           => 'حقل :attribute محظور.',
    'prohibited_if'        => 'حقل :attribute محظور عندما يكون :other هو :value.',
    'prohibited_unless'    => 'حقل :attribute محظور ما لم يكن :other ضمن :values.',
    'prohibits'            => 'حقل :attribute يمنع وجود :other.',
    'regex'                => 'صيغة حقل :attribute غير صالحة.',
    'required'             => 'حقل :attribute مطلوب.',
    'required_array_keys'  => 'يجب أن يحتوي حقل :attribute على إدخالات لـ: :values.',
    'required_if'          => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_if_accepted' => 'حقل :attribute مطلوب عند قبول :other.',
    'required_if_declined' => 'حقل :attribute مطلوب عند رفض :other.',
    'required_unless'      => 'حقل :attribute مطلوب ما لم يكن :other ضمن :values.',
    'required_with'        => 'حقل :attribute مطلوب عند وجود :values.',
    'required_with_all'    => 'حقل :attribute مطلوب عند وجود :values.',
    'required_without'     => 'حقل :attribute مطلوب عند عدم وجود :values.',
    'required_without_all' => 'حقل :attribute مطلوب عند عدم وجود أي من :values.',
    'same'                 => 'يجب أن يتطابق حقل :attribute مع :other.',
    'size'                 => [
        'array'   => 'يجب أن يحتوي حقل :attribute على :size عنصراً.',
        'file'    => 'يجب أن يكون حجم حقل :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة حقل :attribute :size.',
        'string'  => 'يجب أن يكون طول حقل :attribute :size حرفاً.',
    ],
    'starts_with'          => 'يجب أن يبدأ حقل :attribute بأحد القيم التالية: :values.',
    'string'               => 'يجب أن يكون حقل :attribute نصاً.',
    'timezone'             => 'يجب أن يكون حقل :attribute منطقة زمنية صحيحة.',
    'unique'               => 'قيمة حقل :attribute مستخدمة بالفعل.',
    'uploaded'             => 'فشل رفع حقل :attribute.',
    'uppercase'            => 'يجب أن يكون حقل :attribute بأحرف كبيرة.',
    'url'                  => 'يجب أن يكون حقل :attribute رابطاً صحيحاً.',
    'ulid'                 => 'يجب أن يكون حقل :attribute معرّف ULID صحيحاً.',
    'uuid'                 => 'يجب أن يكون حقل :attribute معرّف UUID صحيحاً.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'category_id' => [
            'required' => 'التصنيف مطلوب لبانر صفحة التصنيف.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name'                  => 'الاسم',
        'email'                 => 'البريد الإلكتروني',
        'phone'                 => 'رقم الموبايل',
        'whatsapp'              => 'رقم الواتساب',
        'password'              => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'current_password'      => 'كلمة المرور الحالية',
        'contact'               => 'البريد الإلكتروني أو رقم الموبايل',
        'identifier'            => 'البريد الإلكتروني أو رقم الموبايل',
        'otp'                   => 'كود التفعيل',
        'governorate'           => 'المحافظة',
        'city'                  => 'المدينة',
        'bio'                   => 'النبذة الشخصية',
        'avatar'                => 'الصورة الشخصية',
        'title'                 => 'العنوان',
        'price'                 => 'السعر',
        'category_id'           => 'القسم',
        'target_url'            => 'رابط الهدف',
        'ad_image'              => 'صورة البانر',
        'duration_days'         => 'مدة الحملة',
        'placement'             => 'موضع الإعلان',
    ],

];
