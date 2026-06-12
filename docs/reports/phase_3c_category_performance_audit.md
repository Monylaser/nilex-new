# Phase 3C — CategoryPerformanceWidget Pre-Implementation Audit

**Project:** Nilex Marketplace  
**Date:** 2026-06-11  
**Phase:** 3C — CategoryPerformanceWidget (audit only, no code)  
**Auditor role:** Senior Laravel 12 + Filament v5 Analytics Architect

---

## Executive Summary

Nilex is **ready to implement** `CategoryPerformanceWidget` for MVP using **direct category grouping** (`GROUP BY listings.category_id`). The platform’s category tree exists in the schema and admin UI, but **production data and listing flows currently use a flat root-category model**: all 13 seeded categories are roots, zero child categories exist, and every listing creation path selects a root category only.

Lead-tracking event tables (`listing_views`, `listing_phone_clicks`, `listing_whatsapp_clicks`, `offers`) are in place with adequate indexes for date-filtered, listing-scoped aggregation. Category-level metrics require a **listing join bridge** (events → listings → categories); no schema blocker exists.

**Recommended MVP decisions:**

| Decision | Recommendation |
|----------|----------------|
| Category grouping | **Option A** — `GROUP BY listings.category_id` (no parent rollup) |
| Total Leads | **Option B** — Phone clicks + WhatsApp clicks (contact intent) |
| CTR formula | **CTR A** — `(phone_clicks / views) × 100` (consistent with Phase 3B) |
| Widget architecture | Filament **TableWidget**, full width, date filter (Today / 7d / 30d), `super_admin` only |

**Overall audit status: PASS — no blockers for MVP implementation.**

Parent-category rollup (Option B) should be deferred until listings are consistently attached to leaf subcategories and product confirms rollup semantics.

---

## PASS / BLOCKERS Table

| Area | Status | Notes |
|------|--------|-------|
| Category model & hierarchy API | **PASS** | `parent()`, `children()`, `allChildren()` implemented |
| Category hierarchy in production data | **PASS*** | *Flat today: 13 roots, 0 children; hierarchy unused in live listings |
| Listing → category attachment | **PASS** | All listings use `listings.category_id` FK |
| Listing event relationships | **PASS** | `views()`, `phoneClicks()`, `whatsappClicks()` present |
| Listing `offers()` relationship | **PASS*** | *Missing on `Listing`; workaround exists (subquery / `whereExists`) |
| Event table schemas | **PASS** | All four tables available with `created_at` |
| Event table indexes | **PASS** | `(listing_id, created_at)` + standalone `created_at` on all event tables |
| `listings.category_id` join path | **PASS** | FK index `listings_category_id_foreign` exists |
| Date filtering support | **PASS** | Same pattern as `LeadFunnelWidget` / `TopListingsWidget` |
| CTR calculation feasibility | **PASS** | Zero-safe SQL expression validated in Phase 3B |
| Filament dashboard slot | **PASS** | Sort `9`, register after `TopListingsWidget` |
| Parent category rollup | **DEFER** | Not required for MVP; would add complexity without current data need |
| Pre-implementation migration | **OPTIONAL** | No mandatory migration; optional `listings(category_id, status)` index |
| Legacy counter contamination | **PASS*** | *Risk documented; widget must avoid `listings.views_count`, `categories.views_count` |

**Blockers: none.**

---

## 1. Category Structure

### 1.1 Model API

**File:** `app/Models/Category.php`

| Method / scope | Implementation | Purpose |
|----------------|----------------|---------|
| `parent()` | `belongsTo(Category::class, 'parent_id')` | Direct parent |
| `children()` | `hasMany(Category::class, 'parent_id')->orderBy('sort_order')` | Direct children only |
| `allChildren()` | `children()->with('allChildren')` | Recursive eager-load of descendants (comment warns: use carefully on large trees) |
| `listings()` | `hasMany(Listing::class)` | Direct listings on this category ID |
| `scopeRoots()` | `whereNull('parent_id')` | Root categories |
| `getIsRootAttribute` | `parent_id === null` | Root detection |

