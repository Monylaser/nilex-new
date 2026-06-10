<?php

namespace App\Filament\Admin\Resources\CampaignLinks\Pages;

use App\Filament\Admin\Resources\CampaignLinks\CampaignLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCampaignLink extends EditRecord
{
    protected static string $resource = CampaignLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
