<?php

namespace App\Filament\Admin\Resources\LegalPages\Pages;

use App\Filament\Admin\Resources\LegalPages\LegalPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use JeffersonGoncalves\FilamentTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditLegalPage extends EditRecord
{
    use Translatable;

    protected static string $resource = LegalPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
