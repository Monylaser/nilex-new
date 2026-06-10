<?php

namespace App\Filament\Admin\Resources\LegalPages\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LegalPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('عنوان الصفحة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('الرابط')
                    ->searchable()
                    ->copyable()
                    ->prefix('/pages/'),

                ToggleColumn::make('is_active')
                    ->label('نشطة'),

                TextColumn::make('updated_at')
                    ->label('آخر تعديل')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('النشطة فقط')
                    ->falseLabel('المخفية فقط'),
            ])
            ->actions([
                EditAction::make()->label('تعديل'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }
}
