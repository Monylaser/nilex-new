<?php

namespace App\Filament\Admin\Resources\Listings\Pages;

use App\Filament\Admin\Resources\Listings\ListingResource;
use App\Models\Listing;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth; // 👈 ضفنا الـ Facade هنا

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
                // الزرار يختفي لو الإعلان أصلاً منشور
                ->visible(fn (Listing $record) => $record->status !== Listing::STATUS_PUBLISHED)
                ->action(function (Listing $record) {
                    // 👈 استخدمنا Auth::id() بدلاً من auth()->id()
                    $record->approve(Auth::id());
                    
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
                // الزرار يختفي لو الإعلان أصلاً مرفوض
                ->visible(fn (Listing $record) => $record->status !== Listing::STATUS_REJECTED)
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
                    // 👈 استخدمنا Auth::id() هنا كمان
                    $record->reject(Auth::id(), $data['reason']);

                    // 2. التحقق من الـ Strikes (المخالفات)
                    if ($record->rejectionCausesStrike($data['reason'])) {
                        $user = $record->user;
                        $user->addStrike(); 
                        
                        $msg = 'تم رفض الإعلان وتسجيل مخالفة (Strike) على المستخدم.';
                        
                        // لو اليوزر اتعمله حظر تلقائي
                        if ($user->strike_count >= 3) {
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