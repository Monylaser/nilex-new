<?php

namespace App\Filament\Admin\Resources\PointPlans\Pages;

use App\Filament\Admin\Resources\PointPlans\PointPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPointPlan extends EditRecord
{
    protected static string $resource = PointPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
