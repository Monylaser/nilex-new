<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء حساب أدمن (عشان تدخل بيه علطول)
        // كلمة سر الأدمن تُقرأ من .env؛ وفي غيابها تُولَّد عشوائية (غير ثابتة في الكود).
        User::updateOrCreate(
            ['email' => 'admin@nilex.com'], // الإيميل اللي هتدخل بيه
            [
                'name' => 'Admin Nilex',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', Str::random(32))), // الباسوورد
                'email_verified_at' => now(),
            ]
        );

        // 2. إنشاء أقسام تجريبية (عشان الفورم تظهر فيها بيانات)
        $categories = [
            ['name' => 'عقارات', 'slug' => 'real-estate'],
            ['name' => 'سيارات', 'slug' => 'cars'],
            ['name' => 'إلكترونيات', 'slug' => 'electronics'],
            ['name' => 'خدمات', 'slug' => 'services'],
            ['name' => 'وظائف', 'slug' => 'jobs'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        $this->command->info('✅ تم إنشاء الأدمن والأقسام بنجاح!');
    }
}
