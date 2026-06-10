<?php

namespace App\Filament\Admin\Resources\Locations;

use App\Filament\Admin\Resources\Locations\Pages\CreateLocation;
use App\Filament\Admin\Resources\Locations\Pages\EditLocation;
use App\Filament\Admin\Resources\Locations\Pages\ListLocations;
use App\Filament\Admin\Resources\Locations\Pages\ViewLocation;
use App\Filament\Admin\Resources\Locations\RelationManagers\ChildrenRelationManager;
use App\Filament\Admin\Resources\Locations\Schemas\LocationForm;
use App\Filament\Admin\Resources\Locations\Tables\LocationsTable;
use App\Models\Location;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as FilamentSchema;
use Filament\Tables\Table;
use UnitEnum;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'المحافظات والمدن';

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'موقع';

    protected static ?string $pluralModelLabel = 'المواقع الجغرافية';

    protected static ?string $recordTitleAttribute = 'name_ar';

    public static function form(FilamentSchema $schema): FilamentSchema
    {
        return LocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationsTable::configure($table);
    }

    // إظهار المحافظات فقط في القائمة الرئيسية — المدن تُدار من داخل كل محافظة
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('level', Location::LEVEL_GOVERNORATE);
    }

    // تسجيل مدير علاقة المدن التابعة للمحافظة
    public static function getRelations(): array
    {
        return [
            ChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'edit'   => EditLocation::route('/{record}/edit'),
            'view'   => ViewLocation::route('/{record}'),
        ];
    }
}
