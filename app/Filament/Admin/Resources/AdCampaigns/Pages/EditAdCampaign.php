<?php

namespace App\Filament\Admin\Resources\AdCampaigns\Pages;

use App\Filament\Admin\Resources\AdCampaigns\AdCampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdCampaign extends EditRecord
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
