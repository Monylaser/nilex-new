# TD-05 — `featureWithPoints()` Return Contract Audit

**Project:** Nilex Marketplace  
**Date:** 2026-06-11  
**Debt ID:** TD-05 (related: TD-10, TD-03)  
**Scope:** `Listing::featureWithPoints()`, `UserDashboard::featureListing()`, `PointService`, all callers  
**Mode:** Read-only audit — no application code was modified  
**Reference:** `docs/reports/post_phase3_architecture_audit.md` (TD-05, TD-10)

---

## Executive Summary

TD-05 is **confirmed**. The seller dashboard (`UserDashboard::featureListing()`) treats `Listing::featureWithPoints()` as if it returns a boolean success flag. The method is declared `: void` and implicitly returns `null`, which PHP evaluates as falsy. **Every successful feature operation therefore flashes the wrong message** — *"عذراً، ليس لديك نقاط كافية"* — even though points are deducted and the listing is featured.

The underlying business logic in `Listing::featureWithPoints()` **does work**: it validates balance, decrements `users.points`, writes a ledger row (partially), and updates `is_featured` / `featured_until`. The bug is a **return-type / caller contract mismatch**, not broken domain logic.

The Filament admin path (`ListingTable.php`) calls the same method correctly via `try/catch` and is **not affected**.

Unit/feature tests call `featureWithPoints()` directly without checking a return value, so they pass and **do not catch the seller UX bug**. There is **no Livewire test** for `featureListing()`.

**Audit verdict:** **FAIL (bug confirmed).** Recommended fix: **Option A** — minimal caller-side alignment (or equivalent `bool` return on the model) without changing deduction/feature semantics.

---

## Root Cause

### Primary defect: void used as boolean

```262:298:app/Models/Listing.php
    public function featureWithPoints(int $days): void
    {
        $cost = self::featureCost($days);
        // ...
        if (! $user->hasPoints($cost)) {
            throw new \Exception(/* ... */);
        }
        $user->decrement('points', $cost);
        // ... PointTransaction::create, listing update ...
    }
```

```46:61:app/Livewire/Frontend/UserDashboard.php
    public function featureListing(int $id)
    {
        $listing = Listing::where('user_id', Auth::id())->findOrFail($id);

        if ($listing->is_featured) {
            session()->flash('error', 'هذا الإعلان مميز بالفعل!');
            return;
        }

        if ($listing->featureWithPoints(3)) {
            session()->flash('success', 'تم خصم النقاط وتمييز الإعلان بنجاح! 🚀');
        } else {
            session()->flash('error', 'عذراً، ليس لديك نقاط كافية.');
        }
    }
```

| Outcome | What happens in DB | What `if (...)` sees | Flash message shown |
|---------|-------------------|----------------------|---------------------|
| Sufficient points | Points deducted, listing featured | `null` → falsy | **Error** (wrong) |
| Insufficient points | Nothing changed | `\Exception` thrown (uncaught) | Livewire error — **not** the `else` branch |
| Already featured (guard) | Nothing | Early `return` | Correct error |

The `else` branch is **dead code for insufficient points** because failures throw rather than return `false`. It is **incorrectly executed on every success** because `void` → `null` is falsy.

### Classification

| Category | Applies? |
|----------|----------|
| Return type mismatch | **Yes — primary root cause** |
| Incorrect conditional logic | **Yes — caller assumes boolean API that does not exist** |
| Dead code | **Partial — `else` branch never handles real insufficient-points path** |
| Hidden exception handling issue | **Secondary — uncaught `\Exception` on insufficient balance bypasses friendly Arabic message** |

This is **not** a case of PASS (no bug).

### Secondary observations (out of TD-05 scope but noted)

1. **`is_featured` guard vs extension logic** — `featureWithPoints()` supports extending an active feature from `featured_until`, but `featureListing()` blocks when `is_featured` is true (even if expired). Stale `is_featured` could block legitimate extension.
2. **Hard-coded 3 days** — Seller UI always features for 3 days; admin panel offers 3/7/14/30.
3. **TD-10 / TD-03** — Model bypasses `PointService` (no transaction, no `lockForUpdate`, `points_balance` not synced). Separate debt; Option A does not require fixing these.

---

## Call Flow Diagram

### Seller path (buggy UX)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  user-dashboard.blade.php                                                   │
│  wire:click="featureListing({{ $listing->id }})"                           │
└───────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  UserDashboard::featureListing($id)                                         │
│  1. Load listing (owner scope)                                              │
│  2. if (is_featured) → flash error, return                                  │
│  3. if (featureWithPoints(3))  ← expects bool                               │
└───────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  Listing::featureWithPoints(3): void                                        │
│  ├─ featureCost(3) → 30 points                                              │
│  ├─ user->hasPoints(30)?  NO → throw Exception                              │
│  ├─ user->decrement('points', 30)          [bypasses PointService]          │
│  ├─ PointTransaction::create([...])        [partial ledger]                 │
│  └─ listing->update(is_featured, featured_until)                            │
│  returns: null (implicit void)                                              │
└───────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
            success │                               │ insufficient points
                    ▼                               ▼
         if (null) → FALSE                 Exception bubbles to Livewire
         flash ERROR ❌                      (not else-branch message)
         DB state: featured ✅               DB state: unchanged
