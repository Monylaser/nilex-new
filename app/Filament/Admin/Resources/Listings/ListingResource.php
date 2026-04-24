<?php

namespace App\Filament\Admin\Resources\Listings;

use App\Models\Listing;
use App\Models\Location;
use App\Models\Category;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section  as FormSection;
use Filament\Schemas\Components\Actions  as FormActions;
use Filament\Actions\Action              as FormAction;
use Filament\Forms\Components\TextInput  as FormTextInput;
use Filament\Forms\Components\Select     as FormSelect;
use Filament\Forms\Components\RichEditor as FormRichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Repeater   as FormRepeater;
use Filament\Forms\Components\FileUpload as FormFileUpload;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use BackedEnum;

use App\Filament\Admin\Resources\Listings\Pages\CreateListing;
use App\Filament\Admin\Resources\Listings\Pages\EditListing;
use App\Filament\Admin\Resources\Listings\Pages\ListListings;
use App\Filament\Admin\Resources\Listings\Pages\ViewListing;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'الإعلانات';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── AI Assistant — بدون modal، يشتغل مباشرة ──────────────────────
            FormSection::make('AI Assistant ✨')
              ->description('ارفع الصور أولاً ثم اضغط الزرار')
              ->icon('heroicon-m-sparkles')
              ->schema([
             FormActions::make([
             FormAction::make('generateWithAI')
                ->label('ولّد الإعلان بالـ AI ✨')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->action(function () {
                    // الـ action هيتنفذ في الـ Page مباشرة
                        }),
                 ])->alignCenter(),
             ])->columnSpanFull()->collapsible(),
            // ── بيانات الإعلان الأساسية ───────────────────────────────────────
            FormSection::make('بيانات الإعلان الأساسية')
                ->columns(2)
                ->schema([
                    FormTextInput::make('title')
                        ->label('عنوان الإعلان')
                        ->id('ad-title')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state, '-', 'ar'))),

                    FormTextInput::make('slug')
                        ->label('رابط الإعلان (Slug)')
                        ->id('ad-slug')
                        ->required()
                        ->unique(Listing::class, 'slug', ignoreRecord: true),

                    FormSelect::make('category_id')
                        ->label('القسم')
                        ->id('ad-category')
                        ->options(fn () => Category::where('is_active', true)
                        ->required()
                        ->live() // يجعل الفورم يتفاعل لحظياً عند تغيير القسم
                        ->orderBy('sort_order')
                        ->pluck('name_ar', 'id')
                        ->toArray()
                        ->afterStateUpdated(fn ($set) => $set('custom_fields_values', [])), // تصغير القيم عند تغيير القسم
                        )
                        ->searchable()
                        ->preload()
                        ->required(),

                    FormTextInput::make('price')
                        ->label('السعر')
                        ->id('ad-price')
                        ->numeric()
                        ->prefix('ج.م'),
                ]),

            // ── وصف الإعلان ───────────────────────────────────────────────────
            FormSection::make('وصف الإعلان')
                ->schema([
                    FormRichEditor::make('description')
                        ->label('تفاصيل الإعلان')
                        ->id('ad-description')
                        ->required()
                        ->columnSpanFull(),
                ]),

            // ── الموقع والمعلن ────────────────────────────────────────────────
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
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('location_id', null))
                        ->required(),

                    FormSelect::make('location_id')
                        ->label('المدينة')
                        ->options(function ($get) {
                            $provinceId = $get('province_id');
                            if (blank($provinceId)) return [];
                            return Location::where('parent_id', $provinceId)
                                ->where('level', Location::LEVEL_CITY)
                                ->orderBy('sort_order')
                                ->pluck('name_ar', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->live()
                        ->disabled(fn ($get) => blank($get('province_id')))
                        ->helperText('اختر المحافظة أولاً')
                        ->required(),

                    FormSelect::make('user_id')
                        ->label('المعلن')
                        ->relationship('user', 'name')
                        ->default(fn () => Auth::id())
                        ->required(),
                ]),
                // ✨ السحر هنا: الحقول الديناميكية بناءً على القسم المختبر ✨
        FormSection::make('المواصفات الإضافية')
            ->description('حقول مخصصة تظهر بناءً على القسم المختار')
            ->visible(fn ($get) => !blank($get('category_id'))) // تظهر فقط لو اخترنا قسم
            ->schema(function ($get) {
                $categoryId = $get('category_id');
                $category = Category::find($categoryId);

                if (!$category || !$category->custom_fields_schema) {
                    return [
                        \Filament\Infolists\Components\TextEntry::make('no_fields')
                            ->label('لا توجد مواصفات إضافية لهذا القسم.')
                    ];
                }

                $fields = [];
                foreach ($category->custom_fields_schema as $field) {
                    // تحويل الـ Schema لمكونات Filament حقيقية
                    $fields[] = match ($field['type']) {
                        'text'     => FormTextInput::make("custom_fields_values.{$field['name']}")->label($field['label_ar']),
                        'number'   => FormTextInput::make("custom_fields_values.{$field['name']}")->label($field['label_ar'])->numeric(),
                        'textarea' => FormRichEditor::make("custom_fields_values.{$field['name']}")->label($field['label_ar']),
                        'boolean'  => \Filament\Forms\Components\Toggle::make("custom_fields_values.{$field['name']}")->label($field['label_ar']),
                        default    => FormTextInput::make("custom_fields_values.{$field['name']}")->label($field['label_ar']),
                    };
                }
                return $fields;
            }),

            // ── معرض الصور ───────────────────────────────────────────────────
            FormSection::make('معرض الصور')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('images')
                        ->label('الصور الرئيسية')
                        ->collection('listings')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->columnSpanFull(),

                    FormRepeater::make('extra_images')
                        ->label('صور إضافية')
                        ->schema([
                            FormFileUpload::make('image')
                                ->label('تحميل صورة')
                                ->disk('public')
                                ->directory('extra-listings')
                                ->image(),
                        ])
                        ->addActionLabel('أضف صورة أخرى')
                        ->collapsible()
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('listing_image')
                    ->label('الصورة')
                    ->collection('listings'),
                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->description(function (Listing $record): ?string {
        // لو مفيش قيم ديناميكية، نرجع null
        if (!$record->custom_fields_values) {
            return null;
        }

        // هنجمع أول 3 قيم موجودة ونعرضهم كـ ملخص (Description) تحت العنوان
        $summary = [];
        foreach ($record->custom_fields_values as $key => $value) {
            if (!empty($value)) {
                // بنحاول نجيب اسم الحقل بالعربي من الـ Schema بتاع القسم
                $fieldSchema = collect($record->category->custom_fields_schema ?? [])
                    ->firstWhere('name', $key);
                
                $label = $fieldSchema['label_ar'] ?? $key;
                
                // لو القيمة true/false (بتاعة الـ Toggle) نحولها لنص
                $displayValue = is_bool($value) ? ($value ? 'نعم' : 'لا') : $value;
                
                $summary[] = "{$label}: {$displayValue}";
            }
            
            // نكتفي بظهور أول 3 مواصفات بس عشان الزحمة
            if (count($summary) >= 3) break;
        }

        return implode(' | ', $summary);
    })
    ->wrap(),
                TextColumn::make('category.name_ar')
                    ->label('القسم'),
                TextColumn::make('price')
                    ->label('السعر')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state) . ' ج.م'),
                TextColumn::make('province.name_ar')
                    ->label('المحافظة'),
                TextColumn::make('location.name_ar')
                    ->label('المدينة'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListListings::route('/'),
            'create' => CreateListing::route('/create'),
            'view'   => ViewListing::route('/{record}'),
            'edit'   => EditListing::route('/{record}/edit'),
        ];
    }
}