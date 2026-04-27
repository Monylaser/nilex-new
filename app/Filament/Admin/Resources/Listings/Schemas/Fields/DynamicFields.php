<?php

namespace App\Filament\Admin\Resources\Listings\Schemas\Fields;

use App\Models\Category;
use Filament\Forms\Components\Select    as FormSelect;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Forms\Components\RichEditor as FormRichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section as FormSection;

class DynamicFields
{
    // IDs الأقسام التي لها حقول ثابتة
    private const STATIC_CATEGORY_IDS = [
        1,  // عقارات
        12, // سيارات
        20, // إلكترونيات وأجهزة (موبايلات)
    ];

    public static function make(): FormSection
    {
        return FormSection::make('المواصفات الإضافية')
            ->visible(fn ($get) =>
                !blank($get('category_id')) &&
                !in_array((int) $get('category_id'), self::STATIC_CATEGORY_IDS)
            )
            ->schema(function ($get) {
                $category = Category::find($get('category_id'));

                if (!$category || empty($category->custom_fields_schema)) {
                    return [];
                }

                return collect($category->custom_fields_schema)
                    ->map(function ($field) {
                        $name = "custom_fields_values.{$field['name']}";

                        return match ($field['type'] ?? 'text') {
                            'number'   => FormTextInput::make($name)->label($field['label_ar'])->numeric(),
                            'textarea' => FormRichEditor::make($name)->label($field['label_ar']),
                            'boolean'  => Toggle::make($name)->label($field['label_ar']),
                            'select'   => FormSelect::make($name)
                                ->label($field['label_ar'])
                                ->options(collect($field['options'] ?? [])->pluck('label', 'value')->toArray()),
                            default    => FormTextInput::make($name)->label($field['label_ar']),
                        };
                    })
                    ->toArray();
            });
    }
}