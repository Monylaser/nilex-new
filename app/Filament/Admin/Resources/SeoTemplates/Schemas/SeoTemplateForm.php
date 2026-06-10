<?php

namespace App\Filament\Admin\Resources\SeoTemplates\Schemas;

use App\Models\Category;
use App\Models\Location;
use App\Models\SeoTemplate;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class SeoTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('target_model')
                ->label('نوع الهدف')
                ->options(SeoTemplate::supportedModels())
                ->required()
                ->reactive()
                ->columnSpanFull(),

            Select::make('target_id')
                ->label('الهدف')
                ->required()
                ->columnSpanFull()
                ->options(function (callable $get) {
                    $model = $get('target_model');
                    if (! $model) {
                        return [];
                    }

                    return match ($model) {
                        Category::class => Category::query()
                            ->select('id', 'name_ar')
                            ->pluck('name_ar', 'id'),

                        Location::class => Location::query()
                            ->select('id', 'name_ar')
                            ->pluck('name_ar', 'id'),

                        default => [],
                    };
                })
                ->searchable()
                ->rules([
                    fn (callable $get, ?SeoTemplate $record) => Rule::unique('seo_templates', 'target_id')
                        ->where('target_model', $get('target_model'))
                        ->when($record?->id, fn ($rule) => $rule->ignore($record->id)),
                ])
                ->validationMessages([
                    'unique' => 'هذا القسم أو المحافظة له قالب SEO بالفعل',
                ]),

            TextInput::make('meta_title')
                ->label('Meta Title')
                ->maxLength(70)
                ->helperText('الحد الأقصى 70 حرف — يظهر في نتائج البحث.')
                ->columnSpanFull(),

            Textarea::make('meta_description')
                ->label('Meta Description')
                ->rows(3)
                ->maxLength(320)
                ->helperText('الحد الأقصى 320 حرف.')
                ->columnSpanFull(),

            TextInput::make('keywords')
                ->label('الكلمات المفتاحية')
                ->helperText('افصل بين الكلمات بفاصلة.')
                ->maxLength(500)
                ->columnSpanFull(),

            FileUpload::make('og_image')
                ->label('OG Image (صورة المشاركة)')
                ->helperText('الصورة التي تظهر عند مشاركة الرابط على وسائل التواصل الاجتماعي. الحجم الموصى به: 1200×630 بكسل.')
                ->image()
                ->imagePreviewHeight('120')
                ->disk('public')
                ->directory('seo/og-images')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->columnSpanFull(),

            TextInput::make('canonical_url')
                ->label('Canonical URL')
                ->url()
                ->maxLength(255),

            KeyValue::make('schema_markup')
                ->label('Schema Markup (JSON-LD)')
                ->helperText('أدخل بيانات Schema.org المنظّمة كأزواج مفتاح/قيمة.')
                ->columnSpanFull(),
        ]);
    }
}
