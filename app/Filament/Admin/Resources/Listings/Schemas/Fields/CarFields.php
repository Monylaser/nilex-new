<?php

namespace App\Filament\Admin\Resources\Listings\Schemas\Fields;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\Category;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class CarFields
{
    public static function make(): Section
    {
        return Section::make('مواصفات السيارة')
            ->columns(2)
            ->visible(
                fn ($get) => Category::find($get('category_id'))?->slug === 'cars'
            )
            ->schema([

                // ────────────────────────────────────────────────────────
                // car_brand_id — column مستقلة في جدول listings
                // ────────────────────────────────────────────────────────
                Select::make('car_brand_id')
                    ->label('الماركة')
                    ->options(
                        fn () => CarBrand::where('is_active', true)
                            ->orderBy('sort_order')
                            ->pluck('name_ar', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('car_model_id', null)),

                // ────────────────────────────────────────────────────────
                // car_model_id — column مستقلة في جدول listings
                // ────────────────────────────────────────────────────────
                Select::make('car_model_id')
                    ->label('الموديل')
                    ->options(function ($get) {
                        $brandId = $get('car_brand_id');
                        if (blank($brandId)) {
                            return [];
                        }

                        return CarModel::where('car_brand_id', (int) $brandId)
                            ->where('is_active', true)
                            ->orderBy('name_ar')
                            ->pluck('name_ar', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->live()
                    ->required()
                    ->disabled(fn ($get) => blank($get('car_brand_id')))
                    ->helperText('اختر الماركة أولاً'),

                // ────────────────────────────────────────────────────────
                // year — مخزنة داخل JSON column (custom_fields_values)
                // dot notation بدون statePath — هو الحل الصح في Filament v5
                // ────────────────────────────────────────────────────────
                Select::make('custom_fields_values.year')
                    ->label('سنة الصنع')
                    ->options(array_combine(
                        range((int) date('Y'), 1970),
                        range((int) date('Y'), 1970)
                    ))
                    ->searchable()
                    ->required()
                    ->dehydrateStateUsing(fn ($state) => (string) $state),

                Select::make('custom_fields_values.transmission')
                    ->label('ناقل الحركة')
                    ->options([
                        'automatic' => 'أوتوماتيك',
                        'manual'    => 'مانيوال',
                    ])
                    ->required(),

                Select::make('custom_fields_values.fuel')
                    ->label('نوع الوقود')
                    ->options([
                        'petrol'   => 'بنزين',
                        'diesel'   => 'ديزل',
                        'electric' => 'كهربائي',
                        'hybrid'   => 'هجين',
                        'gas'      => 'غاز (CNG/LPG)',
                    ])
                    ->required(),

                TextInput::make('custom_fields_values.condition')
                    ->label('حالة السيارة')
                    ->placeholder('مثال: فابريكا بالكامل، حالة ممتازة...')
                    ->required(),

                TextInput::make('custom_fields_values.mileage')
                    ->label('عداد الكيلومترات')
                    ->numeric()
                    ->suffix('كم')
                    ->placeholder('مثال: 85000'),

                TextInput::make('custom_fields_values.color')
                    ->label('لون السيارة')
                    ->placeholder('مثال: أبيض، أسود...'),
            ]);
    }
}