<?php

namespace App\Filament\Admin\Resources\Locations;

use App\Filament\Admin\Resources\Locations\Pages\CreateLocation;
use App\Filament\Admin\Resources\Locations\Pages\EditLocation;
use App\Filament\Admin\Resources\Locations\Pages\ListLocations;
use App\Filament\Admin\Resources\Locations\Schemas\LocationForm;
use App\Filament\Admin\Resources\Locations\Tables\LocationsTable;
use App\Models\Location;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as FilamentSchema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name_ar';

    public static function form(FilamentSchema $schema): FilamentSchema
    {
        return LocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }
}
