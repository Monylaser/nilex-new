<?php

namespace App\Filament\Admin\Resources\UserResource\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('email_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('avatar')
                    ->placeholder('-'),
                TextEntry::make('phone'),
                TextEntry::make('phone_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('points')
                    ->numeric(),
                TextEntry::make('trust_score')
                    ->numeric(),
                TextEntry::make('device_id')
                    ->placeholder('-'),
                IconEntry::make('is_banned')
                    ->boolean(),
                TextEntry::make('ban_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
