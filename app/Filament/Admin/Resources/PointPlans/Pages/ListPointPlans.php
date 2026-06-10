<?php

namespace App\Filament\Admin\Resources\PointPlans\Pages;

use App\Filament\Admin\Resources\PointPlans\PointPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPointPlans extends ListRecords
{
    protected static string $resource = PointPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
