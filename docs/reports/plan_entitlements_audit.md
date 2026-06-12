# Plan Entitlements System — Phase 0 Audit

**Project:** Nilex Marketplace  
**Date:** 2026-06-12  
**Phase:** 0 — Audit only (no implementation code)  
**Purpose:** Establish baseline before building a real plan entitlements system  
**Constraint:** Additive, backward-compatible changes only; do not break PointPlan purchasing, payment flow, or seller dashboard behavior.

---

## Executive Summary

Nilex monetization today is **points-based**, not subscription-based. `PointPlan` records are **credit top-up packages** (points + EGP price). Purchasing a plan credits points via Paymob webhook — it does **not** assign a seller tier, unlock features, or persist any entitlement state on the user.

The pricing page feature matrix (`config/pricing.php`) is **marketing/UI positioning only**. Tier columns imply differentiated capabilities, but the codebase grants **identical seller access** to every OTP-verified account regardless of which (or whether any) plan was purchased.

**Verdict:** Plans are almost entirely marketing today. A real entitlements system requires new database tables, a service layer, assignment logic on purchase, and selective enforcement — implemented additively without modifying existing payment or point-transaction flows.

---

## 1. Current PointPlan System

### 1.1 Data model

| Entity | Table | Purpose |
|--------|-------|---------|
| `PointPlan` | `point_plans` | Sellable credit packages |
| `Transaction` | `transactions` | Paymob payment records linked to `plan_id` |
| `PointTransaction` | `point_transactions` | Points ledger (credit/debit) |
| `User.points` | `users` | Active points balance |

**`point_plans` schema** (`database/migrations/2026_06_06_000001_create_point_plans_table.php`):

```
id, name_ar, name_en, points, price, description, is_active, timestamps
```

No tier key, no entitlement JSON, no subscription duration, no feature flags.

**`PointPlan` model** (`app/Models/PointPlan.php`): fillable fields only; no relationships to entitlements; `scopeActive()` + `transactions()` HasMany.

### 1.2 Purchase flow (must remain untouched)

```
User → POST /payment/checkout (plan_id)
     → PaymentController creates Transaction (pending)
     → Paymob iframe redirect
     → POST /payment/webhook (PaymobWebhookService)
     → PointService::credit(user, plan.points)
     → Transaction marked completed
     → PointsPurchased event dispatched (no entitlement listener today)
```

**Evidence:**

- `app/Http/Controllers/Frontend/PaymentController.php` — checkout validates `plan_id`, creates `Transaction`, redirects to Paymob
- `app/Services/PaymobWebhookService.php` — `fulfill()` credits points only; dispatches `PointsPurchased` + notification
- `app/Events/PointsPurchased.php` — carries `User`, `PointPlan`, `Transaction`; **no registered listener** assigns tier or entitlements

### 1.3 What PointPlan purchase actually grants today

| Outcome | Granted? |
|---------|----------|
| Points credited to `users.points` | ✅ Yes |
| Ledger entry in `point_transactions` | ✅ Yes |
| Email/notification | ✅ Yes |
| Seller tier / plan level persisted | ❌ No |
| Feature unlock | ❌ No |
| Entitlement expiry | ❌ No |

### 1.4 Plan naming convention (implicit tier mapping)

The pricing page resolves marketing tier keys from plan names (`resources/views/frontend/pricing.blade.php`, `_feature-matrix.blade.php`):

| Tier key | Name match (EN) | Name match (AR) |
|----------|-----------------|-----------------|
| `starter` | contains "starter" | contains "مبتد" |
| `growth` | contains "growth" | contains "نمو" |
| `pro_seller` | contains "pro" | contains "محترف" |
| `business` | contains "business" | contains "أعمال" / "شرك" |

Test seeder pattern (`tests/Feature/Pricing/PricingMatrixComplianceTest.php`):

| Plan | Points | Price (EGP) |
|------|--------|-------------|
| Starter | 100 | 49 |
| Growth | 250 | 99 |
| Pro Seller | 700 | 249 |
| Business | 1500 | 499 |

