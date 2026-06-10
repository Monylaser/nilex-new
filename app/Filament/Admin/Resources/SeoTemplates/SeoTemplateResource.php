<?php

namespace App\Filament\Admin\Resources\SeoTemplates;

use App\Filament\Admin\Resources\SeoTemplates\Pages;
use App\Filament\Admin\Resources\SeoTemplates\Schemas\SeoTemplateForm;
use App\Filament\Admin\Resources\SeoTemplates\Tables\SeoTemplatesTable;
use App\Models\SeoTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class SeoTemplateResource extends Resource
{
    protected static ?string $model = SeoTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationLabel = 'قوالب SEO';

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'قالب SEO';

    protected static ?string $pluralModelLabel = 'قوالب SEO';

    public static function form(Schema $schema): Schema
    {
        return SeoTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeoTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSeoTemplates::route('/'),
            'create' => Pages\CreateSeoTemplate::route('/create'),
            'edit'   => Pages\EditSeoTemplate::route('/{record}/edit'),
        ];
    }
}
