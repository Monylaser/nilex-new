<?php

namespace App\Filament\Admin\Resources\Locations\Pages;

use App\Filament\Admin\Resources\Locations\LocationResource;
use Filament\Actions\CreateAction; // تأكد إن ده موجود
use Filament\Resources\Pages\ListRecords;

class ListLocations extends ListRecords
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // استعمل الكلاس مباشرة طالما عملت له use فوق
            CreateAction::make(),
        ];
    }
}