**Differentiation today:** points volume and price only. Higher tiers buy more credits per EGP — no backend feature difference.

### 1.5 Points economy (orthogonal to plans)

Points are also earned outside purchases:

| Source | Amount | Evidence |
|--------|--------|----------|
| Registration welcome | Config: 100 (`config/pricing.php`) | User registration flow |
| Listing publish reward | 10 | `HomeController::store()` |
| Phone verify, referral, daily login | Various | Gamification (documented on pricing page) |

Points are spent on listing boosts at **10 points/day** (`Listing::FEATURE_COST_PER_DAY`). Dashboard hardcodes a **3-day boost** (`UserDashboard::featureListing()` → `featureWithPoints(3)` = 30 points).

No plan check precedes boost purchase. Only `hasPoints($cost)` is validated.

---

## 2. Pricing Page Plans & Feature Matrix

### 2.1 Page structure

**Route:** `GET /pricing` → `HomeController::pricing()`  
**Data:** `PointPlan::active()`, `config('pricing.feature_matrix')`, `config('pricing.plan_column_keys')`

Sections: hero, plan cards (checkout CTA), feature matrix, analytics marketing, seller dashboard capabilities, value funnel, how-it-works, trust strip.

### 2.2 Feature matrix rows (`config/pricing.php`)

| Key | Starter | Growth | Pro | Business | Backend reality |
|-----|---------|--------|-----|----------|-----------------|
| `credits` | ✅ | ✅ | ✅ | ✅ | Real — points from purchase |
| `featured_listings` | ✅ | ✅ | ✅ | ✅ | Real for all — via points, no tier limit |
| `home_promotion` | ✅ | ✅ | ✅ | ✅ | Real for all — featured listings appear on homepage if boosted |
| `search_priority` | ❌ | ✅ | ✅ | ✅ | **Marketing only** — no search sort boost |
| `event_views` | ❌ | ✅ | ✅ | ✅ | **Misleading** — all sellers get event views in dashboard |
| `phone_clicks` | ❌ | ✅ | ✅ | ✅ | **Misleading** — all sellers get phone click totals |
| `whatsapp_clicks` | ❌ | ✅ | ✅ | ✅ | **Misleading** — all sellers get WhatsApp totals |
| `basic_ctr` | 🚧 Coming Soon | — | — | — | Not implemented |
| `analytics_charts` | ❌ | ❌ | ✅ | ✅ | **Misleading** — all sellers get bar chart |
| `top_listings` | 🔒 Admin Only | — | — | — | Admin BI only |
| `category_performance` | 🔒 Admin Only | — | — | — | Admin BI only |
| `revenue_analytics` | 🔒 Admin Only | — | — | — | Admin BI only |
| `business_dashboard` | 🚧 Coming Soon | — | — | — | Not implemented |
| `lead_funnel` | 🚧 Coming Soon | — | — | — | Not implemented |
| `advanced_ctr` | 🚧 Coming Soon | — | — | — | Not implemented |
| `monthly_reports` | 🚧 Coming Soon | — | — | — | Not implemented |
| `priority_support` | ❌ | ❌ | ❌ | ✅ | **Marketing only** — no support queue flag |
| `business_badge` | ❌ | ❌ | ❌ | ✅ | **Marketing only** — no badge UI or DB field |

### 2.3 Marketing vs code contradictions

| Copy source | Claim | Code reality |
|-------------|-------|--------------|
| `ui.pricing.dashboard_subtitle` | "Every verified seller account includes these features today — no plan tier required" | ✅ Accurate for dashboard |
| `ui.pricing.matrix_subtitle` | Higher tiers unlock visibility/promotion | ❌ Not enforced — same boost path for all |
| Matrix tier columns for analytics | Growth+ gets event analytics | ❌ All sellers get `SellerListingAnalyticsService` stats |
| Matrix `analytics_charts` Pro+ only | Tier-gated charts | ❌ All sellers get Chart.js bar chart in dashboard |
| Business plan description | Priority visibility, promotion tools | ❌ No priority logic beyond standard featured boost |