**Schema:** `categories` table (`database/migrations/2024_04_17_221759_create_categories_table.php`)

- Columns: `id`, `name_ar`, `name_en`, `slug`, `icon`, `color`, `sort_order`, `is_active`, `parent_id`, `custom_fields_schema`, `timestamps`
- Index: `categories_parent_id_foreign` on `parent_id`
- Legacy: `views_count` added via `2026_04_24_154433_add_views_count_to_categories_table.php` (category **page** visits, not listing lead events)

### 1.2 Is hierarchy used in production?

**Current database snapshot (local, 2026-06-11):**

| Metric | Value |
|--------|------:|
| Total categories | 13 |
| Root categories (`parent_id IS NULL`) | 13 |
| Child categories | 0 |
| Listings | 3 |
| Distinct listing category IDs | 1 (`سيارات`) |

**Conclusion:** Hierarchy is **modeled and admin-enabled** but **not populated or relied upon** in current production-like data.

### 1.3 Where listings attach: parent or leaf?

| Flow | Category selector | Target level |
|------|-------------------|--------------|
| Filament `ListingForm` | `Category::whereNull('parent_id')->where('is_active', true)` | **Root only** |
| Frontend `HomeController::store()` | `category_id` validated via `exists:categories,id` (any ID) | Any category, but UI typically roots |
| Frontend search / grid filters | `Category::whereNull('parent_id')` | **Root only** |
| Admin `CategoryResource` index | `whereNull('parent_id')` | Roots; children via `ChildrenRelationManager` |

**Conclusion:** Listings attach to **`listings.category_id` as stored** — today that is effectively **root categories only**. Subcategories can be created in admin (`ChildrenRelationManager`) but are not offered in listing forms.

### 1.4 CategoryController behavior

**File:** `app/Http/Controllers/Frontend/CategoryController.php`

```php
$listings = $category->listings()->where('status', 'published')->latest()->paginate(12);
```

Comment mentions subcategories (“مع الأقسام الفرعية لو حبيت”) but code loads **direct listings only** — no rollup into parent category pages.

### 1.5 Is category rollup required?

| Scenario | Rollup needed? |
|----------|----------------|
| **MVP (current data)** | **No** — flat roots, listings on roots |
| Future subcategories with listings on leaves | **Yes** — parent dashboards would under-count without rollup |
| Future parent pages including child listings | **Yes** — `CategoryController` would also need change |

### 1.6 Recommendation

**MVP: Option A — `GROUP BY listings.category_id`**

- Matches how listings are stored today
- Matches existing `CategoriesChartWidget` join pattern
- Fastest to implement and verify
- Avoids recursive tree logic and double-counting bugs

**Post-MVP:** Introduce parent rollup only when:
1. Listing forms allow subcategory selection, **and**
2. Product defines rollup rules (direct children only vs full `allChildren()` tree)

---

## 2. Listing Relationships

**File:** `app/Models/Listing.php`

| Relationship | Status | Target |
|--------------|--------|--------|
| `category()` | ✅ Present | `Category` via `category_id` |
| `views()` | ✅ Present | `ListingView` |
| `phoneClicks()` | ✅ Present | `ListingPhoneClick` |
| `whatsappClicks()` | ✅ Present | `ListingWhatsappClick` |
| `offers()` | ❌ **Missing** | `Offer` has inverse `listing()` only |

### Impact of missing `offers()`

| Impact | Severity | Mitigation |
|--------|----------|------------|
| Cannot use `withCount('offers')` with date scope on `Listing` | Low | `Offer::query()` subquery or join grouped by `listing_id` |
| Category widget cannot `$listing->offers()` in PHP loops | None if SQL aggregation used | Same pattern as `TopListingsWidget` (`orWhereExists` on `offers`) |
| Code consistency / maintainability | Medium | Optional Phase 3C-A: add `offers(): HasMany` to `Listing` |

**Not a blocker** for widget implementation.

### Legacy columns on `listings` (do not use)

