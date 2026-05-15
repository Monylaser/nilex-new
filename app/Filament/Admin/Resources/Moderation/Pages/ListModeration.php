<?php

namespace App\Filament\Admin\Resources\Moderation\Pages;

use App\Filament\Admin\Resources\Moderation\ModerationResource;
use Filament\Resources\Pages\ListRecords;

// ✅ Filament v5: Tab class غير موجود — نستخدم الـ default ListRecords
// الفلترة بتتم عبر SelectFilter في الـ table مباشرةً

class ListModeration extends ListRecords
{
    protected static string $resource = ModerationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}