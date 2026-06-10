<?php

namespace App\Filament\Admin\Resources\PointPlans;

use App\Filament\Admin\Resources\PointPlans\Pages;
use App\Models\PointPlan;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PointPlanResource extends Resource
{
    protected static ?string $model = PointPlan::class;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-currency-dollar';
    protected static string|UnitEnum|null   $navigationGroup = 'الإدارة';
    protected static ?string $navigationLabel    = 'خطط النقاط';
    protected static ?string $modelLabel         = 'خطة نقاط';
    protected static ?string $pluralModelLabel   = 'خطط النقاط';
    protected static ?int    $navigationSort     = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name_ar')
                ->label('الاسم بالعربية')
                ->required()
                ->maxLength(255),

            TextInput::make('name_en')
                ->label('الاسم بالإنجليزية')
                ->maxLength(255),

            TextInput::make('points')
                ->label('عدد النقاط')
                ->numeric()
                ->required()
                ->minValue(1),

            TextInput::make('price')
                ->label('السعر (ج.م)')
                ->numeric()
                ->required()
                ->minValue(0)
                ->step(0.01),

            Textarea::make('description')
                ->label('الوصف')
                ->rows(3),

            Toggle::make('is_active')
                ->label('نشط')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->width('60px'),

                TextColumn::make('name_ar')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('points')
                    ->label('النقاط')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('price')
                    ->label('السعر')
                    ->money('EGP')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPointPlans::route('/'),
            'create' => Pages\CreatePointPlan::route('/create'),
            'edit'   => Pages\EditPointPlan::route('/{record}/edit'),
        ];
    }
}
