<?php

namespace App\Filament\Admin\Resources\Listings\Schemas\Fields;

use App\Models\PhoneBrand;
use App\Models\PhoneModel;
use Filament\Forms\Components\Select    as FormSelect;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Schemas\Components\Section as FormSection;

class PhoneFields
{
    // ID قسم إلكترونيات وأجهزة = 20
    public static function make(): FormSection
    {
        return FormSection::make('مواصفات الموبايل')
            ->columns(2)
            ->visible(fn ($get) => (int) $get('category_id') === 20)
            ->schema([
                FormSelect::make('custom_fields_values.brand_id')
                    ->label('الماركة')
                    ->options(fn () => PhoneBrand::active()
                        ->orderBy('sort_order')
                        ->pluck('name_ar', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('custom_fields_values.model_id', null)),

                FormSelect::make('custom_fields_values.model_id')
                    ->label('الموديل')
                    ->options(function ($get) {
                        $brandId = $get('custom_fields_values.brand_id');
                        if (blank($brandId)) return [];

                        return PhoneModel::where('phone_brand_id', $brandId)
                            ->active()
                            ->orderBy('sort_order')
                            ->pluck('name_ar', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->disabled(fn ($get) => blank($get('custom_fields_values.brand_id')))
                    ->helperText('اختر الماركة أولاً'),

                FormSelect::make('custom_fields_values.condition')
                    ->label('الحالة')
                    ->options([
                        'new'         => 'جديد',
                        'used_good'   => 'مستعمل - ممتاز',
                        'used_fair'   => 'مستعمل - جيد',
                        'used_poor'   => 'مستعمل - مقبول',
                        'for_parts'   => 'للقطع',
                    ])
                    ->required(),

                FormSelect::make('custom_fields_values.storage')
                    ->label('سعة التخزين')
                    ->options([
                        '16GB'  => '16 GB',
                        '32GB'  => '32 GB',
                        '64GB'  => '64 GB',
                        '128GB' => '128 GB',
                        '256GB' => '256 GB',
                        '512GB' => '512 GB',
                        '1TB'   => '1 TB',
                    ]),

                FormSelect::make('custom_fields_values.ram')
                    ->label('الرام')
                    ->options([
                        '2GB'  => '2 GB',
                        '3GB'  => '3 GB',
                        '4GB'  => '4 GB',
                        '6GB'  => '6 GB',
                        '8GB'  => '8 GB',
                        '12GB' => '12 GB',
                        '16GB' => '16 GB',
                    ]),

                FormSelect::make('custom_fields_values.color')
                    ->label('اللون')
                    ->options([
                        'black'     => 'أسود',
                        'white'     => 'أبيض',
                        'gold'      => 'ذهبي',
                        'silver'    => 'فضي',
                        'blue'      => 'أزرق',
                        'red'       => 'أحمر',
                        'green'     => 'أخضر',
                        'purple'    => 'بنفسجي',
                        'pink'      => 'وردي',
                        'other'     => 'أخرى',
                    ]),

                FormTextInput::make('custom_fields_values.accessories')
                    ->label('الملحقات')
                    ->placeholder('مثال: شاحن، سماعة، علبة أصلية...'),
            ]);
    }
}