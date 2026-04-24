<?php

namespace App\Filament\Admin\Resources\Categories\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput as NumberInput;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ── البيانات الأساسية ─────────────────────────────────────────
            Section::make('البيانات الأساسية')
                ->columns(2)
                ->schema([
                    TextInput::make('name_ar')
                        ->label('الاسم بالعربية')
                        ->required()
                        ->live(onBlur: true),

                    TextInput::make('name_en')
                        ->label('الاسم بالإنجليزية')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                    TextInput::make('slug')
                        ->label('الرابط')
                        ->disabled()
                        ->dehydrated()
                        ->required(),

                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(0),

                    Toggle::make('is_active')
                        ->label('نشط (ظاهر للمستخدمين)')
                        ->default(true)
                        ->columnSpanFull(),
                ]),

            // ── الأيقونة واللون ───────────────────────────────────────────
            Section::make('الأيقونة واللون')
                ->columns(2)
                ->schema([
                    // ✅ رفع صورة أيقونة من الكمبيوتر
                    FileUpload::make('icon')
                        ->label('أيقونة القسم')
                        ->image()
                        ->disk('public')
                        ->directory('category-icons')
                        ->imagePreviewHeight('80')
                        ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/jpeg', 'image/webp'])
                        ->maxSize(512)
                        ->helperText('PNG أو SVG أو JPG — الحجم الأقصى 512 كيلوبايت')
                        ->columnSpan(1),

                    ColorPicker::make('color')
                        ->label('لون القسم')
                        ->helperText('اختر لوناً مميزاً للقسم')
                        ->columnSpan(1),
                ]),

            // ── الحقول المخصصة ────────────────────────────────────────────
            Section::make('الحقول المخصصة')
                ->collapsible()
                ->schema([
                    Repeater::make('custom_fields_schema')
                        ->label('الحقول المخصصة')
                        ->schema([
                            TextInput::make('name')
                                ->label('اسم الحقل')
                                ->required(),
                            TextInput::make('label_ar')
                                ->label('التسمية بالعربية')
                                ->required(),
                            Select::make('type')
                                ->label('النوع')
                                ->options([
                                    'text'     => 'نص',
                                    'number'   => 'رقم',
                                    'select'   => 'قائمة منسدلة',
                                    'textarea' => 'نص طويل',
                                    'boolean'  => 'نعم / لا',
                                ])
                                ->required(),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('أضف حقلاً جديداً'),
                ]),
        ]);
    }
}