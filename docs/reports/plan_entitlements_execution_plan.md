# Plan Entitlements Phase 1 — Execution Plan

**Project:** Nilex Marketplace  
**Date:** 2026-06-12  
**Prerequisite:** `docs/reports/plan_entitlements_validation_report.md` (passed)

---

## 1. Overview

Build an additive plan entitlements system that assigns tier features on `PointsPurchased` without modifying payment, point crediting, or seller dashboard analytics.

**In scope:** Infrastructure + enforcement of `business_badge`, `priority_support`, `search_priority`, `featured_listings_limit`, `monthly_boost_limit`.

**Out of scope:** Analytics gating, subscriptions, payment changes.

---

## 2. Database Architecture

### 2.1 Migration: `2026_06_12_100001_create_plan_entitlements_tables.php`

#### `plan_entitlements`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `plan_tier` | string(32) | `starter`, `growth`, `pro_seller`, `business` |
| `feature_key` | string(64) | e.g. `business_badge` |
| `value_type` | string(16) | `boolean`, `integer`, `string` |
| `value` | text | Serialized scalar |
| `description` | text nullable | |
| `timestamps` | | |

**Unique:** `(plan_tier, feature_key)`

#### `user_entitlements`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `user_id` | FK → users | cascade delete |
| `feature_key` | string(64) | |
| `value_type` | string(16) | |
| `value` | text | |
| `source` | string(32) | `purchase`, `admin_grant`, `migration_default` |
| `source_plan_id` | FK → point_plans nullable | |
| `source_transaction_id` | FK → transactions nullable | |
| `granted_at` | timestamp | |
| `expires_at` | timestamp nullable | Future subscriptions |
| `timestamps` | | |

**Unique:** `(user_id, feature_key)`  
**Indexes:** `user_id`, `feature_key`

#### `user_entitlement_usage`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `user_id` | FK → users | cascade delete |
| `feature_key` | string(64) | e.g. `monthly_boost_limit` |
| `period_key` | string(16) | `YYYY-MM` calendar month |
| `used_count` | unsigned int default 0 | |
| `timestamps` | | |

**Unique:** `(user_id, feature_key, period_key)`

### 2.2 Migration: `2026_06_12_100002_add_tier_columns_to_plans_and_users.php`

| Table | Column | Type | Notes |
|-------|--------|------|-------|
| `point_plans` | `tier_key` | string(32) nullable | Backfilled by seeder |
| `users` | `plan_tier` | string(32) nullable | Cached highest tier |

**NOT modified:** `users.points`, `transactions`, `point_transactions`

---

## 3. Model Architecture

### 3.1 New models

```
app/Models/PlanEntitlement.php
app/Models/UserEntitlement.php
app/Models/UserEntitlementUsage.php
```

**Shared constants:**

```php
// Value types
TYPE_BOOLEAN = 'boolean'
TYPE_INTEGER = 'integer'
TYPE_STRING  = 'string'

// Sources
SOURCE_PURCHASE = 'purchase'
SOURCE_ADMIN    = 'admin_grant'
SOURCE_MIGRATION = 'migration_default'

// Tier ranks (for upgrade logic)
TIER_RANKS = ['starter' => 1, 'growth' => 2, 'pro_seller' => 3, 'business' => 4]
```

### 3.2 Additive relationships

| Model | Relationship |
|-------|-------------|
| `User` | `entitlements()` HasMany `UserEntitlement` |
| `User` | `entitlementUsage()` HasMany `UserEntitlementUsage` |
| `PointPlan` | `resolveTierKey(): ?string` method (tier_key or name heuristic) |

### 3.3 Casts and accessors

- `UserEntitlement::castValue()` — parse stored value by `value_type`
- `PlanEntitlement::castValue()` — same pattern

---

## 4. Service Architecture

### 4.1 `app/Services/EntitlementService.php`

Singleton registered in `AppServiceProvider`.

