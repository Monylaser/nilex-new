<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SiteSettingResource\Pages;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ColorPicker;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    public static function getModelLabel(): string
    {
        return 'إعدادات الموقع';
    }

    public static function getPluralModelLabel(): string
    {
        return 'إعدادات الموقع';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('معلومات الموقع')
                ->columns(2)
                ->schema([
                    TextInput::make('site_name')
                        ->label('اسم الموقع')
                        ->maxLength(255),

                    TextInput::make('site_email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->maxLength(255),

                    TextInput::make('site_phone')
                        ->label('رقم الهاتف')
                        ->maxLength(50),

                    Toggle::make('is_active')
                        ->label('الموقع نشط'),
                ]),

            Section::make('خلفية شاشة تسجيل الدخول')
                ->description('التحكم في الجانب الأيسر من شاشة اللوجن')
                ->columns(2)
                ->schema([

                    Select::make('auth_bg_type')
                        ->label('نوع الخلفية')
                        ->options([
                            'color' => '🎨 لون',
                            'image' => '🖼️ صورة',
                        ])
                        ->default('color')
                        ->live()
                        ->required(),

                    ColorPicker::make('auth_bg_color')
                        ->label('لون الخلفية')
                        ->default('#085041')
                        ->visible(fn ($get) => $get('auth_bg_type') === 'color'),

                    FileUpload::make('auth_bg_image')
                        ->label('صورة الخلفية')
                        ->image()
                        ->imageEditor()
                        ->directory('site-settings/auth-bg')
                        ->visibility('public')
                        ->maxSize(5120)
                        ->helperText('يُفضَّل أبعاد 1920×1080 أو أكبر')
                        ->columnSpanFull()
                        ->visible(fn ($get) => $get('auth_bg_type') === 'image'),

                    TextInput::make('auth_headline')
                        ->label('العنوان الرئيسي')
                        ->placeholder('منصة الإعلانات المبوبة الأولى في مصر')
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('auth_subtext')
                        ->label('النص الفرعي')
                        ->placeholder('اشترِ وبِع بكل سهولة وأمان')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('اللوجو والفافيكون')
                ->columns(2)
                ->schema([
                    FileUpload::make('site_logo')
                        ->label('لوجو الموقع')
                        ->image()
                        ->imageEditor()
                        ->directory('site-settings/logos')
                        ->visibility('public')
                        ->maxSize(2048),

                    FileUpload::make('site_favicon')
                        ->label('Favicon')
                        ->image()
                        ->directory('site-settings/favicons')
                        ->visibility('public')
                        ->maxSize(512),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site_name')
                    ->label('اسم الموقع')
                    ->placeholder('غير محدد'),

                TextColumn::make('auth_bg_type')
                    ->label('نوع خلفية اللوجن')
                    ->badge()
                    ->color(fn ($state) => $state === 'image' ? 'success' : 'info')
                    ->formatStateUsing(fn ($state) => $state === 'image' ? '🖼️ صورة' : '🎨 لون'),

                ToggleColumn::make('is_active')
                    ->label('نشط'),

                TextColumn::make('updated_at')
                    ->label('آخر تعديل')
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSiteSettings::route('/'),
            'create' => Pages\CreateSiteSetting::route('/create'),
            'edit'   => Pages\EditSiteSetting::route('/{record}/edit'),
        ];
    }
}