```

### Admin path (correct pattern)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  Filament ListingTable — "تمييز الإعلان" action                             │
└───────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  try {                                                                      │
│      $record->featureWithPoints($days);   // void — no return check       │
│      AuditLog::record(...);                                                 │
│      Notification::success(...);                                            │
│  } catch (\Exception $e) {                                                  │
│      Notification::danger($e->getMessage());                                │
│  }                                                                          │
└─────────────────────────────────────────────────────────────────────────────┘
```

### PointService (canonical path — not used by featureWithPoints)

```
PointService::deduct()
  └─ DB::transaction
       ├─ User::lockForUpdate()
       ├─ balance check → InsufficientPointsException
       ├─ increment points + sync points_balance
       └─ PointTransaction (morph reference, current_balance)
```

---

## Files Affected

| File | Role | TD-05 impact |
|------|------|--------------|
| `app/Models/Listing.php` | `featureWithPoints(): void` — deduction + feature | Defines void contract; business logic works |
| `app/Livewire/Frontend/UserDashboard.php` | `featureListing()` — boolean misuse | **Bug location — seller UX** |
| `resources/views/livewire/frontend/user-dashboard.blade.php` | Triggers `featureListing` | Surfaces wrong flash |
| `app/Filament/Admin/Resources/Listings/Schemas/ListingTable.php` | Admin feature action | **Not buggy** — uses try/catch |
| `app/Services/PointService.php` | Canonical ledger API | **Bypassed** by `featureWithPoints` (TD-10) |
| `app/Models/User.php` | `hasPoints()` | Used by model method |
| `app/Models/PointTransaction.php` | Ledger model | Written directly from model |
| `app/Observers/PointTransactionObserver.php` | Sets `current_balance` on create | Compensates for pre-decrement pattern |
| `tests/Feature/NilexAuthPointsTest.php` | Direct `featureWithPoints()` calls | Passes — no return-value assertion |
| `tests/Feature/Listings/ListingWorkflowTest.php` | Boost system tests | Passes — no Livewire coverage |
| `tests/Feature/Dashboard/UserDashboardStatsTest.php` | Dashboard stats only | **No `featureListing` test** |

### Caller inventory (`featureWithPoints`)

| Caller | File | Return handling | Status |
|--------|------|-----------------|--------|
| Seller dashboard | `UserDashboard.php:56` | `if (...)` boolean | **Broken** |
| Admin panel | `ListingTable.php:238` | try/catch, void | Correct |
| Pest tests | `NilexAuthPointsTest.php`, `ListingWorkflowTest.php` | Direct call | N/A |

---

## Detailed Findings

### 1. Return type

- **Declared:** `void` (`Listing.php:262`)
- **Docblock:** `@throws \Exception` on insufficient points — implies exception-based failure, not boolean `false`
- **Actual runtime value when used in expression:** `null` (PHP implicit return from void function)

### 2. Points deduction

- **Yes.** `$user->decrement('points', $cost)` at `Listing.php:276`
- **Cost:** `days × FEATURE_COST_PER_DAY` (10 pts/day); seller path hard-codes `3` days → 30 points
- **Pre-check:** `$user->hasPoints($cost)` before decrement
- **Not atomic:** No `DB::transaction`; decrement and listing update are separate statements
- **`points_balance`:** Not updated (only `PointService::record()` syncs both columns — TD-03 risk)

### 3. Listing featured

- **Yes.** Updates `is_featured = true` and `featured_until` (`Listing.php:294–297`)
- **Extension:** If already featured with future `featured_until`, new days append from that date
- **Seller guard conflict:** `featureListing()` returns early when `is_featured` is true, preventing extension from dashboard

### 4. Exceptions on failure

- **Insufficient points:** `throw new \Exception(...)` with Arabic message (`Listing.php:270–272`)
- **Not** `InsufficientPointsException` (used by `PointService`)
- **UserDashboard:** No try/catch — exception propagates to Livewire error handling

### 5. PointService bypass (TD-10)

| Capability | `PointService::deduct()` | `Listing::featureWithPoints()` |
|------------|--------------------------|--------------------------------|
| `DB::transaction` | Yes | No |
| `lockForUpdate` | Yes | No |
| `points_balance` sync | Yes | No |
| Typed exception | `InsufficientPointsException` | Generic `\Exception` |
| Morph `reference` on ledger | Yes | No — manual `PointTransaction::create` |
| `current_balance` on ledger | Set in service | Set by `PointTransactionObserver` after decrement |

### 6. Ledger entries

`featureWithPoints()` creates a `PointTransaction` with:

```php
PointTransaction::create([
    'user_id'     => $user->id,
    'amount'      => -$cost,
    'type'        => 'feature_listing',      // not in schema / fillable — discarded
    'description' => "...",
    'meta'        => json_encode([...]),     // not in schema / fillable — discarded
]);
```

**Schema** (`2026_04_05_175803_create_point_transactions_table.php`): `user_id`, `amount`, `current_balance`, `description`, `reference_id`, `reference_type`, timestamps.

