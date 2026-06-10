<?php

namespace App\Filament\Admin\Resources\Campaigns\Tables;

use App\Models\Campaign;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('title')
                    ->label('عنوان الحملة')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('target_group')
                    ->label('الفئة')
                    ->formatStateUsing(fn ($state) => Campaign::targetGroupOptions()[$state] ?? $state)
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Campaign::STATUS_SENT      => 'success',
                        Campaign::STATUS_SCHEDULED => 'warning',
                        Campaign::STATUS_FAILED    => 'danger',
                        default                    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => Campaign::statusOptions()[$state] ?? $state),

                TextColumn::make('recipients_count')
                    ->label('المستلمون')
                    ->alignCenter()
                    ->numeric(),

                TextColumn::make('scheduled_at')
                    ->label('موعد الإرسال')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('sent_at')
                    ->label('أُرسل في')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('لم يُرسل بعد'),

                TextColumn::make('created_at')
                    ->label('أُنشئ في')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(Campaign::statusOptions()),

                SelectFilter::make('target_group')
                    ->label('الفئة')
                    ->options(Campaign::targetGroupOptions()),
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
            ->defaultSort('created_at', 'desc');
    }
}
