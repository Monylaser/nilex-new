# Plan Entitlements Phase 1 — Validation Report

**Project:** Nilex Marketplace  
**Date:** 2026-06-12  
**Phase:** 1 — Pre-implementation validation  
**Source of truth:** `docs/reports/plan_entitlements_audit.md` (Phase 0 Audit)

---

## 1. Validation Scope

Pre-implementation verification of eight audit areas before any entitlement code is written.

| Area | Status | Verdict |
|------|--------|---------|
| PointPlan model | ✅ Verified | Matches audit |
| Payment flow | ✅ Verified | Matches audit |
| PointsPurchased event | ✅ Verified | Matches audit |
| Pricing page | ✅ Verified | Matches audit |
| Seller dashboard | ✅ Verified | Matches audit |
| Seller analytics | ✅ Verified | Matches audit |
| Database schema | ✅ Verified | Matches audit (additive columns pending) |
| Existing tests | ✅ Verified | Matches audit |

**Overall:** No blocking discrepancies. Safe to proceed with additive Phase 1 implementation.

---

## 2. Confirmed Findings

### 2.1 PointPlan Model

**File:** `app/Models/PointPlan.php`

- Fillable: `name_ar`, `name_en`, `points`, `price`, `description`, `is_active`
- Casts: `points` (integer), `price` (decimal), `is_active` (boolean)
- `scopeActive()` and `transactions()` HasMany confirmed
- No `tier_key`, no entitlement relationships (expected — Phase 1 adds `tier_key`)

**Migration:** `database/migrations/2026_06_06_000001_create_point_plans_table.php`  
Columns: `id`, `name_ar`, `name_en`, `points`, `price`, `description`, `is_active`, `timestamps` — matches audit §1.1.

### 2.2 Payment Flow

**Checkout:** `app/Http/Controllers/Frontend/PaymentController.php`

```
POST /payment/checkout → validate plan_id → Transaction (pending) → Paymob redirect
```

- Validates `plan_id` against active `point_plans`
- Creates `Transaction` with `user_id`, `plan_id`, `amount`, `status=pending`
- No entitlement logic present (correct)

**Fulfillment:** `app/Services/PaymobWebhookService.php` (lines 200–220)

```
fulfill() → PointService::credit() → Transaction completed → PointsPurchased::dispatch()
```

- Credits points via `PointService::credit()` only
- Dispatches `PointsPurchased` inside `DB::afterCommit()`
- Sends `PointsPurchasedNotification`
- No entitlement assignment today (correct hook point for listener)

### 2.3 PointsPurchased Event

**File:** `app/Events/PointsPurchased.php`

- Carries `User`, `PointPlan`, `Transaction`
- Uses `Dispatchable`, `SerializesModels`
- **No registered listener** in `AppServiceProvider` (confirmed — only auth listeners registered)

### 2.4 Pricing Page

**Route:** `GET /pricing` → `HomeController::pricing()`  
**Config:** `config/pricing.php` — `plan_column_keys`, `feature_matrix` match audit §2.2  
**UI:** `resources/views/frontend/pricing.blade.php` — tier resolution via name heuristics:

| Tier key | EN match | AR match |
|----------|----------|----------|
| `starter` | "starter" | "مبتد" |
| `growth` | "growth" | "نمو" |
| `pro_seller` | "pro" | "محترف" |
| `business` | "business" | "أعمال" / "شرك" |

Test seeder pattern in `tests/Feature/Pricing/PricingMatrixComplianceTest.php` matches audit §1.4 (100/49, 250/99, 700/249, 1500/499).

### 2.5 Seller Dashboard

**File:** `app/Livewire/Frontend/UserDashboard.php`

- `featureListing()` calls `Listing::featureWithPoints(3)` — 3-day hardcoded boost
- `render()` uses `SellerListingAnalyticsService::getDashboardStats()` for event analytics
- Legacy `views_count` / `whatsapp_clicks` sums retained
- No plan tier checks (correct baseline)

### 2.6 Seller Analytics

**File:** `app/Services/SellerListingAnalyticsService.php`

