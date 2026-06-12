# Phase 3C — CategoryPerformanceWidget Report

**Project:** Nilex Marketplace  
**Date:** 2026-06-11  
**Widget:** `CategoryPerformanceWidget` (Filament TableWidget)  
**Audit reference:** `docs/reports/phase_3c_category_performance_audit.md`  
**Status:** PASS

---

## 1. Files Created

| File | Purpose |
|------|---------|
| `app/Filament/Admin/Widgets/CategoryPerformanceWidget.php` | Admin table widget with SQL-aggregated category lead metrics |
| `resources/views/filament/admin/widgets/category-performance-widget.blade.php` | Nilex-styled wrapper with date-range filter (matches `TopListingsWidget`) |
| `scripts/verify_category_performance_widget.php` | Transactional verification script (insert → assert → rollback) |
| `docs/reports/phase_3c_category_performance_widget_report.md` | This report |

---

## 2. Files Modified

| File | Change |
|------|--------|
| `app/Providers/Filament/AdminPanelProvider.php` | Registered `CategoryPerformanceWidget::class` immediately after `TopListingsWidget::class` |

---

## 3. Query Strategy

Two-layer SQL aggregation — all counting happens in the database. Category grouping uses **Option A**: `GROUP BY listings.category_id` (no parent rollup).

### Layer 1 — Four grouped metric subqueries

Each subquery joins an event table to `listings`, filters by `created_at >= :startDate`, and groups by `listings.category_id`:

| Subquery alias | Source table | Output column |
|----------------|--------------|---------------|
| `v` | `listing_views` | `views_count` |
| `p` | `listing_phone_clicks` | `phone_clicks_count` |
| `w` | `listing_whatsapp_clicks` | `whatsapp_clicks_count` |
| `o` | `offers` | `offers_count` |

These are `LEFT JOIN`ed onto `categories` (active only). Rows with zero activity in the period are excluded. Computed columns:

```sql
total_leads = COALESCE(p.phone_clicks_count, 0) + COALESCE(w.whatsapp_clicks_count, 0)
```

### Layer 2 — CTR + ranking wrapper

Outer query wraps Layer 1 via `fromSub(..., 'categories')` and computes:

```sql
CASE WHEN categories.views_count > 0
     THEN (categories.phone_clicks_count * 100.0 / categories.views_count)
     ELSE 0
END AS ctr
```

Then applies ranking + cap:

```sql
ORDER BY total_leads DESC, ctr DESC, views_count DESC
LIMIT 10
```

### Metric definitions (audit-approved)

| Metric | Definition |
|--------|------------|
| Total Leads | Phone clicks + WhatsApp clicks (offers excluded) |
| CTR | `(phone_clicks / views) × 100`, zero-safe |
| Views / clicks / offers | Event table counts only — **not** `listings.views_count`, `listings.whatsapp_clicks`, or `categories.views_count` |

### Date filters

| Filter | Start date |
|--------|------------|
| Today | `Carbon::today()` |
| Last 7 Days (default) | `now()->subDays(6)->startOfDay()` |
| Last 30 Days | `now()->subDays(29)->startOfDay()` |

---

## 4. Performance Analysis

| Approach | Rationale |
|----------|-----------|
| Grouped subqueries per event table | Single scan per table; scales as categories grow |
| `leftJoinSub` on `categories` | One join per metric source, no per-category loops |
| `fromSub` wrapper | CTR alias available in `ORDER BY` without expression repeat |
| Activity pre-filter in Layer 1 | Only categories with period activity returned |
| Existing indexes | `(listing_id, created_at)` on view/click tables; `listings.category_id` FK |
| `limit(10)` in SQL | Top-N resolved in DB before Filament renders |
| `paginated(false)` | No extra pagination query for fixed top-10 widget |

**Verification:** Query log recorded **1 SQL query** for a full widget fetch — no N+1.

**Avoided:** Loading event rows into PHP, correlated counts per category in PHP loops, legacy counter columns.

---

## 5. Verification Results

