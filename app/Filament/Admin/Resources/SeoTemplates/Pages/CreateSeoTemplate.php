<?php

namespace App\Filament\Admin\Resources\SeoTemplates\Pages;

use App\Filament\Admin\Resources\SeoTemplates\SeoTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSeoTemplate extends CreateRecord
{
    protected static string $resource = SeoTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
