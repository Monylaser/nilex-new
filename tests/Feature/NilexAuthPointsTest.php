<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Covers:
 *   1. Registration  → 100 welcome points, is_phone_verified=false, redirect /verify-otp
 *   2. Points        → listing creation credits 10 pts; featureWithPoints deducts correctly
 *   3. Admin ACL     → normal user blocked (403); super_admin allowed through
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ═══════════════════════════════════════════════════════════════════════════
// Test 1 – Registration
// ═══════════════════════════════════════════════════════════════════════════

describe('Registration', function () {

    beforeEach(function () {
        // Prevent OTP email/SMS jobs from actually dispatching.
        Queue::fake();
    });

    it('registers a new user successfully and authenticates them', function () {
        $response = $this->post('/register', [
            'name'                  => 'Ahmed Hassan',
            'contact'               => 'ahmed@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('otp.notice'));
    });

    it('awards exactly 100 welcome points on registration', function () {
        $this->post('/register', [
            'name'                  => 'Ahmed Hassan',
            'contact'               => 'ahmed@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::where('email', 'ahmed@example.com')->firstOrFail();

        expect($user->points)->toBe(100);
    });

    it('sets is_phone_verified to false immediately after registration', function () {
        $this->post('/register', [
            'name'                  => 'Ahmed Hassan',
            'contact'               => 'ahmed@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::where('email', 'ahmed@example.com')->firstOrFail();

        expect($user->is_phone_verified)->toBeFalse();
    });

    it('redirects the newly registered user to /verify-otp', function () {
        $response = $this->post('/register', [
            'name'                  => 'Ahmed Hassan',
            'contact'               => 'ahmed@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('otp.notice'));
    });

    it('rejects registration when the email is already taken', function () {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->post('/register', [
            'name'                  => 'Second User',
            'contact'               => 'duplicate@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('contact');
        expect(User::where('email', 'duplicate@example.com')->count())->toBe(1);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// Test 2 – Points (credit on listing creation + deduction via featureWithPoints)
// ═══════════════════════════════════════════════════════════════════════════

describe('Points', function () {

    beforeEach(function () {
        // HomeController::store() hard-codes category_id=1; the direct Listing::create()
        // calls also use category_id=1 and SQLite enforces FK constraints.
        Category::create([
            'name_ar'   => 'إلكترونيات',
            'name_en'   => 'Electronics',
            'slug'      => 'electronics',
            'is_active' => true,
        ]);
    });

    it('credits 10 points to a verified user who creates a listing', function () {

        $user = User::factory()->create([
            'points'            => 200,
            'is_phone_verified' => true,
        ]);

        $this->actingAs($user)
            ->post('/listings/store', [
                'title'       => 'iPhone 15 Pro للبيع',
                'description' => 'جهاز جديد، 256 جيجا، لون تيتانيوم.',
                'category_id' => Category::value('id'),
                'price'       => 500,
                'condition'   => 'new',
                'price_type'  => 'fixed',
                'phone'       => '01000000000',
            ])
            ->assertRedirect(route('dashboard'));

        expect($user->fresh()->points)->toBe(210); // 200 + 10 credit
    });

    it('deducts the correct number of points when a listing is featured', function () {
        $user = User::factory()->create([
            'points'            => 300,
            'is_phone_verified' => true,
        ]);

        // Create listing directly — isolates the deduction scenario from the HTTP layer.
        $listing = Listing::create([
            'title'       => 'Toyota Corolla 2022',
            'slug'        => 'toyota-corolla-2022',
            'description' => 'سيارة نظيفة جداً، مالك واحد.',
            'price'       => 150_000,
            'category_id' => 1,
            'user_id'     => $user->id,
            'status'      => Listing::STATUS_PUBLISHED,
        ]);

        $days         = 3;
        $expectedCost = Listing::featureCost($days); // 10 pts/day × 3 = 30

        $listing->featureWithPoints($days);

        expect($user->fresh()->points)
            ->toBe(300 - $expectedCost)              // 300 − 30 = 270
            ->and($listing->fresh()->is_featured)->toBeTrue()
            ->and($listing->fresh()->featured_until)->not->toBeNull();
    });

    it('throws an exception when user has insufficient points for featuring', function () {
        $user = User::factory()->create([
            'points'            => 5,   // not enough for 1 day (costs 10)
            'is_phone_verified' => true,
        ]);

        $listing = Listing::create([
            'title'       => 'إعلان اختباري',
            'slug'        => 'test-listing',
            'description' => 'وصف الإعلان.',
            'price'       => 1_000,
            'category_id' => 1,
            'user_id'     => $user->id,
            'status'      => Listing::STATUS_PUBLISHED,
        ]);

        expect(fn () => $listing->featureWithPoints(1))->toThrow(\Exception::class);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// Test 3 – Admin Access Control
// ═══════════════════════════════════════════════════════════════════════════

describe('Admin Access Control', function () {

    beforeEach(function () {
        // Ensure the super_admin role exists before each admin test.
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    });

    it('returns 403 when a normal user tries to access /admin', function () {
        $user = User::factory()->create(['is_phone_verified' => true]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden(); // canAccessPanel() returns false → Filament throws 403
    });

    it('allows a super_admin to access the /admin panel (200 or redirect)', function () {
        $admin = User::factory()->create(['is_phone_verified' => true]);
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->get('/admin');

        // Filament renders the panel (200) or redirects to /admin/dashboard (302).
        expect($response->status())
            ->toBeIn([200, 302])
            ->not->toBe(403);
    });

    it('redirects super_admin to a URL within /admin', function () {
        $admin = User::factory()->create(['is_phone_verified' => true]);
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->get('/admin');

        if ($response->isRedirect()) {
            expect($response->headers->get('Location'))->toContain('admin');
        } else {
            $response->assertOk();
        }
    });

    it('blocks a user with no roles from the /admin panel', function () {
        $noRole = User::factory()->create(['is_phone_verified' => true]);

        $this->actingAs($noRole)
            ->get('/admin')
            ->assertForbidden();
    });
});