- `views_count` — legacy counter (synced with deduped views per Phase 3B fix)
- `whatsapp_clicks` — legacy counter

CategoryPerformanceWidget must use event tables only, same rule as Phase 3B.

---

## 3. Analytics Sources

### 3.1 Event tables

#### `listing_views`

| Field | Notes |
|-------|-------|
| `listing_id`, `user_id`, `ip_address`, `created_at` | No `updated_at` |
| Indexes | `(listing_id, created_at)`, `(user_id, created_at)`, `created_at` |

#### `listing_phone_clicks`

| Field | Notes |
|-------|-------|
| `listing_id`, `user_id`, `created_at` | Dedup: 1h per user (`ListingLeadTrackingService`) |
| Indexes | Same pattern as views |

#### `listing_whatsapp_clicks`

| Field | Notes |
|-------|-------|
| `listing_id`, `user_id`, `created_at` | Dedup: 1h per user |
| Indexes | Same pattern as views |

#### `offers`

| Field | Notes |
|-------|-------|
| `listing_id`, `sender_id`, `receiver_id`, `amount`, `message`, `status`, `created_at`, `updated_at` | Full timestamps |
| Indexes | `listing_id` FK, `created_at` standalone (`offers_created_at_index`) |

**Migration sources:**

- `database/migrations/2026_06_11_000001_create_listing_lead_tracking_tables.php`
- `database/migrations/2026_06_11_000002_add_lead_funnel_analytics_indexes.php`
- `database/migrations/2026_05_15_091908_create_offers_table.php`

### 3.2 Capability matrix

| Capability | Supported? | How |
|------------|------------|-----|
| Category aggregation | ✅ Yes | Join event → `listings` → `GROUP BY listings.category_id` → join `categories` |
| Date filtering | ✅ Yes | `event.created_at >= :startDate` (same windows as Phase 3B) |
| CTR calculations | ✅ Yes | SQL `CASE WHEN views > 0 THEN phone * 100.0 / views ELSE 0 END` |
| Per-category offers | ✅ Yes | Join `offers` through `listings` |
| Direct category_id on events | ❌ No | Must always bridge through `listings` |

### 3.3 Current data volume

Local DB at audit time: **0 rows** in all four event tables. Widget will render empty/zero states until tracking accumulates data. Query design should still be validated with transactional test data (as Phase 3B did).

---

## 4. Category Aggregation Strategy

### Option A — `GROUP BY listings.category_id`

**How it works:** Each listing’s metrics roll up to the exact category ID stored on the listing row.

| Pros | Cons |
|------|------|
| Matches current listing assignment | Under-reports parent if listings move to subcategories later |
| Same as `CategoriesChartWidget` | Parent category pages don’t include child listing metrics |
| Simple SQL, easy to test | Admin may create subcategories that appear “empty” in analytics |
| No double-counting | |

### Option B — Parent category rollup

**How it works:** Map each listing’s `category_id` to root (or parent) via `COALESCE(categories.parent_id, categories.id)` or recursive descendant expansion.

| Pros | Cons |
|------|------|
| Executive view by top-level category | Complex with deep trees |
| Aligns with root-only navigation UX | Risk of double-count if listing attached to parent AND children modeled wrong |
| | `allChildren()` is eager-load recursion — expensive at query time |
| | No current data benefit (0 child categories) |

### Recommendation (MVP)

**Use Option A.**

Safest and fastest given:

1. Flat category data today  
2. Listing forms bind to root categories  
3. Existing chart widget already uses direct `category_id` grouping  
4. Phase 3B established listing-level aggregation patterns that map cleanly to `GROUP BY category_id`

**Future enhancement:** Add rollup as Phase 3C+ when subcategories carry listings; implement via SQL `CASE` mapping to root ID or a materialized `categories.root_id` column.

---

## 5. Lead Definition — Total Leads

### Options evaluated

