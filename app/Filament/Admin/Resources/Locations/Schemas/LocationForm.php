<?php

namespace App\Filament\Admin\Resources\Locations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required()
                    ->maxLength(255),

                TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->required()
                    ->maxLength(255),

                Select::make('level')
                    ->label('المستوى')
                    ->options([
                        0 => 'محافظة',
                        1 => 'مدينة',
                    ])
                    ->required(),

                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ]);
    }
}
