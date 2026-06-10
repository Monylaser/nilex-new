<?php

namespace App\Filament\Admin\Resources\Campaigns;

use App\Filament\Admin\Resources\Campaigns\Pages;
use App\Filament\Admin\Resources\Campaigns\Schemas\CampaignForm;
use App\Filament\Admin\Resources\Campaigns\Tables\CampaignsTable;
use App\Models\Campaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'الحملات الإعلانية';

    protected static string|UnitEnum|null $navigationGroup = 'الإدارة';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'حملة';

    protected static ?string $pluralModelLabel = 'الحملات الإعلانية';

    public static function form(Schema $schema): Schema
    {
        return CampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit'   => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
