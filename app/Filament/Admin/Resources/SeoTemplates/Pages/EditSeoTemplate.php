<?php

namespace App\Filament\Admin\Resources\SeoTemplates\Pages;

use App\Filament\Admin\Resources\SeoTemplates\SeoTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSeoTemplate extends EditRecord
{
    protected static string $resource = SeoTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
