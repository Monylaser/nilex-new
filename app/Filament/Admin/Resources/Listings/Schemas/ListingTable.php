<?php

namespace App\Filament\Admin\Resources\Listings\Schemas;

use App\Models\AuditLog;
use App\Models\Listing;
use App\Notifications\ListingStatusNotification;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

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

                TextColumn::make('user.name')
                    ->label('المعلن')
                    ->searchable()
                    ->icon('heroicon-m-user'),

                TextColumn::make('category.name_ar')
                    ->label('القسم')
                    ->badge()
                    ->color('info'),

                TextColumn::make('price')
                    ->label('السعر')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state) . ' ج.م'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'published' => 'success',
                        'rejected'  => 'danger',
                        'flagged'   => 'gray',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'   => 'قيد المراجعة',
                        'published' => 'منشور',
                        'rejected'  => 'مرفوض',
                        'flagged'   => 'مُبلَّغ عنه',
                        default     => $state,
                    }),

                // ✅ عمود التمييز
                TextColumn::make('featured_until')
                    ->label('مميز حتى')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('province.name_ar')
                    ->label('المحافظة'),

                TextColumn::make('created_at')
                    ->label('تاريخ النشر')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(Listing::statusOptions()),

                SelectFilter::make('category_id')
                    ->label('القسم')
                    ->relationship('category', 'name_ar'),
            ])
            ->recordActions([
                ActionGroup::make([

                    // ══════════════════════════════════════════
                    // ✅ قبول ونشر
                    // ══════════════════════════════════════════
                    Action::make('approve')
                        ->label('قبول ونشر')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status !== Listing::STATUS_PUBLISHED)
                        ->requiresConfirmation()
                        ->modalHeading('تأكيد نشر الإعلان')
                        ->modalDescription(fn ($record) => "هل تريد نشر إعلان \"{$record->title}\"؟")
                        ->modalIcon('heroicon-o-check-circle')
                        ->action(function ($record): void {
                            $record->approve(Auth::id());

                            AuditLog::record('approve_ad', $record, ['title' => $record->title]);
                            static::notifyUser($record, 'published');

                            Notification::make()
                                ->title('✅ تم نشر الإعلان')
                                ->body("\"{$record->title}\" أصبح منشوراً.")
                                ->success()->send();
                        }),

                    // ══════════════════════════════════════════
                    // ❌ رفض مع dropdown
                    // ══════════════════════════════════════════
                    Action::make('reject')
                        ->label('رفض')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->status !== Listing::STATUS_REJECTED)
                        ->modalHeading(fn ($record) => 'رفض إعلان: ' . $record->title)
                        ->modalIcon('heroicon-o-x-circle')
                        ->modalWidth('lg')
                        ->form([
                            Select::make('rejection_reason')
                                ->label('سبب الرفض')
                                ->options(Listing::rejectionReasonOptions())
                                ->required()->searchable()
                                ->helperText('الأسباب المحددة بـ ⚠️ تضيف مخالفة تلقائية للمعلن'),

                            Textarea::make('extra_note')
                                ->label('ملاحظة إضافية (اختياري)')
                                ->placeholder('أي تفاصيل إضافية للمعلن...')
                                ->rows(2),
                        ])
                        ->action(function (array $data, $record): void {
                            $reason = $data['rejection_reason'];
                            $note   = $data['extra_note'] ?? null;

                            $record->reject(Auth::id(), $reason);

                            if ($record->rejectionCausesStrike($reason)) {
                                $record->user->addStrike();
                                AuditLog::record('auto_strike', $record->user, [
                                    'reason'       => $reason,
                                    'listing_id'   => $record->id,
                                    'strike_count' => $record->user->fresh()->strike_count,
                                ]);
                            }

                            AuditLog::record('reject_ad', $record, [
                                'title'            => $record->title,
                                'rejection_reason' => $reason,
                                'extra_note'       => $note,
                                'causes_strike'    => $record->rejectionCausesStrike($reason) ? 'yes' : 'no',
                            ]);

                            static::notifyUser($record, 'rejected', $reason, $note);

                            $label = Listing::rejectionReasonOptions()[$reason] ?? $reason;
                            Notification::make()
                                ->title('❌ تم رفض الإعلان')
                                ->body("\"{$record->title}\" — {$label}")
                                ->danger()->send();
                        }),

                    // ══════════════════════════════════════════
                    // 🌟 تمييز الإعلان بالنقاط
                    // ══════════════════════════════════════════
                    Action::make('feature_ad')
                        ->label('تمييز بالنقاط ⭐')
                        ->icon('heroicon-m-star')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === Listing::STATUS_PUBLISHED)
                        ->modalHeading(fn ($record) => 'تمييز: ' . $record->title)
                        ->modalIcon('heroicon-o-star')
                        ->modalWidth('md')
                        ->form([
                            Select::make('days')
                                ->label('مدة التمييز')
                                ->options([
                                    3  => '3 أيام  — ' . Listing::featureCost(3)  . ' نقطة',
                                    7  => '7 أيام  — ' . Listing::featureCost(7)  . ' نقطة',
                                    14 => '14 يوم  — ' . Listing::featureCost(14) . ' نقطة',
                                    30 => '30 يوم  — ' . Listing::featureCost(30) . ' نقطة',
                                ])
                                ->required()
                                ->helperText(fn ($record) =>
                                    "نقاط المعلن الحالية: {$record->user->points} نقطة"
                                ),
                        ])
                        ->action(function (array $data, $record): void {
                            $days = (int) $data['days'];
                            $cost = Listing::featureCost($days);

                            try {
                                $record->featureWithPoints($days);

                                AuditLog::record('feature_ad', $record, [
                                    'title'          => $record->title,
                                    'days'           => $days,
                                    'points_cost'    => $cost,
                                    'featured_until' => $record->fresh()->featured_until?->format('Y-m-d'),
                                ]);

                                Notification::make()
                                    ->title('🌟 تم تمييز الإعلان')
                                    ->body("\"{$record->title}\" مميز {$days} أيام — خُصم {$cost} نقطة.")
                                    ->success()->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('❌ فشل التمييز')
                                    ->body($e->getMessage())
                                    ->danger()->send();
                            }
                        }),

                    // ══════════════════════════════════════════
                    // ❌ إلغاء التمييز
                    // ══════════════════════════════════════════
                    Action::make('unfeature_ad')
                        ->label('إلغاء التمييز')
                        ->icon('heroicon-m-x-mark')
                        ->color('gray')
                        ->visible(fn ($record) => $record->isCurrentlyFeatured())
                        ->requiresConfirmation()
                        ->modalHeading('إلغاء تمييز الإعلان')
                        ->modalDescription('هل تريد إلغاء التمييز؟ لن تُعاد النقاط.')
                        ->action(function ($record): void {
                            $record->unfeature();
                            AuditLog::record('unfeature_ad', $record, ['title' => $record->title]);
                            Notification::make()->title('تم إلغاء التمييز')->warning()->send();
                        }),

                    // ══════════════════════════════════════════
                    // 🚩 تحديد أمني
                    // ══════════════════════════════════════════
                    Action::make('flag')
                        ->label('تحديد أمني')
                        ->icon('heroicon-m-flag')
                        ->color('gray')
                        ->visible(fn ($record) => $record->status !== Listing::STATUS_FLAGGED)
                        ->form([
                            Select::make('flag_reason')
                                ->label('سبب التحديد')
                                ->options([
                                    'ethical'  => '🚫 مخالفة أخلاقية',
                                    'security' => '🔒 خطر أمني',
                                    'fraud'    => '⚠️ احتيال',
                                    'spam'     => '📢 سبام',
                                    'other'    => '❓ أخرى',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $record): void {
                            $record->flag(Auth::id(), $data['flag_reason']);
                            AuditLog::record('flag_ad', $record, [
                                'title'       => $record->title,
                                'flag_reason' => $data['flag_reason'],
                            ]);
                            Notification::make()
                                ->title('🚩 تم تحديد الإعلان للمراجعة الأمنية')
                                ->warning()->send();
                        }),

                ])->icon('heroicon-m-ellipsis-vertical'),
            ]);
    }

    // ══════════════════════════════════════════
    // إشعار المعلن
    // ══════════════════════════════════════════
    protected static function notifyUser(
        Listing $listing,
        string  $status,
        ?string $reason = null,
        ?string $note   = null
    ): void {
        try {
            $listing->user->notify(
                new ListingStatusNotification($listing, $status, $reason, $note)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                "Notify failed [{$listing->user->email}]: " . $e->getMessage()
            );
        }
    }
}