Verification executed via `php scripts/verify_category_performance_widget.php` using transactional synthetic data (insert → query → assert → rollback).

### Metric accuracy (last 7 days)

| Category | Views | Phone | WhatsApp | Offers | Total Leads | CTR | Result |
|----------|------:|------:|---------:|-------:|------------:|----:|--------|
| Cat A (high volume) | 300 / 300 | 30 / 30 | 20 / 20 | 5 / 5 | 50 / 50 | 10.00% / 10.00% | PASS |
| Cat B (medium) | 100 / 100 | 10 / 10 | 5 / 5 | 3 / 3 | 15 / 15 | 10.00% / 10.00% | PASS |
| Cat C (high CTR) | 2 / 2 | 2 / 2 | 0 / 0 | 0 / 0 | 2 / 2 | 100.00% / 100.00% | PASS |
| Cat D (offers only) | 0 / 0 | 0 / 0 | 0 / 0 | 2 / 2 | 0 / 0 | 0% (zero views) | PASS |

Format: `widget / direct source-table count`.

Events outside the 7-day window (20 days ago) correctly excluded from 7d counts; included in 30d counts (+1 view on Cat A → 301 views in 30d filter).

### CTR calculation

- Zero views → CTR = 0% (Cat D verified via unrestricted aggregated query)
- Non-zero views → `(phone_clicks / views) × 100`, formatted as `12.45%`

### Date filters

| Filter | Behavior | Result |
|--------|----------|--------|
| Today | Cat A events (2 days ago) excluded | PASS |
| Last 7 Days | Primary metric verification window | PASS |
| Last 30 Days | Includes older events within 30-day window | PASS |

### Authorization

- `CategoryPerformanceWidget::canView()` returns true only for `super_admin` — consistent with `LeadFunnelWidget` and `TopListingsWidget`.

### Dashboard registration

- Registered in `AdminPanelProvider` after `TopListingsWidget`
- Widget sort order: `$sort = 9` (LeadFunnel = 7, TopListings = 8)

---

## 6. Ranking Validation

Primary sort (fixed in query):

1. **Total Leads DESC** — phone + WhatsApp contact intent
2. **CTR DESC** — `(phone_clicks / views) × 100`
3. **Views DESC** — reach tie-breaker

**Design intent verified:** Cat C (100% CTR, 2 total leads) ranks **below** Cat A (10% CTR, 50 total leads) and Cat B (10% CTR, 15 total leads).

| Expected order (category IDs) | Actual order | Result |
|-------------------------------|--------------|--------|
| Cat A → Cat B → Cat C | Cat A → Cat B → Cat C | PASS |

Cat D (0 leads, 2 offers) correctly excluded from top-10 display but present in unrestricted aggregated query.

---

## 7. PASS / FAIL Table

| Check | Status |
|-------|--------|
| Widget implemented as Filament TableWidget | PASS |
| Category grouping: `GROUP BY listings.category_id` (Option A) | PASS |
| Uses event tables only (not legacy counters) | PASS |
| Total Leads = phone + WhatsApp (offers separate) | PASS |
| Date filters (Today / 7d / 30d, default 7d) | PASS |
| CTR formula + zero/null protection | PASS |
| Ranking: Total Leads → CTR → Views | PASS |
| Top 10 limit | PASS |
| DB-side aggregation (grouped subqueries) | PASS |
| Single query — no N+1 | PASS |
| super_admin authorization | PASS |
| Dashboard registration order | PASS |
| Metric verification vs source tables | PASS |

---

## 8. Final Verdict

**Overall: PASS**

`CategoryPerformanceWidget` is implemented per Phase 3C audit decisions: direct category grouping, contact-intent total leads (phone + WhatsApp), Phase 3B-consistent CTR, SQL aggregation via grouped subqueries, date filters matching peer widgets, and `super_admin`-only visibility. Transactional verification confirms metric accuracy, ranking logic, date filtering, top-10 cap, and single-query performance.

---

*Verification artifact: `storage/app/category_performance_verification.json`*
