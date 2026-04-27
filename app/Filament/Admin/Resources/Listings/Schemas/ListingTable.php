<?php

namespace App\Filament\Admin\Resources\Listings\Schemas;

use App\Models\Listing;
use Filament\Tables\Table;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;

class ListingTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('images')
                    ->label('الصورة')
                    ->collection('listings')
                    ->conversion('thumb')
                    ->circular(),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->description(function (Listing $record) {
                        if (empty($record->custom_fields_values)) return null;

                        return collect($record->custom_fields_values)
                            ->take(3)
                            ->map(function ($val, $key) use ($record) {
                                $schema  = $record->category?->custom_fields_schema ?? [];
                                $label   = collect($schema)->firstWhere('name', $key)['label_ar'] ?? $key;
                                $display = is_bool($val) ? ($val ? 'نعم' : 'لا') : $val;
                                return "{$label}: {$display}";
                            })
                            ->join(' | ');
                    })
                    ->wrap(),

                TextColumn::make('category.name_ar')
                    ->label('القسم'),

                TextColumn::make('price')
                    ->label('السعر')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state) . ' ج.م'),

                TextColumn::make('province.name_ar')
                    ->label('المحافظة'),

                TextColumn::make('created_at')
                    ->label('تاريخ النشر')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }
}