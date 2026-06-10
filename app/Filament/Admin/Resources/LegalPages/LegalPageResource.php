<?php

namespace App\Filament\Admin\Resources\LegalPages;

use App\Filament\Admin\Resources\LegalPages\Pages;
use App\Filament\Admin\Resources\LegalPages\Schemas\LegalPageForm;
use App\Filament\Admin\Resources\LegalPages\Tables\LegalPagesTable;
use App\Models\LegalPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use JeffersonGoncalves\FilamentTranslatable\Resources\Concerns\Translatable;
use UnitEnum;

class LegalPageResource extends Resource
{
    use Translatable;

    protected static ?string $model = LegalPage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'الصفحات القانونية';

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'صفحة قانونية';

    protected static ?string $pluralModelLabel = 'الصفحات القانونية';

    public static function form(Schema $schema): Schema
    {
        return LegalPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegalPagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLegalPages::route('/'),
            'create' => Pages\CreateLegalPage::route('/create'),
            'edit'   => Pages\EditLegalPage::route('/{record}/edit'),
        ];
    }
}
