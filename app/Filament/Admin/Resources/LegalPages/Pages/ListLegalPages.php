<?php

namespace App\Filament\Admin\Resources\LegalPages\Pages;

use App\Filament\Admin\Resources\LegalPages\LegalPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use JeffersonGoncalves\FilamentTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListLegalPages extends ListRecords
{
    use Translatable;

    protected static string $resource = LegalPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة صفحة'),
        ];
    }
}
