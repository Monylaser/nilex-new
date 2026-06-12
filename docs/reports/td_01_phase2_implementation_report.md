# TD-01 Phase 2 Implementation Report — Seller Analytics Alignment

**Project:** Nilex Marketplace  
**Date:** 2026-06-12  
**Debt ID:** TD-01 (Phase 2 only)  
**Reference audit:** `docs/reports/td_01_seller_admin_analytics_alignment_audit.md`  
**Prior phase:** `docs/reports/td_01_phase1_implementation_report.md`  
**Scope:** Additive event-table analytics layer for seller dashboard

---

## Executive Summary

TD-01 Phase 2 is **complete**. A new `SellerListingAnalyticsService` reads engagement metrics from event tables (`listing_views`, `listing_phone_clicks`, `listing_whatsapp_clicks`) scoped to listings owned by the seller. `UserDashboard` exposes three **new** stats keys alongside unchanged legacy keys.

**Result:** Sellers now have event-based totals available in the dashboard `$stats` array, aligned with the same source of truth admin BI uses. Legacy `views` and `clicks` keys remain unchanged for backward compatibility. No routes, widgets, menus, resources, or permissions were removed or modified.

**Verdict:** **PASS**

---

## Files Changed

| File | Change type | Description |
|------|-------------|-------------|
| `app/Services/SellerListingAnalyticsService.php` | **New** | Event-table aggregation service for seller-owned listings |
| `app/Livewire/Frontend/UserDashboard.php` | Modified (additive) | Merges event stats into `$stats` array; legacy keys preserved |
| `tests/Feature/Dashboard/SellerListingAnalyticsServiceTest.php` | **New** | 8 feature tests for service + dashboard integration |

**Not modified (per scope):**

- `resources/views/livewire/frontend/user-dashboard.blade.php` — UI still displays legacy `views` / `clicks` only (deferred UI update)
- `app/Services/ListingLeadTrackingService.php` — write path unchanged
- `app/Http/Controllers/ListingController.php` — tracking endpoints unchanged
- Filament admin widgets, routes, migrations, permissions
- `tests/Feature/Dashboard/UserDashboardStatsTest.php` — legacy contract tests unchanged

---

## Architectural Decisions

### 1. Scope events by listing ownership, not viewer identity

Event tables store `user_id` as the **viewer/clicker**, not the listing owner. The service counts events via `whereHas('listing', fn ($q) => $q->where('user_id', $user->id))` rather than through `User::listingViews()` (which would count events *initiated by* the seller, not *received on* their listings).

This matches admin widget aggregation logic and the audit recommendation.

### 2. Lifetime totals (no date filter)

Seller dashboard legacy stats are lifetime sums. Phase 2 event stats use the same lifetime semantics (all events, no `created_at` filter) to keep the new layer comparable in scope. Date-range filtering is deferred to a future phase.

### 3. Additive stats keys only

| Key | Source | Status |
|-----|--------|--------|
| `views` | `sum(listings.views_count)` | **Unchanged** |
| `clicks` | `sum(listings.whatsapp_clicks)` | **Unchanged** |
| `views_events` | `COUNT(listing_views)` on seller listings | **New** |
| `phone_clicks` | `COUNT(listing_phone_clicks)` on seller listings | **New** |
| `whatsapp_clicks_events` | `COUNT(listing_whatsapp_clicks)` on seller listings | **New** |

Replacing legacy keys would break existing Blade templates and `UserDashboardStatsTest` assertions. Additive keys allow gradual UI migration.

### 4. Service resolution via container

`UserDashboard` resolves `SellerListingAnalyticsService` via `app()` rather than constructor injection, matching the existing Livewire component style (no DI refactor of unrelated methods).

### 5. `getDashboardStats()` convenience method

Bundles the three event totals into a single array with stable key names, reducing duplication in the Livewire component and providing a reusable API for future seller-facing features (API, per-listing breakdown, etc.).

---

## Code Summary

### SellerListingAnalyticsService

```php
public function totalViewsForUser(User $user): int
{
    return (int) ListingView::query()
        ->whereHas('listing', fn ($query) => $query->where('user_id', $user->id))
        ->count();
}
```

Phone and WhatsApp methods follow the same pattern using `ListingPhoneClick` and `ListingWhatsappClick`.

### UserDashboard integration (additive)

```php
$eventStats = app(SellerListingAnalyticsService::class)->getDashboardStats($user);

$stats = [
    // ... existing keys unchanged ...
    'views'    => Listing::where('user_id', $user->id)->sum('views_count'),
    'clicks'   => Listing::where('user_id', $user->id)->sum('whatsapp_clicks'),
    'views_events'           => $eventStats['views_events'],
    'phone_clicks'           => $eventStats['phone_clicks'],
    'whatsapp_clicks_events' => $eventStats['whatsapp_clicks_events'],
];
```

