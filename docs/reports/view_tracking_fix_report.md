# View Tracking Fix Report

**Project:** Nilex Marketplace  
**Date:** 2026-06-11  
**Issue:** `listings.views_count` inflated vs deduplicated `listing_views`  
**Architect fix:** Synchronize `views_count` with `ListingLeadTrackingService::recordView()` return value

---

## Summary

`ListingController::show()` previously incremented `views_count` on every page load, even when `recordView()` deduplicated the visit and did not insert a row into `listing_views`. This caused legacy view counters to diverge from lead analytics and the `LeadFunnelWidget` funnel data.

The fix gates `views_count` increments behind the boolean returned by `recordView()`, so both counters only advance when a new deduplicated view is recorded.

---

## Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/ListingController.php` | Wrap `$listing->increment('views_count')` in a conditional on `recordView()` returning `true` |

**Not modified (per scope):**

- `app/Services/ListingLeadTrackingService.php` (dedup windows unchanged)
- Lead tracking migrations / tables
- `LeadFunnelWidget` and other analytics widgets
- Phone, WhatsApp, and offer tracking

---

## Before vs After Behavior

### Before

```php
$leadTracking->recordView($listing, auth()->user(), $request->ip());
$listing->increment('views_count');
```

| Scenario | `listing_views` | `views_count` |
|----------|-----------------|---------------|
| First visit (guest or user) | +1 | +1 |
| Refresh within dedup window | unchanged | **+1 (bug)** |
| After N refreshes in 24h | 1 row | N rows worth of inflation |

**Result:** `views_count` > `listing_views` over time. Lead funnel analytics (which read `listing_views`) were correct, but seller dashboards and legacy counters using `views_count` were inflated.

### After

```php
if ($leadTracking->recordView($listing, auth()->user(), $request->ip())) {
    $listing->increment('views_count');
}
```

| Scenario | `listing_views` | `views_count` |
|----------|-----------------|---------------|
| First visit (guest or user) | +1 | +1 |
| Refresh within dedup window | unchanged | unchanged |
| New visit after dedup window expires | +1 | +1 |

**Result:** `views_count` stays aligned with deduplicated `listing_views` rows — single source of truth for view counts.

---

## Verification Results

Verification was executed via HTTP requests against `GET /listings/{listing}` on local MySQL (`nilex_platform`), using the same controller path as production.

### 1. First listing visit (guest)

| Metric | Before | After | Delta |
|--------|--------|-------|-------|
| `listing_views` | 0 | 1 | +1 |
| `views_count` | 0 | 1 | +1 |

**Result:** PASS

### 2. Refresh within dedup window (guest)

| Metric | Before | After | Delta |
|--------|--------|-------|-------|
| `listing_views` | 1 | 1 | 0 |
| `views_count` | 1 | 1 | 0 |

**Result:** PASS

### 3. First authenticated user visit (buyer)

| Metric | Before | After | Delta |
|--------|--------|-------|-------|
| `listing_views` | 1 | 2 | +1 |
| `views_count` | 1 | 2 | +1 |

**Result:** PASS

### 4. Authenticated refresh within dedup window (buyer)

| Metric | Before | After | Delta |
|--------|--------|-------|-------|
| `listing_views` | 2 | 2 | 0 |
| `views_count` | 2 | 2 | 0 |

**Result:** PASS

### Final sync check

After all scenarios:

| `views_count` | `listing_views` | In sync |
|---------------|-----------------|---------|
| 2 | 2 | Yes |

**Overall verification:** PASS

---

## Final Status

## PASS

The `views_count` synchronization bug is fixed. `views_count` now increments only when `ListingLeadTrackingService::recordView()` inserts a new deduplicated view, matching `listing_views` behavior for both guest (IP-based) and authenticated (user-based) visits.
