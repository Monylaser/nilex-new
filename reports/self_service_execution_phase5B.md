# Phase 5B Execution Report — Tracking, Popup Campaigns & Performance Hardening

**Date:** 2026-06-13  
**Project:** Nilex Marketplace  
**Scope:** Phase 5B ONLY (tracking security, Intersection Observer impressions, popup UX, cache hardening)  
**Status:** Complete — **PROJECT COMPLETE**

---

## Pre-Execution Validation

| Requirement | Status |
|-------------|--------|
| `AdCampaignService` | ✅ Exists |
| `resources/views/components/ad-banner.blade.php` | ✅ Exists |
| `ads.impression` / `ads.click` routes | ✅ Registered in `routes/web.php` |

**Result:** Pre-check passed. Phase 5B was **not** blocked.

---

## Tracking Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│  Frontend                                                           │
│    Banner: Intersection Observer (threshold 0.5) → ads.impression   │
│    Banner/Popup click: ads.click → redirect target_url              │
│    Popup: impression on modal open                                  │
└───────────────────────────────┬─────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│  AdTrackingController                                               │
│    ensureTrackable() → 404 if not active + approved + displayable   │
└───────────────────────────────┬─────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│  AdCampaignService::trackImpression() / trackClick()                │
│    Cache dedup: campaign_id + IP + md5(UserAgent) — TTL 60 min      │
│    Dispatch TrackAdImpressionJob / TrackAdClickJob (queued)         │
└───────────────────────────────┬─────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│  Queue workers                                                      │
│    increment views_count / clicks_count + AdCampaignLog row         │
└─────────────────────────────────────────────────────────────────────┘
```

### Tracking security (`404` gate)

`AdCampaign::isTrackable()` requires **all** of:

| Check | Rule |
|-------|------|
| Exists | Route model binding (soft-deleted → 404) |
| Active | `status = active` |
| Approved | `approval_status = approved` |
| Displayable | `isDisplayable()` — paid/null payment, in date window |

Applied in `AdTrackingController` **before** any tracking via `ensureTrackable()`. Invalid requests return HTTP 404 on both `ads.impression` and `ads.click`.

---

## Intersection Observer (Banner Impressions)

**Component:** `resources/views/components/ad-impression-tracker.blade.php` (included once per page via `@once`)

| Setting | Value |
|---------|-------|
| Target | `[data-ad-impression]` on banner `<a>` elements |
| Trigger | Element enters viewport |
| Threshold | `0.5` (50% visible) |
| Unobserve | After first qualifying intersection |
| Page load | **No** impression on load — only on viewport entry |

`ad-banner.blade.php` sets `data-ad-impression="{{ route('ads.impression', $campaign) }}"` and includes `<x-ad-impression-tracker />`.

---

## Duplicate Protection

| Event | Cache key pattern | TTL |
|-------|-------------------|-----|
| Impression | `ad_imp_{campaignId}_{ip}_{md5(ua)}` | 60 minutes |
| Click | `ad_clk_{campaignId}_{ip}_{md5(ua)}` | 60 minutes |

Secondary guard: `WithoutOverlapping` middleware on both queue jobs (60s lock).

---

## Click Tracking

| Component | Role |
|-----------|------|
| `TrackAdClickJob` | Queued click persistence |
| `ads.click` route | Validates trackable → dedup → dispatch job → redirect |

---

## Popup Campaigns

### Selection (single popup)

`AdCampaignService::getForPlacement('popup')`:

- One cached query per retrieval cycle
- `orderByDesc('priority')` → `inRandomOrder()` → `limit(1)` → `first()`
- Post-cache `isDisplayable()` validation
- **Never** renders multiple overlays

### UI (`resources/views/components/ad-popup.blade.php`)

| Requirement | Implementation |
|-------------|----------------|
| Framework | Alpine.js |
| Layout | Centered modal + dark overlay |
| Countdown | 5 seconds |
| Skip button | **تخطي** — disabled until countdown completes |
| Backdrop click | Disabled (no dismiss before countdown) |
| Impression | Fires when modal opens (display event) |

### localStorage frequency cap

| Key | `popup_last_seen` |
|-----|-------------------|
| Value | Unix timestamp (ms) |
| Rule | Show at most **once per 24 hours** per browser |
| Check | On `init()` — skip if elapsed < 86,400,000 ms |

Campaign retrieval uses `AdCampaignService::getForPlacement('popup')` only — no direct `AdCampaign` queries in Blade.

---

## Cache Strategy

### Placement retrieval

```
getForPlacement($placement, $categoryId)
  → Cache::remember(key, 300s)
  → ONE query per placement per cache cycle
  → eager load media
  → post-cache isDisplayable() eviction
