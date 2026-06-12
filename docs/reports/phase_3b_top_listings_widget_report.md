# Phase 3B — TopListingsWidget Report

**Project:** Nilex Marketplace  
**Date:** 2026-06-11  
**Widget:** `TopListingsWidget` (Filament TableWidget)  
**Status:** PASS

---

## 1. Files Created

| File | Purpose |
|------|---------|
| `app/Filament/Admin/Widgets/TopListingsWidget.php` | Admin table widget with aggregated lead metrics and ranking |
| `resources/views/filament/admin/widgets/top-listings-widget.blade.php` | Nilex-styled wrapper with date-range filter (matches `LeadFunnelWidget`) |
| `docs/reports/phase_3b_top_listings_widget_report.md` | This report |

---

## 2. Files Modified

| File | Change |
|------|--------|
| `app/Providers/Filament/AdminPanelProvider.php` | Registered `TopListingsWidget::class` immediately after `LeadFunnelWidget::class` |

---

## 3. Model & Relationship Audit (Pre-Implementation)

### Listing

| Item | Detail |
|------|--------|
| Event relationships | `views()` → `ListingView`, `phoneClicks()` → `ListingPhoneClick`, `whatsappClicks()` → `ListingWhatsappClick` |
| Missing relationship | No `offers()` HasMany (Offer defines inverse `listing()` only) |
| Legacy counters | `views_count` / `whatsapp_clicks` columns exist on `listings` but are **not used** by this widget |
| Widget usage | Subqueries + `whereHas` on event relationships; `orWhereExists` on `offers` table |

### ListingView

| Column | Notes |
|--------|-------|
| `listing_id`, `user_id`, `ip_address`, `created_at` | No `updated_at`; indexed on `(listing_id, created_at)` |

### ListingPhoneClick / ListingWhatsappClick

| Column | Notes |
|--------|-------|
| `listing_id`, `user_id`, `created_at` | Same shape; indexed on `(listing_id, created_at)` |

### Offer

| Column | Notes |
|--------|-------|
| `listing_id`, `sender_id`, `receiver_id`, `amount`, `message`, `status`, `created_at` | Filtered by `created_at` for period metrics |

**Source of truth:** `listing_views`, `listing_phone_clicks`, `listing_whatsapp_clicks`, `offers` event tables only.

---

## 4. Query Strategy

Two-layer SQL aggregation — all counting happens in the database.

### Layer 1 — Per-listing subquery aggregates

Inner query selects `listings.id`, `listings.title`, and four correlated `COUNT(*)` subqueries:

- `views_count` ← `listing_views` where `created_at >= :startDate`
- `phone_clicks_count` ← `listing_phone_clicks`
- `whatsapp_clicks_count` ← `listing_whatsapp_clicks`
- `offers_count` ← `offers`

Only listings with **any** activity in the selected period are included (`whereHas` / `orWhereExists`).

### Layer 2 — CTR + ranking wrapper

Outer query wraps Layer 1 via `fromSub(..., 'listings')` and computes:

```sql
CASE WHEN listings.views_count > 0
     THEN (listings.phone_clicks_count * 100.0 / listings.views_count)
     ELSE 0
END AS ctr
```

Then applies ranking + cap:

```sql
ORDER BY offers_count DESC, ctr DESC, views_count DESC
LIMIT 10
```

### Date filters

| Filter | Start date |
|--------|------------|
| Today (default UI label: اليوم) | `Carbon::today()` |
| Last 7 Days (default) | `now()->subDays(6)->startOfDay()` |
| Last 30 Days | `now()->subDays(29)->startOfDay()` |

---

## 5. Ranking Logic

Primary sort (fixed in query, not CTR-only):

1. **Offers DESC** — commercial intent wins
2. **CTR DESC** — `(phone_clicks / views) × 100`, zero-safe
3. **Views DESC** — tie-breaker on reach

**Design intent verified:** A listing with 1 view + 1 phone click (100% CTR, 0 offers) ranks **below** a listing with 500 views + 50 phone clicks + 20 offers (10% CTR).

---

## 6. Performance Notes

| Approach | Rationale |
|----------|-----------|
| Correlated subqueries per metric | Single round-trip; DB aggregates, no PHP rollups |
| `fromSub` wrapper | Allows CTR alias in `ORDER BY` without repeating expressions |
| Activity pre-filter | `whereHas` / `orWhereExists` limits inner scan to listings with events in range |
| Existing indexes | `(listing_id, created_at)` on all three click/view tables |
| `limit(10)` in SQL | Top-N resolved in DB before Filament renders |
| `paginated(false)` | No extra pagination query for a fixed top-10 widget |
| No N+1 | One query returns all columns; no per-row event loading |

**Avoided:** Loading event rows into PHP, using `listings.views_count` / `listings.whatsapp_clicks`, or `withCount` without date scopes on full relations.

---

## 7. Verification Results

Verification executed via transactional synthetic data (insert → query → assert → rollback) so production data stays untouched.

### Metric accuracy (last 7 days)

| Listing | Views | Phone | WhatsApp | Offers | CTR | Result |
|---------|------:|------:|---------:|-------:|----:|--------|
| سياره لادا | 500 / 500 | 50 / 50 | 10 / 10 | 20 / 20 | 10.00% / 10.00% | PASS |
| سياره لنكولن | 100 / 100 | 10 / 10 | 0 / 0 | 5 / 5 | 10.00% / 10.00% | PASS |
| سياره لادا 2323 | 1 / 1 | 1 / 1 | 0 / 0 | 0 / 0 | 100.00% / 100.00% | PASS |

Format: `widget / listing_* table direct count`.

### CTR calculation

- Zero views → CTR = 0% (no divide-by-zero)
- Non-zero views → `(phone_clicks / views) × 100`, formatted as `12.45%`

### Ranking logic

| Expected order (listing IDs) | Actual order | Result |
|------------------------------|--------------|--------|
| 14 → 16 → 15 | 14 → 16 → 15 | PASS |

Listing 15 (100% CTR, 0 offers) correctly placed last. Listing 14 (20 offers) correctly placed first.

### Authorization

- `TopListingsWidget::canView()` returns true only for `super_admin` — consistent with `LeadFunnelWidget` and other analytics widgets.

### Dashboard registration

- Registered in `AdminPanelProvider` after `LeadFunnelWidget`
- Widget sort order: `$sort = 8` (LeadFunnel = 7)

---

## 8. Sample Output Data

Top 3 rows from verification run (last 7 days filter):

| # | Title | Views | Phone | WhatsApp | Offers | CTR |
|---|-------|------:|------:|---------:|-------:|----:|
| 1 | سياره لادا | 500 | 50 | 10 | 20 | 10.00% |
| 2 | سياره لنكولن | 100 | 10 | 0 | 5 | 10.00% |
| 3 | سياره لادا 2323 | 1 | 1 | 0 | 0 | 100.00% |

---

## 9. PASS / FAIL Status

| Check | Status |
|-------|--------|
| Widget implemented as Filament TableWidget | PASS |
| Uses event tables only (not legacy counters) | PASS |
| Date filters (Today / 7d / 30d, default 7d) | PASS |
| CTR formula + zero/null protection | PASS |
| Ranking: Offers → CTR → Views | PASS |
| Top 10 limit | PASS |
| DB-side aggregation | PASS |
| super_admin authorization | PASS |
| Dashboard registration order | PASS |
| Metric verification vs source tables | PASS |

### Overall: **PASS**
