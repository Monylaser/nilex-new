<?php

namespace App\Filament\Admin\Resources\AdCampaigns\Pages;

use App\Filament\Admin\Resources\AdCampaigns\AdCampaignResource;
use App\Models\AdCampaign;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAdCampaigns extends ListRecords
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $pendingCount = AdCampaign::query()->where('approval_status', 'pending')->count();

        return [
            'all' => Tab::make('الكل')
                ->badge(AdCampaign::count()),

            'pending' => Tab::make('قيد المراجعة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approval_status', 'pending'))
                ->badge($pendingCount)
                ->badgeColor('warning'),
        ];
    }
}