| Option | Definition | Funnel alignment |
|--------|------------|------------------|
| **A** | Phone clicks only | First contact step only; ignores WhatsApp |
| **B** | Phone + WhatsApp clicks | Contact-intent leads (funnel steps 2–3) |
| **C** | Phone + WhatsApp + Offers | Mixes contact actions with negotiation/conversion |

### Context from existing widgets

**`LeadFunnelWidget`** treats each step separately:

- Views → Phone → WhatsApp → Offers  
- CTR metrics are **step-to-step**, not a combined “total leads / views”

**`TopListingsWidget`** ranks by **Offers**, then **phone CTR**, then views — offers are distinct from clicks.

### Recommendation

**Total Leads (MVP) = Option B: Phone clicks + WhatsApp clicks**

| Rationale |
|-----------|
| Represents user **contact intent** before commercial negotiation |
| Avoids counting offers as “leads” when offers are a downstream funnel stage |
| Keeps Offers as its **own column** (consistent with funnel widget and TopListings ranking signal) |
| Label in Arabic UI: **“إجمالي التواصل”** or **“عملاء محتملون (تواصل)”** to distinguish from offers |

**Display suggestion:** Show Phone, WhatsApp, and Offers as separate columns; compute Total Leads = Phone + WhatsApp for sorting/KPI, not for CTR denominator unless explicitly labeled as “Contact Rate.”

Option C is valid for a **“full funnel volume”** metric but blurs semantics; defer to a secondary column if needed.

---

## 6. CTR Definition

### CTR A — `(phone_clicks / views) × 100`

| Used by | Phase 3B `TopListingsWidget`, `LeadFunnelWidget` (“CTR مشاهدة → هاتف”) |
|---------|---------------------------------------------------------------------------|
| Pros | Cross-widget consistency; stable denominator; proven zero-safe pattern |
| Cons | Ignores WhatsApp in numerator |

### CTR B — `(total_leads / views) × 100` where total_leads = phone + whatsapp

| Pros | Reflects overall contact conversion per category |
|------|--------------------------------------------------|
| Cons | Breaks parity with Phase 3B ranking CTR; harder to compare listing vs category dashboards |

### Recommendation

**Primary CTR: CTR A — `(phone_clicks / views) × 100`**

| Reason |
|--------|
| Phase 3B already shipped this formula; admins expect consistent CTR meaning across lead analytics widgets |
| Phone is the first explicit contact action in the documented funnel |
| Zero/null protection already validated: `views <= 0 → 0%` |

**Optional secondary metric (non-MVP):** “Contact Rate” using CTR B as a separate column or description tooltip if product wants WhatsApp included in conversion numerator.

**Do not use** `categories.views_count` or `listings.views_count` as the views denominator.

---

## 7. Performance Analysis

### 7.1 Current indexes (verified via `SHOW INDEX`)

#### `listings`

| Index | Column(s) | Relevance |
|-------|-----------|-----------|
| `listings_category_id_foreign` | `category_id` | ✅ Category join / GROUP BY |
| `listings_user_id_foreign` | `user_id` | Low for this widget |
| Other FK indexes | province, location, car fields | Not used |

#### `categories`

| Index | Column(s) | Relevance |
|-------|-----------|-----------|
| `PRIMARY` | `id` | ✅ Final join |
| `categories_parent_id_foreign` | `parent_id` | Future rollup only |
| `categories_slug_unique` | `slug` | Not used |

#### Event tables (`listing_views`, `listing_phone_clicks`, `listing_whatsapp_clicks`)

| Index | Relevance |
|-------|-----------|
| `(listing_id, created_at)` | ✅ Listing-scoped date counts |
| `(created_at)` | ✅ Period scans before join |
| `(user_id, created_at)` | Not needed for category widget |

#### `offers`

| Index | Relevance |
|-------|-----------|
| `listing_id` FK | ✅ Join to listings |
| `created_at` | ✅ Period filter |

### 7.2 Join path cost

Category aggregation path:

```
event_table
  → JOIN listings ON listings.id = event.listing_id     [uses event.listing_id index]
  → GROUP BY listings.category_id                         [uses listings.category_id index]
  → JOIN categories ON categories.id = listings.category_id
```