Prior compliance audits (`pricing_page_feature_audit.md`, `pricing_compliance_final_pass_report.md`, `seller_dashboard_compliance_audit.md`) correctly labeled admin-only and coming-soon features but noted the **tier-matrix vs all-access implementation gap** as a medium risk.

---

## 3. Existing Permissions

### 3.1 Spatie roles (admin panel only)

**Seeder:** `database/seeders/RoleSeeder.php`

| Role | Scope | Permissions |
|------|-------|-------------|
| `super_admin` | Filament admin | Gate bypass — full access |
| `moderator` | Filament admin | `view_listings`, `approve_listings`, `reject_listings`, `view_users` |
| `admin` | Filament admin | Panel access via `User::canAccessPanel()` |
| `panel_user` | Filament (Shield) | Basic panel user |

**No seller-facing roles.** Seller access = authenticated + OTP verified.

### 3.2 Filament Shield

`config/filament-shield.php` — auto-generates resource/page/widget permissions for admin panel. `custom_permissions` array is empty. No plan or entitlement permissions defined.

### 3.3 Admin widget gating

All BI widgets use `canView()` → `super_admin` only:

- `LeadFunnelWidget`, `TopListingsWidget`, `CategoryPerformanceWidget`
- Revenue/monetization widgets
- Platform stats widgets

**Seller cannot access admin permissions or widgets.** Entitlements are a separate concern from Spatie admin RBAC.

### 3.4 Seller route middleware

| Route group | Middleware | Plan check? |
|-------------|------------|-------------|
| `/dashboard`, `/listings/create`, `/payment/checkout`, etc. | `auth`, `otp.verified` | ❌ None |

---

## 4. Existing Seller Capabilities

### 4.1 Universal seller features (no plan gate)

| Capability | Implementation | Gated? |
|------------|----------------|--------|
| Create/manage listings | `HomeController`, `UserDashboard` | ❌ |
| Feature listing with points | `Listing::featureWithPoints()` | Points only |
| View dashboard stats | `UserDashboard::render()` | ❌ |
| Legacy views/WhatsApp totals | `listings.views_count`, `whatsapp_clicks` | ❌ |
| Event analytics totals | `SellerListingAnalyticsService` | ❌ |
| Performance bar chart | Chart.js in `user-dashboard.blade.php` | ❌ |
| Points history | `/points/history` | ❌ |
| Offer management | `UserDashboard` accept/reject | ❌ |
| Buy point plans | `PaymentController` | ❌ |

### 4.2 Listing boost / featured system

**Mechanism:**

- `listings.is_featured` + `listings.featured_until` columns
- `Listing::scopeFeatured()` — active featured = `is_featured AND featured_until >= now()`
- `Listing::featureWithPoints($days)` — deducts points, extends `featured_until`
- Homepage shows top 3 featured: `HomeController::index()` → `active()->featured()->latest()`

**Not plan-gated:**

- Any seller with sufficient points can boost
- No concurrent featured listing limit
- No monthly boost quota
- No tier-based discount on boost cost
- Search (`HomeController::search()`) does **not** boost featured listings — Scout/Meilisearch sorts by geo or default relevance; `is_featured` absent from `toSearchableArray()`

### 4.3 Absent seller capabilities (marketed or matrix-implied)

| Capability | Status |
|------------|--------|
| Business badge on profile/listings | Not implemented |
| Priority support flag | Not implemented |
| Search result priority | Not implemented |
| Tier-gated analytics sections | Not implemented |
| Monthly boost limits | Not implemented |
| Concurrent featured listing caps | Not implemented |

---

## 5. Existing Analytics Features

### 5.1 Seller layer (all verified sellers)

**Service:** `app/Services/SellerListingAnalyticsService.php`

| Method | Data source |
|--------|-------------|
| `totalViewsForUser()` | `listing_views` (scoped by listing owner) |
| `totalPhoneClicksForUser()` | `listing_phone_clicks` |
| `totalWhatsappClicksForUser()` | `listing_whatsapp_clicks` |
| `getDashboardStats()` | Aggregates above three |