- `totalViewsForUser()`, `totalPhoneClicksForUser()`, `totalWhatsappClicksForUser()`
- `getDashboardStats()` returns all three event totals
- Available to all verified sellers — no tier gate (matches audit §5.1)

### 2.7 Database Schema

**Confirmed existing tables (unchanged in Phase 1):**

| Table | Key columns | Notes |
|-------|-------------|-------|
| `users` | `points`, `points_balance` | `points` is canonical balance per User model comment |
| `transactions` | `user_id`, `plan_id`, `status`, `is_processed` | Paymob linkage intact |
| `point_transactions` | `user_id`, `amount`, `type` | Ledger unchanged |
| `listings` | `is_featured`, `featured_until` | Boost mechanism intact |

**Listing boost:** `Listing::FEATURE_COST_PER_DAY = 10`, `featureWithPoints()` deducts points only — no plan checks.

**Search:** `HomeController::search()` — Scout/Meilisearch with geo sort; no `is_featured` or `search_priority` boost (matches audit §4.2).

### 2.8 Existing Tests

| Suite | Path | Relevance |
|-------|------|-----------|
| Pricing matrix | `tests/Feature/Pricing/PricingMatrixComplianceTest.php` | Must keep passing |
| Pricing page | `tests/Feature/Pricing/PricingPageTest.php` | Must keep passing |
| Dashboard stats | `tests/Feature/Dashboard/UserDashboardStatsTest.php` | Must keep passing |
| Seller analytics | `tests/Feature/Dashboard/SellerListingAnalyticsServiceTest.php` | Must keep passing |
| Listing workflow | `tests/Feature/Listings/ListingWorkflowTest.php` | Boost tests — backward compat critical |
| Points auth | `tests/Feature/NilexAuthPointsTest.php` | Purchase/credit flow |
| Advanced search | `tests/Feature/Search/AdvancedSearchTest.php` | Search priority extension point |

No `tests/Feature/Plans/` directory exists yet (expected — Phase 1 creates it).

---

## 3. Discrepancies

| # | Audit claim | Code reality | Severity | Resolution |
|---|-------------|--------------|----------|------------|
| D1 | Audit §10.2 optional `users.has_priority_support` | User spec omits this column; uses `user_entitlements` only | Low | Follow user spec — no `has_priority_support` column; read `priority_support` from entitlements |
| D2 | Audit §9.2 seeds `credits_included` entitlement | User Phase 1 spec does not seed `credits_included` | Low | Skip — credits remain via `PointPlan.points` (audit §9.2 notes "Existing") |
| D3 | Audit §9.2 seeds `event_views_access`, `phone_clicks_access`, etc. | User spec explicitly defers these to Phase 2 | None | Align with user spec — do not seed Phase 2 keys |
| D4 | No dedicated public seller profile route | Only `/profile` (account settings) and seller areas on listing show + `seller-trust-card` | Low | Badge enforcement on listing cards, show page, `seller-trust-card` component |
| D5 | Audit mentions `home_promotion_slots` (integer) | User spec uses `home_promotion` (boolean) | Low | Follow user spec boolean; homepage promotion remains boost-driven |
| D6 | `ListingController::show()` does not eager-load `user` | Seller section uses `$listing->user` (lazy load) | Low | Add `user` to eager load when implementing badge (additive) |

**No blocking discrepancies** between audit and implementation spec.

---

## 4. Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Breaking boost flow with new limits | **HIGH** | Grandfather users without entitlements (unlimited limits); enforce only after plan purchase |
| Seller dashboard regression | **HIGH** | Do not modify `UserDashboard` stats queries or analytics service |
| Payment/webhook regression | **HIGH** | Listener-only on `PointsPurchased`; zero changes to `PaymentController`, `PaymobWebhookService`, `PointService` |
| Existing boost tests failing | **MEDIUM** | Legacy users in tests have no entitlements → unlimited path preserved |
| Tier name heuristic mismatch | **MEDIUM** | Add `point_plans.tier_key`; seeder backfill; fallback to name heuristics in service |
| Search re-sort pagination edge cases | **MEDIUM** | Post-pagination collection sort within page only; document limitation |
| SQLite test parity | **LOW** | Use standard Laravel migration patterns; enum as string column |
| Matrix still misleading for deferred features | **LOW** | Phase 2 gating deferred per spec; document in implementation report |

