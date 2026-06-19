# Phase 5 Execution Report — Frontend Advertising Placements

**Date:** 2026-06-13  
**Project:** Nilex Marketplace  
**Scope:** Phase 5 ONLY (frontend placements, caching, impression/click tracking)  
**Status:** Complete — STOP before Phase 6

---

## Pre-Execution Validation

| Requirement | Status |
|-------------|--------|
| `AdCampaignService` | ✅ Exists — extended |
| Approved workflow (`approveCampaign` / `rejectCampaign`) | ✅ Exists — Phase 3B |
| `payment_status` workflow (`scopeDisplayable`, `isDisplayable()`) | ✅ Exists — Phase 1/2 |
| Caching support (`Cache::remember` per placement) | ✅ Exists — hardened in Phase 5 |

**Result:** Pre-check passed. Phase 5 was **not** blocked.

---

## 1. Placement Architecture

All four placements retrieve campaigns exclusively through `AdCampaignService::getForPlacement()`. No direct `AdCampaign` queries exist in Blade templates.

| Placement | Cache Key | View | Component |
|-----------|-----------|------|-----------|
| Hero Top Banner | `ad_campaign_hero_top` | `resources/views/frontend/home.blade.php` | `<x-ad-banner>` |
| Home Feed Banner | `ad_campaign_home_feed` | `resources/views/frontend/home.blade.php` (every 8 listings) | `<x-ad-banner>` |
| Category Page Banner | `ad_campaign_category_page_{id}` | `resources/views/frontend/category.blade.php` | `<x-ad-banner>` |
| Login Page Banner | `ad_campaign_login_page` | `resources/views/auth/login.blade.php` | `<x-ad-banner>` *(newly wired)* |

### Display gate (enforced in query + post-cache validation)

A campaign is shown only when **all** of the following hold:

```
payment_status = paid  OR  payment_status IS NULL (legacy admin campaigns)
approval_status = approved
starts_at <= now()
ends_at >= now()
deleted_at IS NULL  (SoftDeletes on model)
status = active       (when self_service_ads enabled)
```

`AdCampaign::isDisplayable()` is the single runtime validator. After cache retrieval, `getForPlacement()` re-checks displayability and evicts stale entries without a second DB query.

### Rendering component

`resources/views/components/ad-banner.blade.php`:

- Responsive `<picture>` with desktop/tablet/mobile WebP conversions
- Click-through via named route `ads.click`
- Impression beacon via named route `ads.impression` (DOMContentLoaded fetch)

---

## 2. Cache Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  Blade / Controller                                         │
│    AdCampaignService::getForPlacement($placement, $catId)   │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  Cache::remember(key, 300s)  →  ONE query per cycle         │
│  Keys via placementCacheKey()                               │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  Post-cache: isDisplayable()?  →  forget + return null      │
└─────────────────────────────────────────────────────────────┘
```

| Constant | Value |
|----------|-------|
| Placement cache TTL | 300 seconds |
| Tracking dedup TTL | 60 minutes |

### Invalidation triggers

| Event | Mechanism |
|-------|-----------|
| Approve | `AdCampaignService::approveCampaign()` → `clearCampaignCache()` |
| Reject | `AdCampaignService::rejectCampaign()` → `clearCampaignCache()` |
| Update | `AdCampaignObserver` → `clearCampaignCache()` (incl. old placement/category keys) |
| Delete | `AdCampaignObserver` → `clearCampaignCache()` |
| Expire | `campaigns:expire` command + observer on status change |
| Stale date window | `getForPlacement()` post-cache eviction |

`clearCampaignCache()` is public and delegates to `placementCacheKey()` for targeted key removal.

---

## 3. Impression Tracking

**Flow:**

```
Browser fetch → AdTrackingController@impression
  → isDisplayable() guard (404 if not)
  → AdCampaignService::trackImpression()
      → dedup key: ad_imp_{campaignId}_{ip}_{md5(ua)}
      → TTL: 60 minutes
      → TrackAdImpressionJob (queued)
          → increment views_count
          → AdCampaignLog (event_type: impression)
