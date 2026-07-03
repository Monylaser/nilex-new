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

        $sellerId      = $data['seller_id'] ?? null;
        $paymentStatus = $data['payment_status'] ?? null;

        if ($sellerId === null && $paymentStatus === null) {
            $data['approval_status'] = 'approved';
        }

        return $data;
    }
}
