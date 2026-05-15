<?php

namespace App\Filament\Admin\Resources\Listings\Schemas;

use App\Models\Listing;
use App\Models\Location;
use App\Models\Category;
use App\Filament\Admin\Resources\Listings\Schemas\Fields\CarFields;
use App\Filament\Admin\Resources\Listings\Schemas\Fields\RealEstateFields;
use App\Filament\Admin\Resources\Listings\Schemas\Fields\DynamicFields;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Actions as FormActions;
use Filament\Actions\Action as FormAction;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\RichEditor as FormRichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ── AI Assistant ──────────────────────────────────────────────────
            FormSection::make('AI Assistant ✨')
                ->description('ارفع الصور أولاً ثم اضغط الزرار')
                ->icon('heroicon-m-sparkles')
                ->schema([
                    FormActions::make([
                        FormAction::make('generateWithAI')
                            ->label('ولّد الإعلان بالـ AI ✨')
                            ->icon('heroicon-m-sparkles')
                            ->color('success')
                            ->action(fn () => null),
                    ])->alignCenter(),
                ])->columnSpanFull()->collapsible(),

            // ── بيانات الإعلان الأساسية ───────────────────────────────────────
            FormSection::make('بيانات الإعلان الأساسية')
                ->columns(2)
                ->schema([
                    FormTextInput::make('title')
                        ->label('عنوان الإعلان')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state, '-', 'ar'))),

                    FormTextInput::make('slug')
                        ->label('رابط الإعلان (Slug)')
                        ->required()
                        ->unique(Listing::class, 'slug', ignoreRecord: true),

                    FormSelect::make('category_id')
                        ->label('القسم')
                        ->options(fn () => Category::whereNull('parent_id')
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->pluck('name_ar', 'id')
                            ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),

                    FormTextInput::make('price')
                        ->label('السعر')
                        ->numeric()
                        ->prefix('ج.م'),
                ]),

            // ── الموقع والمعلن (تم الرفع هنا لضمان مساحة القوائم المنسدلة) ───────────
            FormSection::make('الموقع والمعلن')
                ->columns(3)
                ->schema([
                    FormSelect::make('province_id')
                        ->label('المحافظة')
                        ->options(fn () => Location::where('level', Location::LEVEL_GOVERNORATE)
                            ->orderBy('sort_order')
                            ->pluck('name_ar', 'id')
                            ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('location_id', null))
                        ->required(),

                    FormSelect::make('location_id')
                        ->label('المدينة')
                        ->options(fn ($get) => Location::where('parent_id', $get('province_id'))
                            ->where('level', Location::LEVEL_CITY)
                            ->orderBy('sort_order')
                            ->pluck('name_ar', 'id')
                            ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($get) => blank($get('province_id')))
                        ->helperText('اختر المحافظة أولاً')
                        ->required(),

                    FormSelect::make('user_id')
                        ->label('المعلن')
                        ->relationship('user', 'name')
                        ->default(fn () => Auth::id())
                        ->required(),
                ]),

            // ── حقول ديناميكية حسب القسم ─────────────────────────────────────
            CarFields::make(),
            RealEstateFields::make(),
            DynamicFields::make(),

            // ── وصف الإعلان ───────────────────────────────────────────────────
            FormSection::make('وصف الإعلان')
                ->schema([
                    FormRichEditor::make('description')
                        ->label('تفاصيل الإعلان')
                        ->required()
                        ->columnSpanFull(),
                ]),

            // ── صور الإعلان ───────────────────────────────────────────────────
            FormSection::make('صور الإعلان')
                ->description('اسحب الصور لترتيبها. الصورة الأولى هي الغلاف.')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('images')
                        ->label('')
                        ->collection('listings')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->imageEditor()
                        ->maxSize(2048)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}