```

| Key | Pattern |
|-----|---------|
| Generic | `ad_campaign_{placement}` |
| Category | `ad_campaign_category_page_{id}` |
| Popup | `ad_campaign_popup` |

**Rule:** No `AdCampaign` queries inside Blade loops. Banners call the service once per component render; home feed reuses a single cached `home_feed` key.

---

## Cache Invalidation

| Trigger | Mechanism |
|---------|-----------|
| Approved | `approveCampaign()` → model `saved` → observer |
| Rejected | `rejectCampaign()` → model `saved` → observer |
| Updated | `AdCampaignObserver::saved()` |
| Deleted | `AdCampaignObserver::deleted()` |
| Restored | `AdCampaignObserver::restored()` *(added Phase 5B)* |
| Expired | `saving()` auto-expire + `campaigns:expire` command → `saved` → observer |

All delegate to `queueCampaignCacheInvalidation()` → `InvalidateAdCampaignCacheJob` (queued, targeted key flush).

### Expiration hardening

| Layer | Behaviour |
|-------|-----------|
| Observer `saving()` | Sets `status = expired` when `ends_at < now()` |
| `scopeDisplayable` / `active` | Excludes past `ends_at` |
| `getForPlacement()` post-cache | Evicts stale entries, returns `null` |
| `isTrackable()` | Blocks tracking for expired campaigns |
| `campaigns:expire` | Bulk status update → cache invalidation via observer |

Expired campaigns **never** display or accept tracking events.

---

## Files Created

| File | Purpose |
|------|---------|
| `resources/views/components/ad-impression-tracker.blade.php` | Shared Intersection Observer script (`@once`) |
| `reports/self_service_execution_phase5B.md` | This report |

---

## Files Modified

| File | Changes |
|------|---------|
| `app/Models/AdCampaign.php` | Added `isTrackable()` |
| `app/Http/Controllers/AdTrackingController.php` | `ensureTrackable()` → 404 on both endpoints |
| `app/Services/AdCampaignService.php` | `isTrackable()` guards on tracking; `limit(1)` on placement query |
| `app/Observers/AdCampaignObserver.php` | Added `restored()` cache invalidation |
| `resources/views/components/ad-banner.blade.php` | `data-ad-impression` + Intersection Observer tracker |
| `resources/views/components/ad-popup.blade.php` | 5s countdown, تخطي button, `popup_last_seen` localStorage |

### Unchanged (prior phases — not in Phase 5B scope)

| Area | Notes |
|------|-------|
| Seller dashboard | Not modified |
| Filament admin | Not modified |
| Placement view injections | Not modified (`home`, `category`, `login` blades) |
| `TrackAdImpressionJob` | Reused |
| `TrackAdClickJob` | Reused |
| `routes/web.php` | Tracking routes unchanged |

---

## Manual Testing Checklist

### Tracking security
- [ ] Inactive / unapproved / unpaid / expired campaign → `ads.impression` returns 404
- [ ] Same campaign → `ads.click` returns 404
- [ ] Active + approved + paid + in-range → both endpoints succeed

### Intersection Observer
- [ ] No impression request until banner scrolls into view (≥50% visible)
- [ ] Second scroll into same banner does not re-fire (unobserve + dedup)

### Popup
- [ ] Only one popup renders when multiple `popup` campaigns exist
- [ ] تخطي disabled for 5 seconds, then enabled
- [ ] `popup_last_seen` prevents re-show within 24 hours
- [ ] No popup DOM when no qualifying campaign

### Cache
- [ ] Approve / reject / update / delete / restore clears placement cache
- [ ] `campaigns:expire` clears affected keys
- [ ] Expired campaign absent from all placements immediately after cache eviction

### Queue
- [ ] `TrackAdImpressionJob` / `TrackAdClickJob` processed by worker

---

**STOP. PROJECT COMPLETE.**

Self-Service Advertising Platform: Phases 1–6 + 5A + 5B fully implemented.  
Enable with `SELF_SERVICE_ADS=true` and run `php artisan queue:work`.
