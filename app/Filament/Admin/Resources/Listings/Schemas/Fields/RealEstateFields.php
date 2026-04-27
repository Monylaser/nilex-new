<?php

namespace App\Filament\Admin\Resources\Listings\Schemas\Fields;

use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Schemas\Components\Section as FormSection;

class RealEstateFields
{
    public static function make(): FormSection
    {
        return FormSection::make('مواصفات العقار')
            ->columns(2)
            ->visible(fn ($get) => (int) $get('category_id') === 1)
            ->schema([
                FormSelect::make('custom_fields_values.property_type')
                    ->label('نوع العقار')
                    ->options([
                        'apartment' => 'شقة',
                        'villa'     => 'فيلا',
                        'duplex'    => 'دوبليكس',
                        'studio'    => 'استوديو',
                        'chalet'    => 'شاليه',
                        'office'    => 'مكتب',
                        'shop'      => 'محل تجاري',
                        'warehouse' => 'مخزن',
                        'land'      => 'أرض',
                        'building'  => 'عمارة',
                    ])
                    ->required()
                    ->searchable(),

                FormSelect::make('custom_fields_values.listing_type')
                    ->label('نوع العرض')
                    ->options([
                        'sale' => 'للبيع',
                        'rent' => 'للإيجار',
                    ])
                    ->required(),

                FormSelect::make('custom_fields_values.rooms')
                    ->label('عدد الغرف')
                    ->options([
                        '1'  => 'غرفة واحدة',
                        '2'  => 'غرفتان',
                        '3'  => '3 غرف',
                        '4'  => '4 غرف',
                        '5'  => '5 غرف',
                        '6+' => '6 غرف أو أكثر',
                    ]),

                FormSelect::make('custom_fields_values.bathrooms')
                    ->label('عدد الحمامات')
                    ->options([
                        '1'  => 'حمام واحد',
                        '2'  => 'حمامان',
                        '3'  => '3 حمامات',
                        '4+' => '4 أو أكثر',
                    ]),

                FormSelect::make('custom_fields_values.floor')
                    ->label('الدور')
                    ->options([
                        'ground'  => 'أرضي',
                        '1'       => 'الأول',
                        '2'       => 'الثاني',
                        '3'       => 'الثالث',
                        '4'       => 'الرابع',
                        '5'       => 'الخامس',
                        '6+'      => 'السادس فأكثر',
                        'rooftop' => 'روف',
                    ]),

                FormSelect::make('custom_fields_values.finishing')
                    ->label('نوع التشطيب')
                    ->options([
                        'super_lux'  => 'سوبر لوكس',
                        'lux'        => 'لوكس',
                        'semi_lux'   => 'نص لوكس',
                        'core_shell' => 'كور وشل',
                        'unfinished' => 'تشطيب عادي',
                        'furnished'  => 'مفروش',
                    ]),

                FormTextInput::make('custom_fields_values.area')
                    ->label('المساحة')
                    ->numeric()
                    ->suffix('م²')
                    ->placeholder('مثال: 120'),

                FormSelect::make('custom_fields_values.compound')
                    ->label('هل في كمباوند؟')
                    ->options([
                        'yes' => 'نعم',
                        'no'  => 'لا',
                    ]),
            ]);
    }
}