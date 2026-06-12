<?php

declare(strict_types=1);

/**
 * Restore super_admin Filament navigation after seed/migrate.
 * Ensures role assignment and clears permission/config caches.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

$admin = User::where('email', 'admin@nilex.com')->first() ?? User::orderBy('id')->first();

if (! $admin) {
    fwrite(STDERR, "No admin user found. Run: php artisan db:seed\n");
    exit(1);
}

$role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

if (! $admin->hasRole('super_admin')) {
    $admin->assignRole($role);
    echo "Assigned super_admin to user #{$admin->id} ({$admin->email})\n";
} else {
    echo "User #{$admin->id} already has super_admin\n";
}

app(PermissionRegistrar::class)->forgetCachedPermissions();
\Illuminate\Support\Facades\Artisan::call('config:clear');
\Illuminate\Support\Facades\Artisan::call('cache:clear');

echo "Caches cleared.\n";
echo "define_via_gate: " . (config('filament-shield.super_admin.define_via_gate') ? 'true' : 'false') . "\n";

$checks = ['ViewAny:Listing', 'ViewAny:Category', 'ViewAny:User', 'ViewAny:Role'];
foreach ($checks as $perm) {
    echo "{$perm}: " . ($admin->can($perm) ? 'YES' : 'NO') . "\n";
}

echo "Done. Log out and back in at /admin\n";