No direct `category_id` on event tables — **bridge through listings is mandatory**.

### 7.3 Missing / optional indexes

| Index | Priority | When |
|-------|----------|------|
| `listings(category_id, status)` | Optional | If widget filters `published` only and table grows large |
| `listings(category_id, id)` covering | Low | Only if EXPLAIN shows category GROUP BY bottlenecks |
| Event `(created_at, listing_id)` | Low | Alternative scan order at very high event volume |
| `categories(root_id)` denormalized | Future | If parent rollup added |

### 7.4 Migration recommendation

**No mandatory migration before MVP.**

Optional pre-scale migration (document only):

```sql
-- Optional, not required for Phase 3C MVP
CREATE INDEX listings_category_id_status_index ON listings (category_id, status);
```

Existing indexes are sufficient for current data volume (0 event rows, 3 listings).

---

## 8. Existing Filament Dashboard

### 8.1 Registration order (`AdminPanelProvider`)

Explicit widget array (lead analytics cluster highlighted):

| Order | Widget | `$sort` |
|------:|--------|--------:|
| 1 | `MonetizationOverviewWidget` | 1 |
| 2 | `DailyRevenueWidget` | 2 |
| 3 | `RevenueAlertWidget` | 3 |
| 4 | `WeeklyRevenueChart` | 4 |
| 5 | `MonthlyRevenueWidget` | 5 |
| 6 | `ConversionMetricsWidget` | 6 |
| 7 | **`LeadFunnelWidget`** | **7** |
| 8 | **`TopListingsWidget`** | **8** |
| 9 | `StatsOverviewWidget` | 10 |
| 10 | `AccountWidget` | (default) |
| 11 | `ListingsChart` | 2 |
| 12 | `GovernoratesChartWidget` | 3 |
| 13 | `CategoriesChartWidget` | 4 |
| 14 | `BestSellingPlansChart` | 5 |

Note: Filament also **auto-discovers** widgets in `app/Filament/Admin/Widgets`; `$sort` controls dashboard ordering. Duplicate sort values exist across widgets (e.g. multiple `sort = 4`); Filament resolves by sort then registration/discovery order.

### 8.2 Related existing widget — `CategoriesChartWidget`

- **Metric:** Count of **published listings** per category (inventory, not leads)
- **Query:** `Listing JOIN categories GROUP BY categories.id` — direct `category_id`
- **No conflict** with CategoryPerformanceWidget; different metric family (inventory vs lead performance)

### 8.3 Placement recommendation

| Setting | Value |
|---------|-------|
| Register after | `TopListingsWidget` |
| `$sort` | **9** |
| `columnSpan` | `'full'` (match `TopListingsWidget`) |
| Cluster | Lead analytics: Funnel (7) → Top Listings (8) → **Category Performance (9)** |
| Authorization | `canView(): super_admin` (same as peer widgets) |
| Date filter UI | Reuse `resources/views/filament/admin/widgets/components/date-range-filter.blade.php` |
| Default filter | `last_7_days` |

---

## 9. Query Design Recommendation (No Code)

### 9.1 Target output columns

| Column | Source |
|--------|--------|
| Category name | `categories.name_ar` (or locale-aware accessor pattern) |
| Views | `COUNT(*)` from `listing_views` ⋈ `listings` |
| Phone clicks | `COUNT(*)` from `listing_phone_clicks` ⋈ `listings` |
| WhatsApp clicks | `COUNT(*)` from `listing_whatsapp_clicks` ⋈ `listings` |
| Offers | `COUNT(*)` from `offers` ⋈ `listings` |
| Total leads | `phone + whatsapp` (computed in SQL or presentation layer) |
| CTR | `(phone / NULLIF(views, 0)) * 100` |

### 9.2 Recommended SQL strategy

**Pattern: four grouped subqueries joined to `categories`**