---

## 5. Compatibility Concerns

### 5.1 Must remain unchanged

- `PaymentController::checkout()` and callback
- `PaymobWebhookService::fulfill()` point crediting path
- `PointService::credit()` / `deduct()` / `record()`
- `UserDashboard` render and analytics queries
- `SellerListingAnalyticsService` methods
- `users.points` column semantics
- `transactions` and `point_transactions` schemas

### 5.2 Safe extension points (additive)

| Location | Extension |
|----------|-----------|
| `PointsPurchased` event | New `AssignPlanEntitlementsListener` |
| `AppServiceProvider::boot()` | Register listener |
| `Listing::featureWithPoints()` | Pre-check limits; post-record usage |
| `HomeController::search()` | Post-sort boost for `search_priority` |
| Listing card / show / seller-trust-card | Display `business_badge` |
| Filament `UserInfolist` / `UsersTable` | Read-only `plan_tier`, `priority_support` |
| `PointPlan` model | Add `tier_key` fillable + `resolveTierKey()` helper |
| `User` model | Add `plan_tier` + `entitlements()` relationship |

### 5.3 Legacy user grandfathering

Per audit §14.2 and user spec §STEP 9:

- Users with **no** `user_entitlements` rows → treat as legacy grandfathered
- Legacy users: unlimited boost limits (`null` limit = no cap)
- Legacy users: no premium booleans (`business_badge`, `search_priority`, `priority_support` = false)
- Existing analytics UI unchanged for all users
- Users who purchase plans receive tier entitlements via listener

### 5.4 Tier persistence model

Per audit §14.3 recommendation (aligned with user spec):

- **Highest-ever tier** — purchasing a higher tier upgrades; purchasing a lower tier does not downgrade
- `users.plan_tier` cached for display; source of truth in `user_entitlements`

---

## 6. Recommended Implementation Approach

### Phase 1 order of execution

1. **Database** — migrations for three new tables + `tier_key` / `plan_tier` columns
2. **Models** — `PlanEntitlement`, `UserEntitlement`, `UserEntitlementUsage` + relationships
3. **Seeder** — `PlanEntitlementSeeder` with exact tier values from spec; register in `DatabaseSeeder`
4. **Service** — `EntitlementService` with caching, legacy detection, tier ranking
5. **Listener** — `AssignPlanEntitlementsListener` registered on `PointsPurchased`
6. **Enforcement** — badge display, Filament indicators, search boost, soft limits in `featureWithPoints`
7. **Tests** — `tests/Feature/Plans/` full coverage
8. **Report** — implementation report with test results

### Entitlement keys to seed (Phase 1 only)

| Key | Starter | Growth | Pro | Business |
|-----|---------|--------|-----|----------|
| `featured_listings_limit` | 1 | 3 | 5 | 10 |
| `monthly_boost_limit` | 2 | 5 | 10 | 20 |
| `search_priority` | false | true | true | true |
| `home_promotion` | true | true | true | true |
| `business_badge` | false | false | false | true |
| `priority_support` | false | false | false | true |

### Explicit non-goals (Phase 1)

- Analytics tier gating (`analytics_access`, `analytics_charts`, event views, phone/WhatsApp access)
- Subscription renewals / plan expiration
- Payment flow modifications
- Pricing matrix copy changes

---

## 7. Validation Exit Criteria

| Criterion | Status |
|-----------|--------|
| PointPlan verified | ✅ |
| Payment flow verified | ✅ |
| PointsPurchased verified | ✅ |
| Pricing page verified | ✅ |
| Dashboard verified | ✅ |
| Analytics verified | ✅ |
| Schema verified | ✅ |
| Tests inventoried | ✅ |
| Discrepancies documented | ✅ |
| Risks assessed | ✅ |
| Implementation approach defined | ✅ |

**Validation: COMPLETE — approved to proceed to execution plan and implementation.**
