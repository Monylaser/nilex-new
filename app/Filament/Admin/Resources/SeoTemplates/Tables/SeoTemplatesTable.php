<?php

namespace App\Filament\Admin\Resources\SeoTemplates\Tables;

use App\Models\SeoTemplate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SeoTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('target_model')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => SeoTemplate::supportedModels()[$state] ?? class_basename($state))
                    ->badge()
                    ->color('info'),

                TextColumn::make('target_id')
                    ->label('الهدف')
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record->target_model) {
                            return $state;
                        }
                        try {
                            $model = app($record->target_model)::find($state);
                            return $model?->name_ar ?? $model?->name ?? "#{$state}";
                        } catch (\Throwable) {
                            return "#{$state}";
                        }
                    })
                    ->searchable(),

                TextColumn::make('meta_title')
                    ->label('Meta Title')
                    ->limit(50)
                    ->placeholder('—'),

                TextColumn::make('meta_description')
                    ->label('Meta Description')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                TextColumn::make('canonical_url')
                    ->label('Canonical URL')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('target_model')
                    ->label('النوع')
                    ->options(SeoTemplate::supportedModels()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
