<?php

namespace App\Filament\Admin\Resources\SeoTemplates\Pages;

use App\Filament\Admin\Resources\SeoTemplates\SeoTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSeoTemplates extends ListRecords
{
    protected static string $resource = SeoTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
