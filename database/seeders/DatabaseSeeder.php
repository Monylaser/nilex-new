<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. استدعاء الـ Seeders الخارجية أولاً
        $this->call([
            LocationSeeder::class,
            CategorySeeder::class,
        ]);

        // 2. إنشاء الأقسام (نفس كودك بالظبط)
        $categories = [
            [
                'name_ar' => 'عقارات',
                'name_en' => 'Real Estate',
                'slug' => 'real-estate',
            ],
            [
                'name_ar' => 'سيارات',
                'name_en' => 'Cars',
                'slug' => 'cars',
            ],
            [
                'name_ar' => 'إلكترونيات',
                'name_en' => 'Electronics',
                'slug' => 'electronics',
            ],
            [
                'name_ar' => 'خدمات',
                'name_en' => 'Services',
                'slug' => 'services',
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // 3. إنشاء مستخدم "أدمن" (نفس كودك بالظبط)
        User::updateOrCreate(
            ['email' => 'admin@nilex.com'], 
            [
                'name' => 'سنيور نايلكس',
                'password' => Hash::make('12345678'), 
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ تم زرع البيانات بنجاح: 4 أقسام وحساب أدمن جاهز.');
    }
}