<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$user = User::where('email', 'admin@nilex.com')->first() ?? User::first();

if (! $user) {
    echo "NO_USER\n";
    exit(1);
}

echo "User ID: {$user->id}\n";
echo "Email: {$user->email}\n";
echo "Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";

$role = Role::where('name', 'super_admin')->first();
echo 'super_admin permission count: ' . ($role?->permissions()->count() ?? 0) . "\n";
echo 'Total permissions: ' . Permission::count() . "\n";

$checks = [
    'ViewAny:Listing',
    'ViewAny:Category',
    'ViewAny:Location',
    'ViewAny:User',
    'ViewAny:Moderation',
    'ViewAny:AuditLog',
    'ViewAny:Role',
    'ViewAny:Campaign',
    'ViewAny:LegalPage',
];

foreach ($checks as $perm) {
    $has = $user->can($perm) ? 'YES' : 'NO';
    echo "{$perm}: {$has}\n";
}
