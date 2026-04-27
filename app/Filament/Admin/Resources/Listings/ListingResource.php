<?php

namespace App\Filament\Admin\Resources\Listings;

use App\Models\Listing;
use App\Filament\Admin\Resources\Listings\Schemas\ListingForm;
use App\Filament\Admin\Resources\Listings\Schemas\ListingTable;
use App\Filament\Admin\Resources\Listings\Pages\CreateListing;
use App\Filament\Admin\Resources\Listings\Pages\EditListing;
use App\Filament\Admin\Resources\Listings\Pages\ListListings;
use App\Filament\Admin\Resources\Listings\Pages\ViewListing;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'الإعلانات';

    public static function form(Schema $schema): Schema
    {
        return ListingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ListingTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListListings::route('/'),
            'create' => CreateListing::route('/create'),
            'view'   => ViewListing::route('/{record}'),
            'edit'   => EditListing::route('/{record}/edit'),
        ];
    }
}