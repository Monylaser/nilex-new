<?php

namespace App\Filament\Admin\Resources\Listings\Pages;

use App\Filament\Admin\Resources\Listings\ListingResource;
use App\Filament\Admin\Resources\Listings\Support\ListingModeration;
use App\Models\Listing;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ViewListing extends ViewRecord
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. زرار الموافقة والنشر
            Actions\Action::make('approve')
                ->label('موافقة ونشر ✅')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                // الزرار يختفي لو الإعلان أصلاً منشور أو لا يملك صلاحية القبول
                ->visible(fn (Listing $record) => $record->status !== Listing::STATUS_PUBLISHED && (auth()->user()?->can('approve_listings') ?? false))
                ->action(function (Listing $record) {
                    // نفس المسار الكامل المستخدم في الجدول (AuditLog + إشعار المعلن)
                    ListingModeration::approve($record);

                    Notification::make()
                        ->title('تم نشر الإعلان بنجاح!')
                        ->success()
                        ->send();
                }),

            // 2. زرار الرفض والمخالفات
            Actions\Action::make('reject')
                ->label('رفض الإعلان ⛔')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                // الزرار يختفي لو الإعلان أصلاً مرفوض أو لا يملك صلاحية الرفض
                ->visible(fn (Listing $record) => $record->status !== Listing::STATUS_REJECTED && (auth()->user()?->can('reject_listings') ?? false))
                ->form([ // 👈 تحذير المحرر هنا كاذب وتقدر تتجاهله تماماً
                    Select::make('reason')
                        ->label('سبب الرفض')
                        ->options(Listing::rejectionReasonOptions())
                        ->required(),
                    Textarea::make('notes')
                        ->label('ملاحظات إضافية (تظهر للمستخدم)')
                        ->nullable(),
                ])
                ->action(function (array $data, Listing $record) {
                    // نفس المسار الكامل المستخدم في الجدول (AuditLog + إشعار + strikes)
                    $causesStrike = $record->rejectionCausesStrike($data['reason']);

                    ListingModeration::reject($record, $data['reason'], $data['notes'] ?? null);

                    if ($causesStrike) {
                        $msg = 'تم رفض الإعلان وتسجيل مخالفة (Strike) على المستخدم.';

                        if ($record->user->fresh()->strike_count >= 3) {
                            $msg .= ' 🚨 تم حظر المستخدم تلقائياً لتخطيه 3 مخالفات!';
                        }
                    } else {
                        $msg = 'تم رفض الإعلان (بدون مخالفة).';
                    }

                    Notification::make()
                        ->title('تم الرفض')
                        ->body($msg)
                        ->danger()
                        ->send();
                }),

            // زرار التعديل
            Actions\EditAction::make(),
        ];
    }
}