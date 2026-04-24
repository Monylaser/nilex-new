<?php
// app/Filament/Admin/Resources/Users/Tables/UsersTable.php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Services\PointService;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(static::columns())
            ->recordActions(static::recordActions())
            ->groupedBulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }

    // ── Columns ──────────────────────────────────────────────────

    private static function columns(): array
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->sortable(),

            TextColumn::make('name')
                ->label('الاسم')
                ->searchable()
                ->sortable(),

            TextColumn::make('email')
                ->label('البريد الإلكتروني')
                ->searchable()
                ->sortable(),

            TextColumn::make('points')
                ->label('النقاط')
                ->numeric()
                ->sortable()
                ->badge()
                ->color('success')
                ->alignEnd(),

            TextColumn::make('created_at')
                ->label('تاريخ التسجيل')
                ->dateTime('M d, Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // ── Record Actions ────────────────────────────────────────────

    private static function recordActions(): array
    {
        return [
            EditAction::make(),

            Action::make('add_points')
                ->label('إضافة نقاط')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->modalHeading('إضافة نقاط للمستخدم')
                ->modalSubmitActionLabel('إضافة')
                ->schema([                           // ✅ v5 بيستخدم schema مش form
                    TextInput::make('amount')
                        ->label('الكمية')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->prefix('pts'),

                    TextInput::make('description')
                        ->label('السبب')
                        ->placeholder('مثال: مكافأة ترحيب')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data, $record): void {
                    app(PointService::class)->credit(
                        user:        $record,
                        amount:      (int) $data['amount'],
                        description: $data['description'],
                    );

                    Notification::make()
                        ->title('تم إضافة النقاط بنجاح')
                        ->body("+{$data['amount']} نقطة أُضيفت لـ {$record->name}")
                        ->success()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }
}
