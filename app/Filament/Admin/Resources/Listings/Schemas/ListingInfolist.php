<?php

namespace App\Filament\Admin\Resources\Listings\Schemas;

use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Read-only review layout for the admin "View listing" page.
 *
 * Replaces the previous behavior where the View page fell back to the editable
 * form schema (noisy: AI section, slug, user select, disabled inputs). This
 * gives moderators a clean review screen: key facts, the FULL description, and
 * a real image gallery read from the canonical 'images' collection.
 *
 * Admin-only — labels stay Arabic, consistent with the rest of app/Filament/*
 * (Phase D i18n deferred).
 */
class ListingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات الإعلان')
                ->columns(2)
                ->schema([
                    TextEntry::make('title')
                        ->label('العنوان')
                        ->weight('bold')
                        ->columnSpanFull(),

                    TextEntry::make('price')
                        ->label('السعر')
                        ->formatStateUsing(fn ($state) => number_format((float) $state) . ' ج.م'),

                    TextEntry::make('category.name_ar')
                        ->label('القسم')
                        ->badge()
                        ->color('info'),

                    TextEntry::make('user.name')
                        ->label('المعلن')
                        ->icon('heroicon-m-user'),

                    TextEntry::make('status')
                        ->label('الحالة')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending'   => 'warning',
                            'published' => 'success',
                            'rejected'  => 'danger',
                            'flagged'   => 'gray',
                            default     => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'pending'   => 'قيد المراجعة',
                            'published' => 'منشور',
                            'rejected'  => 'مرفوض',
                            'flagged'   => 'مُبلَّغ عنه',
                            default     => $state,
                        }),

                    TextEntry::make('province.name_ar')
                        ->label('المحافظة')
                        ->placeholder('—'),

                    TextEntry::make('created_at')
                        ->label('تاريخ الإضافة')
                        ->dateTime('d M Y'),
                ]),

            Section::make('وصف الإعلان')
                ->schema([
                    TextEntry::make('description')
                        ->label('')
                        ->html()
                        ->placeholder('لا يوجد وصف')
                        ->columnSpanFull(),
                ]),

            Section::make('صور الإعلان')
                ->schema([
                    SpatieMediaLibraryImageEntry::make('images')
                        ->label('')
                        ->collection('images')
                        ->conversion('card')
                        ->placeholder('لا توجد صور')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