```

- Dedup prevents duplicate impressions from the same IP + User Agent + Campaign within 60 minutes.
- Job uses `WithoutOverlapping` middleware (60s lock) as a second guard.
- Processing is fully queued (`ShouldQueue`).

---

## 4. Click Tracking

**Flow:**

```
User click → AdTrackingController@click
  → AdCampaignService::trackClick()
      → isDisplayable() guard (redirect only, no count)
      → dedup key: ad_clk_{campaignId}_{ip}_{md5(ua)}
      → TTL: 60 minutes
      → TrackAdClickJob (queued)   ← NEW in Phase 5
          → increment clicks_count
          → AdCampaignLog (event_type: click)
  → redirect to target_url
```

Previously clicks were recorded synchronously in the service. Phase 5 moves persistence to `TrackAdClickJob` to match the impression pipeline and satisfy the queued-processing requirement.

---

## 5. Expiration

| Mechanism | Behaviour |
|-----------|-----------|
| `AdCampaignObserver::saving()` | Auto-sets `status = expired` when `ends_at < now()` |
| `campaigns:expire` command | Bulk-updates expired campaigns and clears placement cache |
| `scopeDisplayable` / `active` | `ends_at >= now()` excludes expired campaigns from queries |
| Post-cache validation | Evicts cached campaigns that fall outside the date window |

Expired campaigns never render on any of the four placements.

---

## 6. Files Created

| File | Purpose |
|------|---------|
| `app/Jobs/TrackAdClickJob.php` | Queued click persistence with dedup middleware |
| `reports/self_service_execution_phase5.md` | This report |

---

## 7. Files Modified

| File | Changes |
|------|---------|
| `app/Services/AdCampaignService.php` | `placementCacheKey()`, public `clearCampaignCache()` / `clearPlacementCache()`, post-cache display validation, queued click tracking, 60-min dedup TTL |
| `app/Observers/AdCampaignObserver.php` | Delegates cache invalidation to service (targeted keys incl. `login_page`) |
| `app/Console/Commands/ExpireCampaigns.php` | Clears placement cache after marking campaigns expired |
| `app/Http/Controllers/AdTrackingController.php` | `isDisplayable()` guard on impression endpoint |
| `resources/views/auth/login.blade.php` | Login Page Banner wired via `getForPlacement('login_page')` |
| `resources/views/components/ad-banner.blade.php` | Impression beacon uses named route instead of hardcoded URL |

### Files unchanged (already compliant from prior phases)

| File | Notes |
|------|-------|
| `resources/views/frontend/home.blade.php` | Hero + Home Feed already use `AdCampaignService` |
| `resources/views/frontend/category.blade.php` | Category banner already uses `AdCampaignService` |
| `app/Jobs/TrackAdImpressionJob.php` | Existing queued impression job reused |
| `routes/web.php` | `ads.impression` / `ads.click` routes already registered |

---

## 7. Manual Testing Checklist

### Display
- [ ] Unpaid self-service campaign never shows on any placement
- [ ] Paid + pending approval never shows
- [ ] Paid + approved + in date range shows on all 4 placements
- [ ] Expired campaign never shows (even if cache was warm)
- [ ] Soft-deleted campaign never shows

### Caching
- [ ] Second page load within 300s hits cache (verify with query log)
- [ ] Admin approve/reject clears placement immediately
- [ ] `campaigns:expire` clears affected placement keys

### Tracking
- [ ] Impression dedup within 60 min (same IP + UA)
- [ ] Click dedup within 60 min (same IP + UA)
- [ ] Jobs processed on queue worker (`php artisan queue:work`)

### Login placement
- [ ] Banner visible on `/login` when an approved, paid, in-range `login_page` campaign exists
- [ ] Banner absent when no qualifying campaign exists

---

**STOP.** Awaiting approval before Phase 6.
