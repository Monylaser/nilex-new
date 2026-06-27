<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Admin — Points Adjustment Fix (UserResource `adjust_points` action).
 *
 *   - ROOT CAUSE (closed here): the old UserResource form bound a "رصيد النقاط"
 *     field directly to `users.points_balance` — a PASSIVE MIRROR column that no
 *     user-facing surface reads (the app reads `users.points` everywhere). So an
 *     admin's manual edit saved to `points_balance` had ZERO real effect on the
 *     user's balance, created no PointTransaction, and was later overwritten by
 *     PointService (which resyncs points_balance = points on the next operation).
 *   - FIX: the raw field is removed; a dedicated `adjust_points` action (in the
 *     table row group AND the EditUser header) runs PointService::credit/deduct,
 *     so `points` + `points_balance` move together, a real PointTransaction is
 *     logged (visible in the user's points history), and an AuditLog is recorded.
 */

use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Models\AuditLog;
use App\Models\PointTransaction;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');

    $this->member = User::factory()->create([
        'points'         => 100,
        'points_balance' => 100,
    ]);
});

// ═══════════════════════════════════════════════════════════════════════════
// 1) Credit: moves points + points_balance together, logs a real transaction
// ═══════════════════════════════════════════════════════════════════════════

it('credit updates points AND points_balance and creates a PointTransaction shown in history', function () {
    Livewire::actingAs($this->admin)
        ->test(ListUsers::class)
        ->callAction(
            TestAction::make('adjust_points')->table($this->member),
            data: [
                'operation' => 'credit',
                'amount'    => 3,
                'reason'    => 'تعويض عن خطأ',
            ],
        );

    $fresh = $this->member->fresh();
    expect($fresh->points)->toBe(103);
    expect($fresh->points_balance)->toBe(103); // mirror stays in sync

    $tx = PointTransaction::where('user_id', $this->member->id)->latest()->first();
    expect($tx)->not->toBeNull();
    expect($tx->amount)->toBe(3);
    expect($tx->current_balance)->toBe(103);
    expect($tx->description)->toBe('تعديل إداري: تعويض عن خطأ'); // fixed AR prefix shown to the user

    // It will appear in the user's points history (same relation the view reads).
    expect($this->member->pointTransactions()->count())->toBe(1);
});

// ═══════════════════════════════════════════════════════════════════════════
// 2) Debit: same mechanism, negative amount
// ═══════════════════════════════════════════════════════════════════════════

it('debit updates points AND points_balance and logs a negative PointTransaction', function () {
    Livewire::actingAs($this->admin)
        ->test(ListUsers::class)
        ->callAction(
            TestAction::make('adjust_points')->table($this->member),
            data: [
                'operation' => 'debit',
                'amount'    => 40,
                'reason'    => 'استرداد نقاط',
            ],
        );

    $fresh = $this->member->fresh();
    expect($fresh->points)->toBe(60);
    expect($fresh->points_balance)->toBe(60);

    $tx = PointTransaction::where('user_id', $this->member->id)->latest()->first();
    expect($tx->amount)->toBe(-40);
    expect($tx->current_balance)->toBe(60);
    expect($tx->description)->toBe('تعديل إداري: استرداد نقاط');
});

// ═══════════════════════════════════════════════════════════════════════════
// 3) Insufficient debit is rejected gracefully (clear message, no crash)
// ═══════════════════════════════════════════════════════════════════════════

it('rejects a debit larger than the balance without crashing and leaves everything unchanged', function () {
    Livewire::actingAs($this->admin)
        ->test(ListUsers::class)
        ->callAction(
            TestAction::make('adjust_points')->table($this->member),
            data: [
                'operation' => 'debit',
                'amount'    => 500, // member only has 100
                'reason'    => 'محاولة خصم زائد',
            ],
        )
        ->assertNotified('الرصيد غير كافٍ لإتمام الخصم');

    $fresh = $this->member->fresh();
    expect($fresh->points)->toBe(100);          // untouched
    expect($fresh->points_balance)->toBe(100);  // untouched

    expect(PointTransaction::where('user_id', $this->member->id)->count())->toBe(0);
    expect(AuditLog::where('action', 'adjust_points')->count())->toBe(0);
});

// ═══════════════════════════════════════════════════════════════════════════
// 4) AuditLog is recorded on a successful adjustment
// ═══════════════════════════════════════════════════════════════════════════

it('records an AuditLog with the operation, amount and reason', function () {
    Livewire::actingAs($this->admin)
        ->test(ListUsers::class)
        ->callAction(
            TestAction::make('adjust_points')->table($this->member),
            data: [
                'operation' => 'credit',
                'amount'    => 7,
                'reason'    => 'مكافأة',
            ],
        );

    $log = AuditLog::where('action', 'adjust_points')
        ->where('target_id', $this->member->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->admin_id)->toBe($this->admin->id);
    expect($log->payload['operation'])->toBe('credit');
    expect($log->payload['amount'])->toBe(7);
    expect($log->payload['reason'])->toBe('مكافأة');
});

// ═══════════════════════════════════════════════════════════════════════════
// 5) The action is available in BOTH places (table row + EditUser header)
// ═══════════════════════════════════════════════════════════════════════════

it('exposes the adjust_points action in the EditUser header', function () {
    Livewire::actingAs($this->admin)
        ->test(EditUser::class, ['record' => $this->member->id])
        ->assertActionExists('adjust_points');
});

it('exposes the adjust_points action and applies it from the EditUser header', function () {
    Livewire::actingAs($this->admin)
        ->test(EditUser::class, ['record' => $this->member->id])
        ->callAction('adjust_points', data: [
            'operation' => 'credit',
            'amount'    => 5,
            'reason'    => 'تعديل من الهيدر',
        ]);

    expect($this->member->fresh()->points)->toBe(105);
});
