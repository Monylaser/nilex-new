<?php

namespace App\Filament\Admin\Resources\LegalPages\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LegalPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ── البيانات الأساسية ─────────────────────────────────────────
            Section::make('البيانات الأساسية')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('عنوان الصفحة')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) =>
                            $set('slug', Str::slug($state))
                        )
                        ->columnSpanFull(),

                    TextInput::make('slug')
                        ->label('الرابط (Slug)')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('يُستخدم في رابط الصفحة: /pages/...'),

                    Toggle::make('is_active')
                        ->label('نشطة (ظاهرة للزوار)')
                        ->default(true),
                ]),

            // ── المحتوى ────────────────────────────────────────────────────
            Section::make('محتوى الصفحة')
                ->schema([
                    RichEditor::make('content')
                        ->label('المحتوى')
                        ->required()
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike',
                            'h2', 'h3',
                            'bulletList', 'orderedList',
                            'blockquote',
                            'link',
                            'undo', 'redo',
                        ])
                        ->columnSpanFull(),
                ]),

            // ── SEO ────────────────────────────────────────────────────────
            Section::make('إعدادات SEO')
                ->collapsible()
                ->collapsed()
                ->columns(1)
                ->schema([
                    TextInput::make('seo_title')
                        ->label('عنوان SEO')
                        ->maxLength(70)
                        ->helperText('اتركه فارغاً لاستخدام عنوان الصفحة تلقائياً'),

                    Textarea::make('seo_description')
                        ->label('وصف SEO')
                        ->rows(3)
                        ->maxLength(160)
                        ->helperText('يُعرض في نتائج محركات البحث (160 حرفاً كحد أقصى)'),

                    TextInput::make('meta_keywords')
                        ->label('الكلمات المفتاحية')
                        ->maxLength(255)
                        ->helperText('افصل بين الكلمات بفاصلة'),
                ]),
        ]);
    }
}
