<?php

namespace App\Filament\Admin\Resources\Listings\Pages;

use App\Filament\Admin\Resources\Listings\ListingResource;
use App\Models\Listing;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListListings extends ListRecords
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->badge(Listing::count()),

            'pending' => Tab::make('قيد المراجعة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Listing::STATUS_PENDING))
                ->badge(Listing::where('status', Listing::STATUS_PENDING)->count())
                ->badgeColor('warning'),

            'published' => Tab::make('منشور')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Listing::STATUS_PUBLISHED))
                ->badge(Listing::where('status', Listing::STATUS_PUBLISHED)->count())
                ->badgeColor('success'),

            'flagged_auto' => Tab::make('مُبلَّغ تلقائياً 🚩')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_flagged', true))
                ->badge(Listing::where('is_flagged', true)->count())
                ->badgeColor('danger'),

            'rejected' => Tab::make('مرفوض')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Listing::STATUS_REJECTED))
                ->badge(Listing::where('status', Listing::STATUS_REJECTED)->count())
                ->badgeColor('danger'),
        ];
    }
}