```
┌─────────────────────────────────────────────────────────────┐
│  views_by_cat     = SELECT category_id, COUNT(*)            │
│                     FROM listing_views v                    │
│                     JOIN listings l ON l.id = v.listing_id  │
│                     WHERE v.created_at >= :start            │
│                     GROUP BY l.category_id                  │
├─────────────────────────────────────────────────────────────┤
│  phone_by_cat     = (same pattern on listing_phone_clicks)   │
│  whatsapp_by_cat  = (same pattern on listing_whatsapp_clicks)│
│  offers_by_cat    = (same pattern on offers)                │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
        SELECT c.id, c.name_ar,
               COALESCE(v.cnt,0) AS views_count,
               COALESCE(p.cnt,0) AS phone_clicks_count,
               ...
               CASE WHEN COALESCE(v.cnt,0) > 0
                    THEN COALESCE(p.cnt,0) * 100.0 / v.cnt
                    ELSE 0 END AS ctr
        FROM categories c
        LEFT JOIN views_by_cat v ON v.category_id = c.id
        LEFT JOIN phone_by_cat p ON p.category_id = c.id
        ...
        WHERE (activity predicate — at least one metric > 0)
        ORDER BY total_leads DESC, ctr DESC, views_count DESC
        LIMIT N (e.g. top 10 or all active categories)
```

### 9.3 Why subqueries over correlated counts per category row

| Approach | Verdict |
|----------|---------|
| Correlated subquery per category (N categories) | Acceptable at ~13 categories |
| **Grouped subquery joined once** | ✅ Preferred — single scan per event table |
| Load all events into PHP | ❌ Forbidden |
| N+1 per category | ❌ Forbidden |

At Nilex’s current scale (~13 categories), both grouped subqueries and `withCount` on a filtered listing set work; grouped subqueries scale better as categories grow.

### 9.4 Ranking recommendation

Align with Phase 3B philosophy (volume over spike CTR):

1. **Total leads (phone + whatsapp) DESC** — primary category KPI  
2. **Offers DESC** — commercial depth  
3. **CTR A DESC** — efficiency tie-breaker  
4. **Views DESC** — reach tie-breaker  

Alternative: sort by views DESC if product prioritizes traffic over contact volume. Document choice in Phase 3C implementation spec.

### 9.5 Row inclusion rules

| Rule | Recommendation |
|------|----------------|
| Include categories with zero activity | **No** for MVP table (keeps dashboard clean) |
| Include inactive categories | **No** — filter `categories.is_active = true` |
| Filter listings by status | **Optional** — include all statuses for lead truth, or restrict to `published` if product wants marketplace-only metrics (document decision in 3C spec) |

### 9.6 Filament integration

- **Widget type:** `Filament\Widgets\TableWidget`
- **Query entry:** `table()->query(fn () => ...)` returning Eloquent/Query builder on `Category` or wrapped subquery
- **Custom view:** Mirror `top-listings-widget.blade.php` for date filter in section header
- **Pagination:** `paginated(false)` if showing fixed top-N categories

---

## 10. Risks

### 10.1 Hierarchy risks

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Subcategories added; listings attach to leaves | Medium | Parent categories show zero in Option A | Defer rollup; monitor category depth |
| `CategoryController` doesn’t roll up listings | Medium | Frontend vs analytics mismatch | Document; fix controller when rollup added |
| `allChildren()` used in aggregation | Low | Performance / memory issues | Never use eager recursion for SQL aggregation |

### 10.2 Data consistency risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Legacy `listings.views_count` used by mistake | Inflated views vs event table | Code review: event tables only |
| Legacy `categories.views_count` confused with listing views | Wrong metric (page hits) | Do not use in widget |
| Dedup windows in `ListingLeadTrackingService` | Category totals reflect deduped events (correct) | Document in widget description |
| Guest phone/WhatsApp clicks not deduped by IP | Slightly higher click counts vs views | Known service behavior; consistent across widgets |

### 10.3 Performance risks

| Risk | When | Mitigation |
|------|------|------------|
| Full table scan on events for 30-day window | High event volume | Existing `created_at` indexes; grouped subqueries |
| Join explosion | Very large listings table | Filter by date on events first, then join |
| Dashboard load with many widgets | Always | Keep one query with subqueries, no polling unless needed |

