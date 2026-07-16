<?php

/**
 * Pre-launch security: login must not hardcode admin@gmail.com.
 * Staff reach /admin via Spatie roles after OTP; regular users never do.
 */

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('moderator', 'web');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('does not redirect a normal user with email admin@gmail.com to /admin', function () {
    $user = User::factory()->phoneVerified()->create([
        'email' => 'admin@gmail.com',
    ]);

    $response = $this->post('/login', [
        'email' => 'admin@gmail.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

it('redirects a verified staff user with an admin role to /admin after login', function () {
    $user = User::factory()->phoneVerified()->create([
        'email' => 'staff-admin@example.com',
    ]);
    $user->assignRole('admin');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/admin');
});

it('still sends an unverified staff user to OTP before any admin redirect', function () {
    $user = User::factory()->phoneUnverified()->create([
        'email' => 'unverified-admin@example.com',
        'email_verified_at' => null,
    ]);
    $user->assignRole('admin');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('otp.notice', absolute: false));
});
