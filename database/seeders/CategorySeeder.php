<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // مسح القديم
        Category::truncate();

        $categories = [
            ['name_ar' => 'عقارات',               'name_en' => 'Real Estate',        'color' => '#10b981', 'sort' => 1],
            ['name_ar' => 'سيارات',
    'slug' => 'cars',
    'custom_fields_schema' => [
        ['name' => 'engine_cc', 'label_ar' => 'سعة المحرك (CC)', 'type' => 'number'],
        ['name' => 'year', 'label_ar' => 'سنة الصنع', 'type' => 'number'],
        ['name' => 'model', 'label_ar' => 'الموديل', 'type' => 'text'],
    ],
    'is_active' => true, 2],
            ['name_ar' => 'إلكترونيات وأجهزة',   'name_en' => 'Electronics',        'color' => '#8b5cf6', 'sort' => 3],
            ['name_ar' => 'أثاث ومنزل',           'name_en' => 'Furniture & Home',   'color' => '#f59e0b', 'sort' => 4],
            ['name_ar' => 'ملابس وموضة',          'name_en' => 'Fashion',            'color' => '#ec4899', 'sort' => 5],
            ['name_ar' => 'وظائف',                'name_en' => 'Jobs',               'color' => '#06b6d4', 'sort' => 6],
            ['name_ar' => 'خدمات',                'name_en' => 'Services',           'color' => '#64748b', 'sort' => 7],
            ['name_ar' => 'حيوانات أليفة',        'name_en' => 'Pets',               'color' => '#84cc16', 'sort' => 8],
            ['name_ar' => 'أطفال ومستلزماتهم',   'name_en' => 'Kids & Baby',        'color' => '#f97316', 'sort' => 9],
            ['name_ar' => 'رياضة وترفيه',         'name_en' => 'Sports & Leisure',   'color' => '#ef4444', 'sort' => 10],
            ['name_ar' => 'مواد البناء والتشييد', 'name_en' => 'Building Materials', 'color' => '#92400e', 'sort' => 11],
            ['name_ar' => 'صناعة وأعمال',         'name_en' => 'Business',           'color' => '#1e40af', 'sort' => 12],
            ['name_ar' => 'منوعات',               'name_en' => 'Miscellaneous',      'color' => '#6b7280', 'sort' => 13],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'name_ar'    => $cat['name_ar'],
                'name_en'    => $cat['name_en'],
                'slug'       => Str::slug($cat['name_en']),
                'color'      => $cat['color'],
                'icon'       => null,
                'is_active'  => true,
                'sort_order' => $cat['sort'],
                'parent_id'  => null,
            ]);
        }
    }
}