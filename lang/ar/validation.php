<?php

return [

    /*
    |--------------------------------------------------------------------------
    | رسائل التحقق
    |--------------------------------------------------------------------------
    |
    | تحتوي الأسطر التالية على رسائل الخطأ الافتراضية التي يستخدمها كلاس التحقق.
    | بعض هذه القواعد تحتوي على إصدارات متعددة مثل قواعد الحجم. لا تتردد في تعديلها.
    |
    */

    'accepted' => 'يجب قبول الحقل :attribute.',
    'accepted_if' => 'يجب قبول الحقل :attribute عندما يكون :other هو :value.',
    'active_url' => 'يجب أن يكون الحقل :attribute رابطًا صالحًا.',
    'after' => 'يجب أن يكون الحقل :attribute تاريخًا بعد :date.',
    'after_or_equal' => 'يجب أن يكون الحقل :attribute تاريخًا بعد أو يساوي :date.',
    'alpha' => 'يجب أن يحتوي الحقل :attribute على أحرف فقط.',
    'alpha_dash' => 'يجب أن يحتوي الحقل :attribute على أحرف، أرقام، شرطات، وشرطات سفلية فقط.',
    'alpha_num' => 'يجب أن يحتوي الحقل :attribute على أحرف وأرقام فقط.',
    'array' => 'يجب أن يكون الحقل :attribute مصفوفة.',
    'ascii' => 'يجب أن يحتوي الحقل :attribute على رموز وأحرف أبجدية رقمية ذات بايت واحد فقط.',
    'before' => 'يجب أن يكون الحقل :attribute تاريخًا قبل :date.',
    'before_or_equal' => 'يجب أن يكون الحقل :attribute تاريخًا قبل أو يساوي :date.',
    'between' => [
        'array' => 'يجب أن يحتوي الحقل :attribute على عدد عناصر بين :min و :max.',
        'file' => 'يجب أن يكون حجم الملف :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة الحقل :attribute بين :min و :max.',
        'string' => 'يجب أن يكون طول النص :attribute بين :min و :max حروف.',
    ],
    'boolean' => 'يجب أن يكون الحقل :attribute صحيحًا أو خطأ.',
    'can' => 'يحتوي الحقل :attribute على قيمة غير مصرح بها.',
    'confirmed' => 'تأكيد الحقل :attribute لا يتطابق.',
    'contains' => 'الحقل :attribute يفتقد قيمة مطلوبة.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => 'يجب أن يكون الحقل :attribute تاريخًا صالحًا.',
    'date_equals' => 'يجب أن يكون الحقل :attribute تاريخًا مساويًا لـ :date.',
    'date_format' => 'يجب أن يطابق الحقل :attribute التنسيق :format.',
    'decimal' => 'يجب أن يحتوي الحقل :attribute على :decimal منازل عشرية.',
    'declined' => 'يجب رفض الحقل :attribute.',
    'declined_if' => 'يجب رفض الحقل :attribute عندما يكون :other هو :value.',
    'different' => 'يجب أن يكون الحقل :attribute و :other مختلفين.',
    'digits' => 'يجب أن يحتوي الحقل :attribute على :digits أرقام.',
    'digits_between' => 'يجب أن يكون الحقل :attribute بين :min و :max أرقام.',
    'dimensions' => 'أبعاد الصورة في الحقل :attribute غير صالحة.',
    'distinct' => 'الحقل :attribute يحتوي على قيمة مكررة.',
    'doesnt_end_with' => 'يجب ألا ينتهي الحقل :attribute بأحد القيم التالية: :values.',
    'doesnt_start_with' => 'يجب ألا يبدأ الحقل :attribute بأحد القيم التالية: :values.',
    'email' => 'يجب أن يكون الحقل :attribute بريدًا إلكترونيًا صالحًا.',
    'ends_with' => 'يجب أن ينتهي الحقل :attribute بأحد القيم التالية: :values.',
    'enum' => 'القيمة المحددة للحقل :attribute غير صالحة.',
    'exists' => 'القيمة المحددة للحقل :attribute غير موجودة.',
    'extensions' => 'يجب أن يكون الحقل :attribute بامتداد من: :values.',
    'file' => 'يجب أن يكون الحقل :attribute ملفًا.',
    'filled' => 'يجب أن يحتوي الحقل :attribute على قيمة.',
    'gt' => [
        'array' => 'يجب أن يحتوي الحقل :attribute على أكثر من :value عنصر.',
        'file' => 'يجب أن يكون حجم الملف :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة الحقل :attribute أكبر من :value.',
        'string' => 'يجب أن يكون طول النص :attribute أكبر من :value حروف.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي الحقل :attribute على :value عنصر أو أكثر.',
        'file' => 'يجب أن يكون حجم الملف :attribute أكبر من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة الحقل :attribute أكبر من أو تساوي :value.',
        'string' => 'يجب أن يكون طول النص :attribute أكبر من أو يساوي :value حروف.',
    ],
    'image' => 'يجب أن يكون الحقل :attribute صورة.',
    'in' => 'القيمة المحددة للحقل :attribute غير صالحة.',
    'in_array' => 'يجب أن يكون الحقل :attribute موجودًا في :other.',
    'integer' => 'يجب أن يكون الحقل :attribute عددًا صحيحًا.',
    'ip' => 'يجب أن يكون الحقل :attribute عنوان IP صالحًا.',
    'ipv4' => 'يجب أن يكون الحقل :attribute عنوان IPv4 صالحًا.',
    'ipv6' => 'يجب أن يكون الحقل :attribute عنوان IPv6 صالحًا.',
    'json' => 'يجب أن يكون الحقل :attribute نص JSON صالح.',
    'lowercase' => 'يجب أن يكون الحقل :attribute بأحرف صغيرة.',
    'lt' => [
        'array' => 'يجب أن يحتوي الحقل :attribute على أقل من :value عنصر.',
        'file' => 'يجب أن يكون حجم الملف :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة الحقل :attribute أقل من :value.',
        'string' => 'يجب أن يكون طول النص :attribute أقل من :value حروف.',
    ],
    'lte' => [
        'array' => 'يجب ألا يحتوي الحقل :attribute على أكثر من :value عنصر.',
        'file' => 'يجب أن يكون حجم الملف :attribute أقل من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة الحقل :attribute أقل من أو تساوي :value.',
        'string' => 'يجب أن يكون طول النص :attribute أقل من أو يساوي :value حروف.',
    ],
    'mac_address' => 'يجب أن يكون الحقل :attribute عنوان MAC صالح.',
    'max' => [
        'array' => 'يجب ألا يحتوي الحقل :attribute على أكثر من :max عنصر.',
        'file' => 'يجب ألا يكون حجم الملف :attribute أكبر من :max كيلوبايت.',
        'numeric' => 'يجب ألا تكون قيمة الحقل :attribute أكبر من :max.',
        'string' => 'يجب ألا يكون طول النص :attribute أكبر من :max حروف.',
    ],
];