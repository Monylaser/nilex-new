<?php

namespace App\Filament\Admin\Resources\Locations\Schemas;

use App\Models\Location;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('البيانات الأساسية')
                ->columns(2)
                ->schema([

                    TextInput::make('name_ar')
                        ->label('الاسم بالعربية')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('name_en')
                        ->label('الاسم بالإنجليزية')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                    TextInput::make('slug')
                        ->label('الرابط (Slug)')
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Section::make('التصنيف والترتيب')
                ->columns(2)
                ->schema([

                    Select::make('level')
                        ->label('النوع')
                        ->options([
                            Location::LEVEL_GOVERNORATE => 'محافظة',
                            Location::LEVEL_CITY        => 'مدينة / حي',
                        ])
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('parent_id', null)),

                    // يظهر فقط عند اختيار "مدينة"
                    Select::make('parent_id')
                        ->label('المحافظة التابعة لها')
                        ->options(
                            fn () => Location::governorates()->pluck('name_ar', 'id')
                        )
                        ->searchable()
                        ->native(false)
                        ->required(
                            fn ($get) => (int) $get('level') === Location::LEVEL_CITY
                        )
                        ->visible(
                            fn ($get) => (int) $get('level') === Location::LEVEL_CITY
                        )
                        ->placeholder('اختر المحافظة'),

                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(0),

                    Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
                ]),
        ]);
    }
}
