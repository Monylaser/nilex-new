<?php

namespace App\Filament\Admin\Resources\ActivityLogs;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'سجل النشاطات';

    protected static string|UnitEnum|null $navigationGroup = 'الإدارة';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'نشاط';

    protected static ?string $pluralModelLabel = 'سجل النشاطات';

    // Read-only resource
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
            ->query(Activity::query()->with(['causer', 'subject'])->latest())
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('created_at')
                    ->label('التوقيت')
                    ->dateTime('d M Y — H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('d M Y H:i:s')),

                TextColumn::make('log_name')
                    ->label('القناة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'auth'    => 'warning',
                        'roles'   => 'violet',
                        'default' => 'gray',
                        default   => 'info',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'auth'    => '🔐 مصادقة',
                        'roles'   => '👑 صلاحيات',
                        'default' => '📋 عام',
                        default   => $state,
                    }),

                TextColumn::make('event')
                    ->label('الحدث')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'created' => 'إنشاء',
                        'updated' => 'تعديل',
                        'deleted' => 'حذف',
                        default   => $state ?? '—',
                    }),

                TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->description),

                TextColumn::make('causer.name')
                    ->label('المُنفِّذ')
                    ->icon('heroicon-m-user-circle')
                    ->placeholder('النظام')
                    ->searchable(),

                TextColumn::make('subject_type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '—')
                    ->badge()
                    ->color('info'),

                TextColumn::make('subject_id')
                    ->label('ID المستهدف')
                    ->alignCenter()
                    ->placeholder('—'),

                TextColumn::make('properties')
                    ->label('التفاصيل')
                    ->formatStateUsing(function ($state): string {
                        if (empty($state)) return '—';

                        $data = is_string($state)
                            ? (json_decode($state, true) ?? [])
                            : (is_array($state) ? $state : (method_exists($state, 'toArray') ? $state->toArray() : []));

                        $old = $data['old'] ?? [];
                        $new = $data['attributes'] ?? [];

                        if (! empty($old) && ! empty($new)) {
                            return collect($new)
                                ->map(fn ($v, $k) => isset($old[$k]) && $old[$k] !== $v
                                    ? "{$k}: {$old[$k]} → {$v}"
                                    : null)
                                ->filter()
                                ->implode(' | ') ?: '—';
                        }

                        return collect($data)
                            ->except(['old', 'attributes'])
                            ->map(fn ($v, $k) => "{$k}: " . (is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v))
                            ->implode(' | ') ?: '—';
                    })
                    ->limit(70)
                    ->tooltip(fn ($record) => $record->properties
                        ? json_encode($record->properties, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                        : '—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('القناة')
                    ->options([
                        'auth'    => '🔐 مصادقة',
                        'roles'   => '👑 صلاحيات',
                        'default' => '📋 عام',
                    ]),

                SelectFilter::make('event')
                    ->label('الحدث')
                    ->options([
                        'created' => 'إنشاء',
                        'updated' => 'تعديل',
                        'deleted' => 'حذف',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
