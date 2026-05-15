<?php

namespace App\Filament\Admin\Resources\AuditLogs;

use App\Models\AuditLog;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static string|UnitEnum|null   $navigationGroup = 'الإدارة';
    protected static ?string $navigationLabel = 'سجل الإجراءات';
    protected static ?int    $navigationSort  = 10;

    public static function canCreate(): bool        { return false; }
    public static function canEdit($record): bool   { return false; }
    public static function canDelete($record): bool { return false; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(AuditLog::query()->with('admin')->latest())
            ->columns([
                TextColumn::make('id')->label('#')->width('60px'),

                TextColumn::make('created_at')
                    ->label('التوقيت')
                    ->dateTime('d M Y — H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('d M Y H:i:s')),

                TextColumn::make('admin.name')
                    ->label('المشرف')
                    ->searchable()
                    ->icon('heroicon-m-user-circle'),

                TextColumn::make('action')
                    ->label('الإجراء')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'approve') => 'success',
                        str_contains($state, 'reject')  => 'danger',
                        str_contains($state, 'ban')     => 'danger',
                        str_contains($state, 'flag')    => 'warning',
                        str_contains($state, 'unban')   => 'success',
                        str_contains($state, 'strike')  => 'warning',
                        default                         => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approve_ad'    => '✅ موافقة على إعلان',
                        'reject_ad'     => '❌ رفض إعلان',
                        'flag_ad'       => '🚩 تحديد إعلان',
                        'ban_user'      => '🚫 حظر مستخدم',
                        'unban_user'    => '✅ رفع حظر',
                        'add_strike'    => '⚠️ إضافة مخالفة',
                        'reset_strikes' => '🔄 إعادة تعيين مخالفات',
                        default         => $state,
                    }),

                TextColumn::make('target_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'App\\Models\\Listing' => 'إعلان',
                        'App\\Models\\User'    => 'مستخدم',
                        default                => class_basename($state),
                    })
                    ->badge()
                    ->color('info'),

                TextColumn::make('target_id')->label('ID المستهدف')->alignCenter(),

                TextColumn::make('payload')
                    ->label('التفاصيل')
                    ->formatStateUsing(function ($state): string {
                        // ✅ الحل: نتعامل مع أي نوع بياني بأمان
                        if (empty($state)) {
                            return '—';
                        }

                        // لو string — نحاول نحوله لـ array
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                                return $state; // نرجعه كما هو
                            }
                            $state = $decoded;
                        }

                        // لو مش array بعد كل ده — نرجع dash
                        if (! is_array($state)) {
                            return '—';
                        }

                        return implode(' | ', array_map(
                            fn ($k, $v) => "{$k}: {$v}",
                            array_keys($state),
                            array_values($state)
                        ));
                    })
                    ->limit(60)
                    ->tooltip(function ($record): string {
                        $payload = $record->payload;
                        if (empty($payload)) return '—';
                        if (is_string($payload)) {
                            return $payload;
                        }
                        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    }),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('الإجراء')
                    ->options([
                        'approve_ad'    => 'موافقة على إعلان',
                        'reject_ad'     => 'رفض إعلان',
                        'flag_ad'       => 'تحديد إعلان',
                        'ban_user'      => 'حظر مستخدم',
                        'unban_user'    => 'رفع حظر',
                        'add_strike'    => 'إضافة مخالفة',
                        'reset_strikes' => 'إعادة تعيين مخالفات',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}