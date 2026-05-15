<?php

namespace App\Filament\Admin\Resources\UserResource;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\AuditLog;
use App\Models\User;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;           // ✅ Filament v5: من Filament\Actions
use Filament\Actions\ActionGroup;      // ✅ Filament v5: من Filament\Actions
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-users';
    protected static string|UnitEnum|null   $navigationGroup = 'الإشراف';
    protected static ?string $navigationLabel = 'المستخدمون';
    protected static ?int    $navigationSort  = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('الاسم')->required(),
            TextInput::make('email')->label('البريد الإلكتروني')->email()->required(),
            Select::make('roles')
                ->label('الدور')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload(),
            Toggle::make('is_banned')->label('محظور'),
            Textarea::make('ban_reason')->label('سبب الحظر')->rows(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->icon('heroicon-m-user'),

                TextColumn::make('email')
                    ->label('البريد')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('roles.name')
                    ->label('الدور')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('strike_count')
                    ->label('المخالفات')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (int) $state === 0 => 'success',
                        (int) $state === 1 => 'warning',
                        (int) $state >= 2  => 'danger',
                        default            => 'gray',
                    })
                    ->alignCenter(),

                IconColumn::make('is_banned')
                    ->label('محظور')
                    ->boolean()
                    ->trueIcon('heroicon-m-no-symbol')
                    ->falseIcon('heroicon-m-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('listings_count')
                    ->label('الإعلانات')
                    ->counts('listings')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_banned')
                    ->label('الحالة')
                    ->trueLabel('محظورون فقط')
                    ->falseLabel('نشطون فقط'),

                SelectFilter::make('roles')
                    ->label('الدور')
                    ->relationship('roles', 'name'),
            ])
            // ✅ Filament v5: recordActions بدل actions
            ->recordActions([
                ActionGroup::make([

                    Action::make('ban')
                        ->label('حظر')
                        ->icon('heroicon-m-no-symbol')
                        ->color('danger')
                        ->visible(fn ($record) => ! $record->is_banned)
                        ->form([
                            Textarea::make('ban_reason')
                                ->label('سبب الحظر')
                                ->required()
                                ->rows(2),
                        ])
                        ->action(function (array $data, $record): void {
                            $record->ban($data['ban_reason']);
                            AuditLog::record('ban_user', $record, ['reason' => $data['ban_reason']]);
                            Notification::make()->title('تم حظر المستخدم')->danger()->send();
                        }),

                    Action::make('unban')
                        ->label('رفع الحظر')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->is_banned)
                        ->requiresConfirmation()
                        ->action(function ($record): void {
                            $record->unban();
                            AuditLog::record('unban_user', $record);
                            Notification::make()->title('تم رفع الحظر')->success()->send();
                        }),

                    Action::make('add_strike')
                        ->label('إضافة مخالفة')
                        ->icon('heroicon-m-exclamation-triangle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('إضافة مخالفة')
                        ->modalDescription('عند 3 مخالفات يُحظر المستخدم تلقائياً.')
                        ->action(function ($record): void {
                            $record->addStrike();
                            AuditLog::record('add_strike', $record, ['strike_count' => $record->strike_count]);
                            Notification::make()
                                ->title('تم إضافة مخالفة')
                                ->body("المخالفات الحالية: {$record->strike_count}")
                                ->warning()
                                ->send();
                        }),

                    Action::make('reset_strikes')
                        ->label('إعادة تعيين المخالفات')
                        ->icon('heroicon-m-arrow-path')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function ($record): void {
                            $record->update(['strike_count' => 0]);
                            AuditLog::record('reset_strikes', $record);
                            Notification::make()->title('تم إعادة تعيين المخالفات')->success()->send();
                        }),

                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}