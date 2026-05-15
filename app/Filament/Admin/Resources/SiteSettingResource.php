<?php

namespace App\Filament\Admin\Resources;

// ── Core ──────────────────────────────────────────────────────────────────────
use App\Filament\Admin\Resources\SiteSettingResource\Pages;
use App\Models\SiteSetting;
use BackedEnum;

// ── Filament Resource ─────────────────────────────────────────────────────────
use Filament\Resources\Resource;

// ── Schema (Filament v5 — replaces Filament\Forms\Form) ──────────────────────
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

// ── Form Components ───────────────────────────────────────────────────────────
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

// ── Table ─────────────────────────────────────────────────────────────────────
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;

// ── Actions (Filament v5) ─────────────────────────────────────────────────────
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getModelLabel(): string
    {
        return __('Site Setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Site Settings');
    }

    // ── Form (Filament v5: Schema instead of Form) ────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('General Information'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')
                            ->label(__('Site Name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('site_email')
                            ->label(__('Site Email'))
                            ->email()
                            ->maxLength(255),

                        TextInput::make('site_phone')
                            ->label(__('Phone'))
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('site_url')
                            ->label(__('Site URL'))
                            ->url()
                            ->maxLength(255),

                        Textarea::make('site_description')
                            ->label(__('Description'))
                            ->columnSpanFull()
                            ->rows(3)
                            ->maxLength(500),
                    ]),

                Section::make(__('Logo & Favicon'))
                    ->columns(2)
                    ->schema([
                        FileUpload::make('site_logo')
                            ->label(__('Site Logo'))
                            ->image()
                            ->imageEditor()
                            ->directory('site-settings/logos')
                            ->visibility('public')
                            ->maxSize(2048),

                        FileUpload::make('site_favicon')
                            ->label(__('Favicon'))
                            ->image()
                            ->directory('site-settings/favicons')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/x-icon', 'image/png', 'image/svg+xml'])
                            ->maxSize(512),
                    ]),

                Section::make(__('Status'))
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true),
                    ]),
            ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('site_logo')
                    ->label(__('Logo'))
                    ->height(40)
                    ->circular(),

                TextColumn::make('site_name')
                    ->label(__('Site Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('site_email')
                    ->label(__('Email'))
                    ->searchable(),

                TextColumn::make('site_phone')
                    ->label(__('Phone')),

                ToggleColumn::make('is_active')
                    ->label(__('Active')),

                TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSiteSettings::route('/'),
            'create' => Pages\CreateSiteSetting::route('/create'),
            'edit'   => Pages\EditSiteSetting::route('/{record}/edit'),
        ];
    }
}