| Method | Signature | Behavior |
|--------|-----------|----------|
| `hasFeature` | `(User, string): bool` | Boolean entitlements; legacy → false for premium flags |
| `canUseFeature` | `(User, string): bool` | Boolean check OR limit headroom > 0 |
| `remainingUsage` | `(User, string): ?int` | `null` = unlimited; else limit − used |
| `getLimit` | `(User, string): ?int` | `null` = unlimited (legacy grandfathered) |
| `assignFromPlan` | `(User, PointPlan, Transaction): void` | Upgrade tier if higher; upsert entitlements |
| `recordUsage` | `(User, string, int=1): void` | Increment `user_entitlement_usage` for current month |
| `resolveTier` | `(User): ?string` | From `users.plan_tier` or entitlements |
| `getUserEntitlements` | `(User): Collection` | All entitlements with parsed values |

**Caching:** `Cache::remember("entitlements:user:{$id}", 300, ...)` — invalidated on `assignFromPlan` and `recordUsage`.

**Legacy detection:**

```php
isLegacyGrandfathered(User $user): bool
// true when user_entitlements count = 0
// → getLimit() returns null (unlimited)
// → hasFeature() returns false for premium booleans
```

**Tier upgrade logic:**

```php
shouldUpgradeTier(?string $current, string $incoming): bool
// Compare TIER_RANKS; only upgrade if incoming > current
```

**Limit checks for `featured_listings_limit`:**

```php
activeFeaturedCount(User $user, ?Listing $excluding = null): int
// Count listings where is_featured AND featured_until >= now()
```

---

## 5. Listener Architecture

### 5.1 `app/Listeners/AssignPlanEntitlementsListener.php`

```
PointsPurchased
  → AssignPlanEntitlementsListener::handle()
    → EntitlementService::assignFromPlan($user, $plan, $transaction)
```

**Properties:**

- Synchronous (default) — runs after commit via event dispatch timing
- Idempotent — upsert on `(user_id, feature_key)`
- No modification to `PaymobWebhookService`

### 5.2 Registration

```php
// AppServiceProvider::boot()
Event::listen(PointsPurchased::class, AssignPlanEntitlementsListener::class);
```

---

## 6. Seeder Architecture

### 6.1 `database/seeders/PlanEntitlementSeeder.php`

Seeds `plan_entitlements` for four tiers with exact values from spec.

Also backfills `point_plans.tier_key` where plan names match heuristics.

### 6.2 `DatabaseSeeder` registration

```php
$this->call([
    // ...existing...
    PlanEntitlementSeeder::class,
]);
```

### 6.3 Seeder data matrix

| feature_key | starter | growth | pro_seller | business |
|-------------|---------|--------|------------|----------|
| featured_listings_limit | 1 | 3 | 5 | 10 |
| monthly_boost_limit | 2 | 5 | 10 | 20 |
| search_priority | false | true | true | true |
| home_promotion | true | true | true | true |
| business_badge | false | false | false | true |
| priority_support | false | false | false | true |

---

## 7. Enforcement Strategy

### 7.1 `business_badge` (display only)

| Surface | Implementation |
|---------|---------------|
| `frontend/partials/listing-card.blade.php` | Badge next to seller info if `$listing->user` has feature |
| `frontend/listings/show.blade.php` | Badge in advertiser section |
| `components/seller-trust-card.blade.php` | Business verified badge row |

**Helper:** `EntitlementService::hasFeature($seller, 'business_badge')` via Blade `@php` or View Composer.

**New partial:** `frontend/partials/business-badge.blade.php` for reuse.

### 7.2 `priority_support` (internal flag)

| Surface | Implementation |
|---------|---------------|
| `UserInfolist` | IconEntry read-only for `priority_support` |
| `UsersTable` | Badge column for plan tier + priority support indicator |

No seller-facing workflow change.

### 7.3 `search_priority` (additive ranking boost)

**File:** `HomeController::search()`

After `$search->paginate(12)`:

1. Eager-load `user` on result collection
2. Re-sort within page: listings from sellers with `search_priority` first
3. Preserve existing geo/relevance sort as secondary key

Does not remove or replace Scout sort — additive layer only.

### 7.4 `featured_listings_limit` (soft limit)

**File:** `Listing::featureWithPoints()`

Before point deduction:

```php
if (! $entitlementService->canUseFeature($user, 'featured_listings_limit')) {
    throw new \Exception('...');
}
```

Check: `activeFeaturedCount < getLimit()` (skip if limit is null/unlimited).

Extending an already-featured listing does not count as new slot.

### 7.5 `monthly_boost_limit` (usage tracking)

**File:** `Listing::featureWithPoints()`

