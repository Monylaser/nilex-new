<?php

namespace App\Filament\Admin\Resources\AdCampaigns\Pages;

use App\Filament\Admin\Resources\AdCampaigns\AdCampaignResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAdCampaign extends CreateRecord
{
    protected static string $resource = AdCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
