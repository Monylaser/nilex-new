<?php

namespace App\Filament\Admin\Resources\Locations\Tables;

use App\Models\Location;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name_ar')
                    ->label('الاسم بالعربي')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name_en')
                    ->label('English Name')
                    ->searchable(),

                // عرض نوع السجل (محافظة / مدينة) كـ badge ملوّن
                TextColumn::make('level')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        Location::LEVEL_GOVERNORATE => 'success',
                        Location::LEVEL_CITY        => 'info',
                        default                     => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        Location::LEVEL_GOVERNORATE => 'محافظة',
                        Location::LEVEL_CITY        => 'مدينة',
                        default                     => 'غير معروف',
                    }),

                // عدد المدن التابعة لكل محافظة
                TextColumn::make('children_count')
                    ->counts('children')
                    ->label('المدن')
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label('النوع')
                    ->options([
                        Location::LEVEL_GOVERNORATE => 'محافظات فقط',
                        Location::LEVEL_CITY        => 'مدن فقط',
                    ])
                    ->native(false),

                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->native(false),
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
