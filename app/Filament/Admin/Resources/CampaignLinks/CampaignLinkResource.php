<?php

namespace App\Filament\Admin\Resources\CampaignLinks;

use App\Filament\Admin\Resources\CampaignLinks\Pages;
use App\Models\CampaignLink;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CampaignLinkResource extends Resource
{
    protected static ?string $model = CampaignLink::class;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-link';
    protected static string|UnitEnum|null   $navigationGroup = 'الإدارة';
    protected static ?string $navigationLabel    = 'روابط الإحالة';
    protected static ?string $modelLabel         = 'رابط إحالة';
    protected static ?string $pluralModelLabel   = 'روابط الإحالة';
    protected static ?int    $navigationSort     = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label('كود الإحالة')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(100)
                ->helperText('مثال: SUMMER2026'),

            TextInput::make('points_reward')
                ->label('النقاط الممنوحة عند الاشتراك')
                ->numeric()
                ->required()
                ->minValue(0),

            TextInput::make('usage_limit')
                ->label('الحد الأقصى للاستخدام')
                ->numeric()
                ->nullable()
                ->minValue(1)
                ->helperText('اتركه فارغاً للاستخدام غير المحدود'),

            DateTimePicker::make('expires_at')
                ->label('تنتهي في')
                ->nullable(),

            TextInput::make('used_count')
                ->label('مرات الاستخدام')
                ->numeric()
                ->default(0)
                ->disabled(),

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

                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('points_reward')
                    ->label('النقاط')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('used_count')
                    ->label('الاستخدامات')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('usage_limit')
                    ->label('الحد الأقصى')
                    ->numeric()
                    ->default('∞'),

                TextColumn::make('expires_at')
                    ->label('ينتهي في')
                    ->dateTime('d/m/Y')
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
            'index'  => Pages\ListCampaignLinks::route('/'),
            'create' => Pages\CreateCampaignLink::route('/create'),
            'edit'   => Pages\EditCampaignLink::route('/{record}/edit'),
        ];
    }
}