Before deduction: `canUseFeature($user, 'monthly_boost_limit')`  
After successful boost: `recordUsage($user, 'monthly_boost_limit')`

Period key: `now()->format('Y-m')`

---

## 8. Legacy User Strategy

| User state | Limits | Premium booleans | Analytics |
|------------|--------|------------------|-----------|
| No entitlements (legacy) | Unlimited (`null`) | All false | Unchanged — full dashboard |
| Purchased starter+ | Tier limits apply | Per tier | Unchanged — full dashboard |
| Upgraded tier | Higher limits | Per tier | Unchanged |

**No migration backfill required** for legacy users — absence of entitlements triggers grandfather path.

Optional future: derive tier from completed transactions for users who purchased before Phase 1 deploy.

---

## 9. Authorization

- No new Spatie permissions
- No Filament Shield custom permissions
- Entitlements are orthogonal to admin RBAC
- Enforcement via `EntitlementService` only
- Existing policies unchanged

---

## 10. Testing Strategy

### 10.1 New suite: `tests/Feature/Plans/`

| File | Coverage |
|------|----------|
| `EntitlementAssignmentTest.php` | Listener fires on `PointsPurchased`; entitlements created |
| `EntitlementCheckTest.php` | `hasFeature`, `canUseFeature`, `remainingUsage`, `getLimit` |
| `PlanUpgradeTest.php` | Higher tier upgrades; lower tier does not downgrade |
| `TierResolutionTest.php` | `resolveTier`, `PointPlan::resolveTierKey` |
| `MonthlyUsageTrackingTest.php` | `recordUsage`, period keys, limit enforcement |
| `FeatureEnforcementTest.php` | Badge visibility, search priority sort, boost limits |
| `BackwardCompatibilityTest.php` | Legacy users unlimited; existing tests unaffected |

### 10.2 Regression suites (must pass)

```
tests/Feature/Pricing/*
tests/Feature/Dashboard/*
tests/Feature/Listings/ListingWorkflowTest.php
tests/Feature/NilexAuthPointsTest.php
tests/Feature/Search/AdvancedSearchTest.php
```

### 10.3 Test patterns

- Use `Event::fake()` except when testing listener execution
- Seed `PlanEntitlementSeeder` in plan tests
- Legacy user = `User::factory()->create()` with no entitlements
- Purchased user = dispatch `PointsPurchased` or call `assignFromPlan` directly

---

## 11. Rollback Strategy

### 11.1 Code rollback

1. Remove listener registration from `AppServiceProvider`
2. Remove enforcement checks from `Listing::featureWithPoints()` and `HomeController::search()`
3. Remove badge UI partials
4. Remove Filament column additions
5. Delete service, listener, models

Payment flow unaffected throughout — no rollback needed for payment code.

### 11.2 Database rollback

```bash
php artisan migrate:rollback --step=2
```

Drops: `plan_entitlements`, `user_entitlements`, `user_entitlement_usage`, `tier_key`, `plan_tier`.

**Data preserved:** `users.points`, `transactions`, `point_transactions`, listing boost state.

### 11.3 Runtime disable (emergency)

Remove listener registration only — entitlements stop assigning; enforcement can be bypassed by commenting limit checks. Legacy grandfather path ensures no user lockout.

---

## 12. Implementation Sequence

| Step | Deliverable | Est. files |
|------|-------------|------------|
| 1 | Migrations (2) | 2 |
| 2 | Models (3) + model extensions (2) | 5 |
| 3 | Seeder | 1 |
| 4 | EntitlementService | 1 |
| 5 | Listener + registration | 2 |
| 6 | Enforcement (Listing, HomeController, views, Filament) | 8 |
| 7 | Tests | 7 |
| 8 | Implementation report | 1 |

**Total new/modified files:** ~27

---

## 13. Success Criteria

- [ ] Migrations run clean on MySQL and SQLite (tests)
- [ ] `PointsPurchased` assigns entitlements without touching payment code
- [ ] Legacy users retain unlimited boosts and full analytics
- [ ] Business badge visible only for business tier
- [ ] Search priority boosts entitled sellers within result page
- [ ] Featured/monthly limits enforced for entitled users only
- [ ] All existing test suites pass
- [ ] New `tests/Feature/Plans/` suite passes
- [ ] Implementation report generated

**Execution plan: COMPLETE — ready for implementation.**