**Write path:** `ListingLeadTrackingService` — deduped event recording on view, phone reveal, WhatsApp click.

### 5.2 Admin layer (super_admin only)

Platform-wide BI with date filters: lead funnel, CTR, top listings, category performance, revenue charts, conversion metrics. Documented fully in `pricing_page_feature_audit.md`.

### 5.3 Analytics tier matrix vs reality

The matrix implies Growth+ gets event analytics and Pro+ gets charts. In code, **Starter-equivalent sellers receive identical analytics UI** if they complete OTP verification. The only honest tier differentiation in the matrix today is `credits` (real) and correctly badged coming-soon/admin-only rows.

---

## 6. What Currently Differentiates Plans

| Dimension | Differentiated? | How |
|-----------|-----------------|-----|
| Points purchased | ✅ Yes | `point_plans.points` |
| Price (EGP) | ✅ Yes | `point_plans.price` |
| Marketing name/tier column | ✅ Yes | Name heuristics in Blade |
| Feature access | ❌ No | Identical for all sellers |
| Boost cost | ❌ No | Flat 10 pts/day |
| Analytics depth | ❌ No | Same dashboard |
| Search/home visibility mechanics | ❌ No | Same boost path |
| Support priority | ❌ No | — |
| Business badge | ❌ No | — |
| Subscription duration | ❌ No | One-time credit purchase |

**Conclusion:** Plans are **credit bundles with marketing tier labels**, not functional subscription tiers.

---

## 7. Capabilities That Can Realistically Be Gated

Prioritized by implementation safety and audit alignment.

### 7.1 Tier 1 — Safe to enforce now (additive, non-breaking)

| Entitlement key | Type | Rationale | Enforcement point |
|-----------------|------|-----------|-------------------|
| `business_badge` | boolean | Pure display; no existing behavior | Listing cards, show page, profile — show badge if entitled |
| `priority_support` | boolean | Internal flag only; no seller workflow change | `users` support queue / admin user detail |
| `analytics_access` | boolean | Gate **future** expanded analytics; grandfather current dashboard | New analytics routes only — **do not hide existing dashboard stats** per constraint #5 |
| `search_priority` | boolean | Additive sort boost in search | `HomeController::search()` post-sort or Meilisearch rank rules |

### 7.2 Tier 2 — Safe with soft limits (requires usage tracking)

| Entitlement key | Type | Rationale | Notes |
|-----------------|------|-----------|-------|
| `featured_listings_limit` | integer | Cap concurrent active featured listings | Default high or unlimited for legacy users; enforce in `featureWithPoints()` |
| `monthly_boost_limit` | integer | Cap boosts per calendar month | Track in `user_entitlement_usage` or counter on `user_entitlements` |
| `home_promotion_slots` | integer | Priority in homepage featured carousel | Extend existing `featured()->take(3)` sort |

### 7.3 Tier 3 — Requires new seller UI (defer or mark coming soon)

| Entitlement key | Type | Rationale |
|-----------------|------|-----------|
| `analytics_charts` | boolean/level | Per-listing charts, date filters — not built for sellers |
| `lead_funnel` | boolean | Admin widget exists; seller UI needed |
| `advanced_ctr` | boolean | No seller CTR service |
| `monthly_reports` | boolean | No export/report pipeline |
| `business_dashboard` | boolean | No separate business dashboard |

### 7.4 Not gateable to sellers (keep admin-only)

| Feature | Reason |
|---------|--------|
| `top_listings` | Platform-wide admin BI |
| `category_performance` | Platform-wide admin BI |
| `revenue_analytics` | Platform monetization admin BI |

---

## 8. Marketing-Only Features Today

Features marked ✅ in the matrix for some tiers but **not enforced or partially false**:

