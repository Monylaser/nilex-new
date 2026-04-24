<?php

namespace App\Filament\Admin\Resources\Categories\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use App\Models\Category;
use Illuminate\Support\Facades\Blade; // السطر ده مهم جداً عشان يشغل الأيقونة

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // 1. الأيقونة: تم إصلاح استدعاء Blade
                TextColumn::make('icon')
                    ->label('')
                    ->formatStateUsing(fn (string $state) => Blade::render("<x-heroicon-o-{$state} class='w-8 h-8' />"))
                    ->html()
                    ->alignCenter(),

                // 2. الاسم (عربي/إنجليزي)
                TextColumn::make('name_ar')
                    ->label('القسم')
                    ->description(fn (Category $record): string => $record->name_en)
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // 3. عداد المشاهدات
                TextColumn::make('views_count')
                    ->label('المشاهدات')
                    ->icon('heroicon-o-eye')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->alignCenter(),

                // 4. اللون
                ColorColumn::make('color')
                    ->label('اللون')
                    ->copyable(),

                // 5. العدادات
                TextColumn::make('children_count')
                    ->label('فرعيات')
                    ->counts('children')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('listings_count')
                    ->label('إعلانات')
                    ->counts('listings')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                // 6. الحالة
                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->native(false),
            ])
            // تم تغيير الميثود لـ recordActions لإنهاء الـ Deprecation warning في v5.4
            ->recordActions([
                EditAction::make()
                    ->label('تعديل'),

                Action::make('toggleActive')
                    ->label(fn (Category $record) => $record->is_active ? 'إخفاء' : 'تنشيط')
                    ->icon(fn (Category $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Category $record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn (Category $record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order');
    }
}