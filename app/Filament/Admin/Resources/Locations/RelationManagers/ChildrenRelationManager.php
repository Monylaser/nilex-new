<?php

namespace App\Filament\Admin\Resources\Locations\RelationManagers;

use App\Models\Location;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $title = 'المدن التابعة';

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            TextInput::make('name_ar')
                ->label('اسم المدينة بالعربية')
                ->required()
                ->maxLength(255),

            TextInput::make('name_en')
                ->label('اسم المدينة بالإنجليزية')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, $set, $get) {
                    // ربط الـ slug بالمحافظة الأم + اسم المدينة
                    $parent = $this->getOwnerRecord();
                    $parentSlug = $parent?->slug ?? Str::slug($parent?->name_en ?? '');
                    $set('slug', $parentSlug . '-' . Str::slug($state));
                }),

            TextInput::make('slug')
                ->label('الرابط (Slug)')
                ->required()
                ->disabled()
                ->dehydrated()
                ->maxLength(255),

            TextInput::make('sort_order')
                ->label('الترتيب')
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->label('نشطة')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_ar')
            ->columns([
                TextColumn::make('name_ar')
                    ->label('اسم المدينة')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('name_en')
                    ->label('English Name')
                    ->searchable(),

                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->native(false),
            ])
            ->headerActions([
                CreateAction::make()->label('إضافة مدينة'),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}
