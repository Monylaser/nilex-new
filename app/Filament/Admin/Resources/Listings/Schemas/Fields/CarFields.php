<?php

namespace App\Filament\Admin\Resources\Listings\Schemas\Fields;

use App\Models\CarBrand;
use App\Models\CarModel;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Schemas\Components\Section as FormSection;

class CarFields
{
    public static function make(): FormSection
    {
        return FormSection::make('مواصفات السيارة')
            ->columns(2)
            // استخدام المسار بدون تحديد النوع عشان VS Code يسكت
            ->visible(fn ($get) => (int) $get('category_id') === 12)
            ->schema([
                
                // 1. الماركة
                FormSelect::make('custom_fields_values.brand_id')
                    ->label('الماركة')
                    ->options(fn () => CarBrand::active()
                        ->orderBy('sort_order')
                        ->pluck('name_ar', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->required()
                    ->live()
                    // تصفير الموديل عند تغيير الماركة
                    ->afterStateUpdated(fn ($set) => $set('custom_fields_values.model_id', null)),

                // 2. الموديل
                FormSelect::make('custom_fields_values.model_id')
                    ->label('الموديل')
                    ->options(function ($get) {
                        $brandId = $get('custom_fields_values.brand_id');
                        
                        if (blank($brandId)) return [];

                        return CarModel::where('car_brand_id', $brandId)
                            ->active()
                            ->orderBy('name_ar')
                            ->pluck('name_ar', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->disabled(fn ($get) => blank($get('custom_fields_values.brand_id')))
                    ->helperText('اختر الماركة أولاً'),

                FormSelect::make('custom_fields_values.year')
                    ->label('سنة الصنع')
                    ->options(array_combine(range(date('Y'), 1970), range(date('Y'), 1970)))
                    ->searchable()
                    ->required(),

                FormSelect::make('custom_fields_values.transmission')
                    ->label('ناقل الحركة')
                    ->options([
                        'automatic' => 'أوتوماتيك',
                        'manual'    => 'مانيوال',
                    ])
                    ->required(),

                FormSelect::make('custom_fields_values.fuel')
                    ->label('نوع الوقود')
                    ->options([
                        'petrol'   => 'بنزين',
                        'diesel'   => 'ديزل',
                        'electric' => 'كهربائي',
                        'hybrid'   => 'هجين',
                        'gas'      => 'غاز (CNG/LPG)',
                    ])
                    ->required(),

                // 3. حالة السيارة (نص كما طلبت)
                FormTextInput::make('custom_fields_values.condition')
                    ->label('حالة السيارة')
                    ->placeholder('مثال: فابريكا بالكامل، حالة ممتازة...')
                    ->required(),

                FormTextInput::make('custom_fields_values.mileage')
                    ->label('عداد الكيلومترات')
                    ->numeric()
                    ->suffix('كم')
                    ->placeholder('مثال: 85000'),

                FormTextInput::make('custom_fields_values.color')
                    ->label('لون السيارة')
                    ->placeholder('مثال: أبيض، أسود...'),
            ]);
    }
}