---

## Compatibility Analysis

| Change | Breaks existing behavior? | Notes |
|--------|---------------------------|-------|
| New `SellerListingAnalyticsService` | No | New file, no side effects |
| New `$stats` keys on dashboard | No | Additive; Blade ignores unknown keys |
| Legacy `views` / `clicks` keys | No | Same computation as before |
| `UserDashboardStatsTest` (11 tests) | No | All pass unchanged |
| Public listing badges (`views_count`) | No | Not touched |
| Admin Filament widgets | No | Not touched |
| Routes / permissions / navigation | No | Not touched |

**Known divergence (expected, documented):**

- Legacy `views` may exceed `views_events` on listings with pre-fix view inflation
- Legacy `clicks` may lag `whatsapp_clicks_events` on pre-Phase-1 traffic (no backfill yet)
- Event totals include deduplicated events only (service write path); legacy columns mirror deduped writes post-fix

---

## Tests Before / After

| Metric | Before (Phase 1) | After (Phase 2) | Delta |
|--------|------------------|-----------------|-------|
| Total tests | 150 | **158** | +8 |
| Passed | 150 | **158** | — |
| Failed | 0 | **0** | — |
| Assertions | 357 | **371** | +14 |
| Duration | ~93s | ~42s | Environment variance |

**Command:**

```bash
php artisan test
# Tests: 158 passed (371 assertions)
```

### New tests (`SellerListingAnalyticsServiceTest.php`)

| Test | Coverage |
|------|----------|
| Zero totals when seller has no listings | Empty-state guard |
| Zero totals when listings have no events | Empty events guard |
| Aggregates views across multiple seller listings | Multi-listing views |
| Aggregates phone clicks across multiple seller listings | Phone click aggregation |
| Aggregates whatsapp clicks across multiple seller listings | WhatsApp aggregation |
| Does not count events on other sellers' listings | Isolation / scoping |
| `getDashboardStats()` returns all event keys | Convenience method |
| Dashboard exposes event stats while preserving legacy keys | Livewire integration |

### Unchanged regression suites

| Suite | Tests | Status |
|-------|-------|--------|
| `UserDashboardStatsTest` — legacy `views` / `clicks` | 11 | PASS |
| `ListingWorkflowTest` — TD-01 Phase 1 WhatsApp dual-write | 3 | PASS |
| All other suites | 136 | PASS |

---

## Risks

| Risk | Severity | Status |
|------|----------|--------|
| Event stats visible in `$stats` but not in Blade UI yet | Low | **Open** — keys available; UI update deferred |
| Legacy vs event divergence confuses sellers when UI switches | Medium | **Open** — document before UI migration |
| Historical gap (pre-fix / pre-dual-write data) | Medium | **Open** — Phase 3 backfill |
| Query performance at scale (3 COUNT subqueries per dashboard load) | Low | Indexed `listing_id`; cache deferred |
| Accidental use of `User::listingViews()` for seller stats | Low | **Mitigated** — service uses listing ownership scope |

---

## Deferred Items

| Item | Phase | Notes |
|------|-------|-------|
| Blade UI — display event stats / phone card | Phase 2b or 3 | Requires design approval |
| Per-listing event counts in dashboard table rows | Phase 2b | `perListingStats()` not implemented yet |
| Date-range filters on seller stats | Future | Admin parity feature |
| Historical backfill script | Phase 3 | `scripts/backfill_listing_legacy_counters.php` |
| Remove legacy column reads | Post-backfill | Requires explicit approval |
| Replace public `views_count` badges | Future | Out of TD-01 scope |

---

## Deliverables

| Deliverable | Status |
|-------------|--------|
| `SellerListingAnalyticsService` created | ✅ |
| `totalViewsForUser()` | ✅ |
| `totalPhoneClicksForUser()` | ✅ |
| `totalWhatsappClicksForUser()` | ✅ |
| `getDashboardStats()` | ✅ |
| Additive dashboard stats keys | ✅ |
| No existing functionality removed | ✅ |
| Feature tests (8 new) | ✅ |
| Full test suite green | ✅ |
| Implementation report | ✅ |

---

## Final Status

## PASS

TD-01 Phase 2 additive seller analytics layer is implemented, tested, and verified. Legacy dashboard behavior is fully preserved. Event-based stats are available via new `$stats` keys for future UI integration.

---

*End of TD-01 Phase 2 Implementation Report*