### 10.4 Legacy counter risks

| Counter | Location | Widget rule |
|---------|----------|-------------|
| `listings.views_count` | Listings table | ❌ Do not use |
| `listings.whatsapp_clicks` | Listings table | ❌ Do not use |
| `categories.views_count` | Categories table | ❌ Do not use |

### 10.5 Funnel interpretation risks

| Risk | Mitigation |
|------|------------|
| Admin interprets Total Leads as including offers | Label clearly; keep Offers separate |
| CTR compared to LeadFunnel global CTR | Category CTR is per-category; global funnel is platform-wide |
| High CTR / low volume category outranks high-volume | Use multi-key ranking (leads → offers → CTR → views) |

---

## Proposed Implementation Plan

### Phase 3C-A — Readiness & conventions (low risk)

**Goal:** Align models and docs before widget code.

| Step | Action |
|------|--------|
| 1 | Confirm lead-tracking migrations applied in all environments |
| 2 | *(Optional)* Add `offers(): HasMany` to `Listing` for consistency with other event relations |
| 3 | Freeze metric definitions in widget docstring: event tables only, CTR A, Total Leads = phone + whatsapp |
| 4 | Decide listing status filter (all listings vs `published` only) and document in implementation spec |
| 5 | No mandatory index migration; schedule optional `listings(category_id, status)` if EXPLAIN shows need |

**Exit criteria:** Metric definitions signed off; no schema blockers.

---

### Phase 3C-B — CategoryPerformanceWidget core

**Goal:** Implement widget matching Phase 3B patterns.

| Step | Action |
|------|--------|
| 1 | Create `CategoryPerformanceWidget` extending `Filament\Widgets\TableWidget` |
| 2 | Set `$sort = 9`, `columnSpan = 'full'`, `canView()` → `super_admin` |
| 3 | Add date filter property default `last_7_days` + `getFilters()` (Today / 7d / 30d) |
| 4 | Create blade wrapper reusing `date-range-filter` component (copy `top-listings-widget` pattern) |
| 5 | Implement grouped subquery strategy: four metric subqueries ⋈ `categories` |
| 6 | Columns: category name, views, phone, whatsapp, offers, total leads, CTR (formatted `12.45%`) |
| 7 | Ranking: total leads DESC → offers DESC → CTR DESC → views DESC |
| 8 | Restrict to categories with activity in period + `is_active = true` |
| 9 | `paginated(false)`; sortable columns where SQL aliases allow |

**Exit criteria:** Widget renders on admin dashboard; single-query aggregation; no legacy counters.

---

### Phase 3C-C — Registration, verification, report

**Goal:** Ship with proof.

| Step | Action |
|------|--------|
| 1 | Register `CategoryPerformanceWidget` in `AdminPanelProvider` immediately after `TopListingsWidget` |
| 2 | Run transactional verification (insert synthetic events across 2+ categories → assert counts vs direct SQL → rollback) |
| 3 | Verify ranking: high-lead category beats high-CTR/low-volume category |
| 4 | Verify date filters change all metrics |
| 5 | Create `docs/reports/phase_3c_category_performance_widget_report.md` with PASS/FAIL |
| 6 | Manual UI check: Arabic labels, dark mode, filter live update |

**Exit criteria:** Verification PASS; implementation report filed.

---

## Final Recommendations Summary

| Topic | Decision |
|-------|----------|
| **Category grouping** | Option A — `GROUP BY listings.category_id` |
| **Total Leads** | Option B — Phone + WhatsApp clicks |
| **CTR formula** | CTR A — `(phone_clicks / views) × 100` |
| **Widget architecture** | Filament `TableWidget`, full width, `$sort = 9`, after `TopListingsWidget`, `super_admin` only, shared date-range filter blade |
| **Pre-migration** | None required for MVP |
| **Audit outcome** | **PASS — proceed to Phase 3C implementation** |

---

*End of audit. No application files were modified during this audit.*