| Feature | Matrix claim | Actual state | Risk if unaddressed |
|---------|--------------|--------------|----------------------|
| `search_priority` | Growth+ | No search boost logic | False advertising |
| `event_views` | Growth+ | All sellers | Misleading tier column |
| `phone_clicks` | Growth+ | All sellers | Misleading tier column |
| `whatsapp_clicks` | Growth+ | All sellers | Misleading tier column |
| `analytics_charts` | Pro+ | All sellers get basic chart | Misleading tier column |
| `priority_support` | Business only | Not implemented | False advertising |
| `business_badge` | Business only | Not implemented | False advertising |
| `featured_listings` | All tiers ✅ | Same unlimited boost path | Misleading if limits intended |
| `home_promotion` | All tiers ✅ | Same mechanism | OK today — boost-driven |

**Recommended resolution path:**

1. **Implement entitlements** for boolean/limits where safe (§7.1–7.2)
2. **Grandfather existing dashboard analytics** for all verified sellers (constraint #5) while gating *new* analytics surfaces behind `analytics_access`
3. **Update matrix copy** only after enforcement exists — or mark unimplemented rows coming soon (already done for CTR/funnel/reports)

---

## 9. Proposed Entitlement Catalog

Canonical keys aligned with `config/pricing.php` matrix and implementation phases.

### 9.1 Plan tier keys

```
starter | growth | pro_seller | business
```

Derived from `PointPlan` name heuristics (existing Blade logic) — add explicit `tier_key` column to `point_plans` in Phase 1 (additive, nullable, backfill via seeder).

### 9.2 Entitlement definitions (proposed defaults)

| Key | Type | Starter | Growth | Pro | Business | Enforcement phase |
|-----|------|---------|--------|-----|----------|-------------------|
| `credits_included` | integer | 100 | 250 | 700 | 1500 | Existing (PointPlan.points) |
| `featured_listings_limit` | integer | 1 | 3 | 5 | 10 | Phase 1 — soft cap |
| `monthly_boost_limit` | integer | 2 | 5 | 10 | 20 | Phase 1 — usage table |
| `search_priority` | boolean | false | true | true | true | Phase 1 — search sort |
| `home_promotion` | boolean | true | true | true | true | Existing via boost |
| `event_views_access` | boolean | false | true | true | true | Phase 2 — new UI sections only |
| `phone_clicks_access` | boolean | false | true | true | true | Phase 2 |
| `whatsapp_clicks_access` | boolean | false | true | true | true | Phase 2 |
| `analytics_charts` | boolean | false | false | true | true | Phase 2 — enhanced charts |
| `analytics_access` | boolean | false | false | true | true | Phase 2 |
| `priority_support` | boolean | false | false | false | true | Phase 1 — flag only |
| `business_badge` | boolean | false | false | false | true | Phase 1 — display |

**Coming soon / admin-only** — do not seed as seller entitlements until product exists:

`basic_ctr`, `lead_funnel`, `advanced_ctr`, `monthly_reports`, `business_dashboard`, `top_listings`, `category_performance`, `revenue_analytics`

### 9.3 Tier resolution model (recommended)

**Option A — Purchase-activated tier (recommended for Phase 1):**

- On successful `PointsPurchased`, assign/update `user_entitlements` from purchased plan's tier
- Tier level = **highest tier ever purchased** (monotonic upgrade) OR **latest purchase overwrites** (simpler)
- Entitlements persist until admin revoke or optional `expires_at` (future subscription model)

**Option B — Subscription with expiry:**

- Deferred — requires billing cycle design outside current Paymob one-shot flow

**Backward compatibility default:**

- Users with **no purchase history** → `starter` tier entitlements OR explicit `legacy_unlimited` flag set on migration
- All existing sellers retain current dashboard behavior (constraint #5)

---

## 10. Required Database Changes (Additive Only)

### 10.1 New tables

#### `plan_entitlements`

Defines default entitlements per plan tier.

```sql
plan_entitlements
├── id
├── plan_tier          VARCHAR  -- starter|growth|pro_seller|business
├── feature_key        VARCHAR  -- e.g. business_badge, featured_listings_limit
├── value_type         ENUM     -- boolean|integer|string
├── value              TEXT     -- JSON-encoded or scalar string
├── description        TEXT NULL
├── timestamps
└── UNIQUE(plan_tier, feature_key)
```

#### `user_entitlements`

Materialized entitlements per user (denormalized for fast checks).

```sql
user_entitlements
├── id
├── user_id            FK → users
├── feature_key        VARCHAR
├── value_type         ENUM
├── value              TEXT
├── source             VARCHAR  -- purchase|admin_grant|migration_default
├── source_plan_id     FK → point_plans NULL
├── source_transaction_id FK → transactions NULL
├── granted_at         TIMESTAMP
├── expires_at         TIMESTAMP NULL
├── timestamps
└── UNIQUE(user_id, feature_key)  -- or allow history via separate usage table
```

#### `user_entitlement_usage` (recommended for limits)

```sql
user_entitlement_usage
├── id
├── user_id            FK → users
├── feature_key        VARCHAR  -- e.g. monthly_boost_limit
├── period_key         VARCHAR  -- e.g. 2026-06 (calendar month)
├── used_count         INTEGER DEFAULT 0
├── timestamps
└── UNIQUE(user_id, feature_key, period_key)
```

### 10.2 Optional additive columns (Phase 1)

| Table | Column | Purpose |
|-------|--------|---------|
| `point_plans` | `tier_key` VARCHAR NULL | Explicit tier mapping (eliminates name heuristics) |
| `users` | `plan_tier` VARCHAR NULL | Cached current tier for quick display |
| `users` | `has_priority_support` BOOLEAN DEFAULT false | Denormalized support flag (optional — can live in user_entitlements only) |

### 10.3 Tables explicitly NOT modified

- `point_transactions` — no schema or logic changes
- `transactions` — no breaking changes; optional nullable FK from `user_entitlements.source_transaction_id` only
- `users.points` — unchanged

### 10.4 Migration / seed strategy

1. Create tables
2. Seed `plan_entitlements` from proposed catalog (§9.2)
3. Backfill `user_entitlements` for existing users:
   - Default: starter-tier limits OR `legacy_grandfathered = true` for analytics/display
   - Derive tier from max completed `transactions.plan_id` where plan name maps to tier
4. No destructive data migration

---

## 11. Proposed Service Architecture (Phase 1 preview)

**`EntitlementService`** — central API (not implemented in Phase 0):

```php
hasFeature(User $user, string $key): bool
canUseFeature(User $user, string $key): bool      // boolean + limit headroom
remainingUsage(User $user, string $key): ?int     // null = unlimited
getLimit(User $user, string $key): ?int
assignFromPlan(User $user, PointPlan $plan, Transaction $tx): void
recordUsage(User $user, string $key, int $amount = 1): void
resolveTier(User $user): string
```

**Assignment hook (additive listener):**

```
PointsPurchased → AssignPlanEntitlementsListener → EntitlementService::assignFromPlan()
```

**Enforcement hook points (Phase 1 safe set):**

| Location | Check |
|----------|-------|
| `Listing::featureWithPoints()` | `canUseFeature('monthly_boost_limit')`, `featured_listings_limit` |
| `HomeController::search()` | `search_priority` boost in sort |
| `show.blade.php` / listing cards | `business_badge` display |
| Admin user resource | `priority_support` badge |
| Future analytics routes | `analytics_access` middleware |

**Explicit non-goals for Phase 1:**

- Do not modify `PaymentController`, `PaymobWebhookService` fulfillment logic (listener only)
- Do not modify `UserDashboard` stats queries (constraint #5)
- Do not block existing boost flow without grandfather defaults

---

## 12. Test Strategy (Phase 1 preview)

**New suite:** `tests/Feature/Plans/`

| Test file | Coverage |
|-----------|----------|
| `EntitlementAssignmentTest.php` | Purchase triggers entitlement assignment |
| `EntitlementCheckTest.php` | `hasFeature`, `canUseFeature`, `remainingUsage` |
| `PlanUpgradeTest.php` | Higher tier purchase upgrades entitlements |
| `FeatureAccessTest.php` | Badge visibility, boost limits, search priority |
| `BackwardCompatibilityTest.php` | Users without entitlements retain existing behavior |

Existing suites that must keep passing:

- `tests/Feature/Pricing/*`
- `tests/Feature/Dashboard/*`
- `tests/Feature/Listings/ListingWorkflowTest.php`
- `tests/Feature/NilexAuthPointsTest.php`

---

## 13. Risk Register

| Risk | Severity | Mitigation |
|------|----------|------------|
| Breaking existing boost flow with new limits | HIGH | Grandfather defaults; soft limits with high caps initially |
| Modifying seller dashboard per constraint #5 | HIGH | Gate new surfaces only; leave `UserDashboard` untouched |
| Payment flow regression | HIGH | Listener-only assignment; no webhook changes |
| Tier name heuristic mismatch | MEDIUM | Add `point_plans.tier_key`; seeder backfill |
| Matrix still misleading during rollout | MEDIUM | Implement badge + search priority first; sync matrix in implementation report |
| Dual analytics sources confusion | LOW | Entitlements separate from legacy/event data paths |
| SQLite vs MySQL migration parity | LOW | Follow existing migration patterns; verify with scripts |

---

## 14. Phase 0 Decisions & Recommendations

### 14.1 Proceed to Phase 1? **Yes**, with scope discipline

Build entitlements infrastructure additively:

1. Migrations + models + seeder
2. `EntitlementService` + `PointsPurchased` listener
3. Enforce: `business_badge`, `priority_support`, `search_priority`, soft `featured_listings_limit` / `monthly_boost_limit`
4. Tests in `tests/Feature/Plans/`
5. Implementation report

### 14.2 Defer to Phase 2+

- Seller analytics tier gating (new UI sections)
- CTR, funnel, monthly reports
- Subscription expiry model
- Pricing matrix copy updates (after enforcement verified)

### 14.3 Open product decisions (need stakeholder input)

| Question | Options | Recommendation |
|----------|---------|----------------|
| Tier persistence model | Latest purchase vs highest-ever tier | **Highest-ever tier** — rewards upgrades, avoids downgrade on cheap top-up |
| Default tier for existing users | Starter vs grandfather-all | **Grandfather analytics** + starter limits on *new* limit keys only |
| Boost limit defaults | Strict vs generous | **Generous** initially (e.g. 999 monthly) then tighten |

---

## 15. Evidence Index

| Area | Primary files |
|------|---------------|
| PointPlan model | `app/Models/PointPlan.php` |
| Payment checkout | `app/Http/Controllers/Frontend/PaymentController.php` |
| Webhook fulfillment | `app/Services/PaymobWebhookService.php` |
| Points service | `app/Services/PointService.php` |
| Pricing config/matrix | `config/pricing.php` |
| Pricing UI | `resources/views/frontend/pricing.blade.php`, `_feature-matrix.blade.php` |
| Seller dashboard | `app/Livewire/Frontend/UserDashboard.php` |
| Seller analytics | `app/Services/SellerListingAnalyticsService.php` |
| Listing boost | `app/Models/Listing.php` (`featureWithPoints`, `FEATURE_COST_PER_DAY`) |
| Admin permissions | `database/seeders/RoleSeeder.php`, `config/filament-shield.php` |
| Admin widget gating | `app/Filament/Admin/Widgets/*` (`canView()` → super_admin) |
| Prior audits | `docs/reports/pricing_page_feature_audit.md`, `pricing_compliance_final_pass_report.md`, `seller_dashboard_compliance_audit.md` |

---

## 16. Phase 0 Exit Criteria

| Criterion | Status |
|-----------|--------|
| PointPlan system documented | ✅ |
| Pricing matrix vs code gap identified | ✅ |
| Permissions vs seller capabilities separated | ✅ |
| Analytics layers mapped | ✅ |
| Listing boost system mapped | ✅ |
| Gateable vs marketing-only features classified | ✅ |
| Database schema proposed (additive) | ✅ |
| Entitlement catalog proposed | ✅ |
| Implementation risks identified | ✅ |
| No implementation code written | ✅ |

**Phase 0: COMPLETE — ready for Phase 1 implementation upon approval.**
