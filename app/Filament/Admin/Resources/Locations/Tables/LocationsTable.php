<?php

namespace App\Filament\Admin\Resources\Locations\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
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
                    ->sortable(),

                TextColumn::make('name_en')
                    ->label('English Name')
                    ->searchable(),

                TextColumn::make('level')
                    ->label('المستوى')
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        0 => 'success', // محافظة
                        1 => 'info',    // مدينة
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state) => $state === 0 ? 'محافظة' : 'مدينة'),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                // هنضيف فلاتر هنا لاحقاً
            ]);
    }
}
