<?php

namespace App\Filament\Admin\Resources\Listings;

use App\Models\Listing;
use App\Filament\Admin\Resources\Listings\Schemas\ListingForm;
use App\Filament\Admin\Resources\Listings\Schemas\ListingInfolist;
use App\Filament\Admin\Resources\Listings\Schemas\ListingTable;
use App\Filament\Admin\Resources\Listings\Pages\CreateListing;
use App\Filament\Admin\Resources\Listings\Pages\EditListing;
use App\Filament\Admin\Resources\Listings\Pages\ListListings;
use App\Filament\Admin\Resources\Listings\Pages\ViewListing;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    // ✅ Filament v5: navigationIcon = string|BackedEnum|null
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    // ✅ Filament v5: navigationGroup = string|UnitEnum|null
    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?string $navigationLabel = 'الإعلانات';
    protected static ?int    $navigationSort  = 3;

    // ✅ Filament v5: form يستقبل Schema — مش Form
    public static function form(Schema $schema): Schema
    {
        return ListingForm::configure($schema);
    }

    // ✅ صفحة العرض تستخدم infolist مخصص للمراجعة (gallery + وصف كامل)
    public static function infolist(Schema $schema): Schema
    {
        return ListingInfolist::configure($schema);
    }

    // ✅ table يستقبل Table — طبيعي
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