**Result:** A ledger row **is** created with `user_id`, `amount`, `description`. `current_balance` is populated by `PointTransactionObserver::creating()` reading the already-decremented `user.points`. `type`, `meta`, and listing morph reference are **not** persisted. Ledger is **partially correct** but inconsistent with `PointService` records.

**Observer side effect:** `PointTransactionObserver::created()` flashes `points_added` session message on every transaction — may interact unexpectedly with Livewire session flashes on seller path.

---

## Risk Assessment

| Risk | Severity | Likelihood | Notes |
|------|----------|------------|-------|
| Seller sees failure after successful feature | **High** | **Certain** on every success via dashboard | Trust/ support burden; users may retry and spend again if guard fails |
| Uncaught exception on insufficient points | Medium | When balance < cost | Harsh UX vs intended Arabic flash |
| Double-spend under concurrency | Medium | Low–Medium | No row lock; two parallel requests could pass `hasPoints` |
| `points` / `points_balance` drift | Low | When admin reads `points_balance` | TD-03; bypass doesn't update mirror column |
| Partial ledger (no listing reference) | Low | Every feature via points | Audit/reporting gap |
| Admin path regression | None | N/A | Uses try/catch correctly |

**Business impact:** Revenue-impacting UX — monetization action succeeds silently in data while UI reports failure. Aligns with post-Phase-3 audit assessment (**High** priority fix, **Low** implementation effort).

---

## Recommended Fix Options

### Option A — Minimal safe fix (preferred)

**Goal:** Align return contract and caller expectations without changing deduction/feature semantics.

**Approach A1 (caller-only — smallest diff):**

```php
// UserDashboard::featureListing()
try {
    $listing->featureWithPoints(3);
    session()->flash('success', 'تم خصم النقاط وتمييز الإعلان بنجاح! 🚀');
} catch (\Exception $e) {
    session()->flash('error', $e->getMessage());
}
```

**Approach A2 (model returns bool — matches caller intent):**

```php
public function featureWithPoints(int $days): bool
{
    // existing logic unchanged
    return true;
}
// throw on failure unchanged
```

Also update `UserDashboard` to try/catch for insufficient points (A1) or keep `if` with A2.

| Pros | Cons |
|------|------|
| Low risk, small diff | Does not fix TD-10 bypass |
| Preserves existing tests | A2 changes public API signature |
| Matches admin try/catch pattern | Should add Livewire test for regression |

**Recommendation:** **A1** if minimizing model changes; **A2** if documenting success explicitly is desired. Either satisfies TD-05.

---

### Option B — Refactor to use `PointService` fully

**Goal:** Route deduction through `PointService::deduct()` inside `featureWithPoints()`, then update listing.

```php
app(PointService::class)->deduct(
    $user,
    $cost,
    "تمييز إعلان #{$this->id} لمدة {$days} أيام",
    $this,
);
// then update is_featured / featured_until in same DB::transaction
```

| Pros | Cons |
|------|------|
| Fixes TD-10 partially | Larger change; touches concurrency + ledger shape |
| `lockForUpdate`, `points_balance` sync | Must wrap listing update in same transaction |
| Consistent `InsufficientPointsException` | Requires updating tests expecting `\Exception` |
| Morph reference on ledger | Remove duplicate `PointTransaction::create` |

**When to choose:** TD-05 fix **plus** ledger hardening in one pass; acceptable if QA bandwidth exists.

---

### Option C — Service-layer redesign

**Goal:** Extract `ListingFeatureService` (or extend `PointService`) owning the full flow: validate → deduct → feature → audit log.

| Pros | Cons |
|------|------|
| Single entry point for seller + admin | Highest scope |
| Testable unit with mocked dependencies | Migration of Filament action + model method |
| Future product rules (packages, promos) | Overkill for TD-05 alone |

**When to choose:** Planned monetization refactor, not urgent TD-05 closure.

---

## Test Coverage Gap

Existing tests verify model behavior only:

- `NilexAuthPointsTest` — deduction amount, exception on low balance
- `ListingWorkflowTest` — `is_featured`, `featured_until`, points math

**Missing:** `Livewire::test(UserDashboard::class)->call('featureListing', $id)->assertSessionHas('success', ...)` 

This gap allowed TD-05 to ship undetected.

---

## Decision

### **FAIL (bug confirmed)**

The post-Phase-3 audit finding is accurate: **`featureWithPoints()` returns void while `UserDashboard` treats the return value as boolean**, causing successful features to display an insufficient-points error.

### Recommended implementation

**Implement Option A (Approach A1 — caller try/catch)** as the TD-05 fix:

1. Minimal diff confined to `UserDashboard::featureListing()`
2. Matches the working Filament admin pattern
3. Preserves `Listing::featureWithPoints()` behavior and existing model tests
4. Correctly surfaces insufficient-points message via `$e->getMessage()`

Follow-up (separate tasks): Option B for TD-10, add Livewire regression test, reconcile `is_featured` guard with extension logic.

---

*Audit performed read-only. No fixes were applied.*
