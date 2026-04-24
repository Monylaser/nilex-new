<?php

namespace App\Filament\Admin\Resources\Listings\Pages;

use App\Filament\Admin\Resources\Listings\ListingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class EditListing extends EditRecord
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    #[On('ai-ad-generated')]
    public function fillFromAI(array $data): void
    {
        $formData = [];

        if (!empty($data['title'])) {
            $formData['title'] = $data['title'];
            $formData['slug']  = Str::slug($data['title'], '-', 'ar');
        }

        if (!empty($data['description'])) {
            $formData['description'] = $data['description'];
        }

        if (!empty($data['price'])) {
            $formData['price'] = (float) $data['price'];
        }

        if (!empty($formData)) {
            $this->form->fill([
                ...$this->form->getState(),
                ...$formData,
            ]);

            Notification::make()
                ->title('تم تحديث البيانات بواسطة الـ AI ✨')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('فشل سحب البيانات')
                ->body('البيانات المستلمة من الـ AI غير مكتملة.')
                ->danger()
                ->send();
        }
    }
}