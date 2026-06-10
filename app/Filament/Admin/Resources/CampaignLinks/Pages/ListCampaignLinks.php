<?php

namespace App\Filament\Admin\Resources\CampaignLinks\Pages;

use App\Filament\Admin\Resources\CampaignLinks\CampaignLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCampaignLinks extends ListRecords
{
    protected static string $resource = CampaignLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
