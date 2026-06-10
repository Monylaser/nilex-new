<?php

namespace App\Filament\Admin\Resources\LegalPages\Pages;

use App\Filament\Admin\Resources\LegalPages\LegalPageResource;
use Filament\Resources\Pages\CreateRecord;
use JeffersonGoncalves\FilamentTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateLegalPage extends CreateRecord
{
    use Translatable;

    protected static string $resource = LegalPageResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
