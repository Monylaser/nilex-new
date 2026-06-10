<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // مسح الكاش المؤقت لـ Spatie لضمان تطبيق الأذونات فوراً
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── الأذونات ─────────────────────────────────────────────────────────

        $permissions = [
            'view_listings',    // عرض الإعلانات
            'approve_listings', // قبول الإعلانات
            'reject_listings',  // رفض الإعلانات
            'view_users',       // عرض المستخدمين
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── الأدوار ──────────────────────────────────────────────────────────

        // super_admin: صلاحيات كاملة بدون قيود (Filament Shield يتعامل معه خصيصاً)
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        // moderator: أذونات محددة فقط
        $moderator = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'web']);
        $moderator->syncPermissions($permissions);

        // ── تعيين super_admin للمستخدم رقم 1 (أو أول مستخدم موجود كبديل) ──────

        $user = User::find(1) ?? User::where('email', 'admin@nilex.com')->first() ?? User::orderBy('id')->first();

        if ($user) {
            $user->assignRole($superAdmin);
            $this->command->info("✅ تم تعيين دور super_admin للمستخدم: {$user->name} (ID: {$user->id})");
        } else {
            $this->command->warn('⚠️  لا يوجد أي مستخدم في قاعدة البيانات — تخطّي تعيين الدور.');
        }

        $this->command->info('✅ تم إنشاء الأدوار والأذونات بنجاح.');
    }
}
