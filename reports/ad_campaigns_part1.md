# Ad Campaigns — Part 1 Implementation Report

**Date:** 2026-06-13  
**Scope:** Database, Models, Observer, Service Layer, Queued Job  
**Status:** Complete

---

## Files Created

| File | Purpose |
|------|---------|
| `database/migrations/2026_06_13_000001_create_ad_campaigns_table.php` | `ad_campaigns` table with placement, approval workflow, scheduling, metrics, soft deletes |
| `database/migrations/2026_06_13_000002_create_ad_campaign_logs_table.php` | `ad_campaign_logs` table for impression/click events (created_at only) |
| `app/Models/AdCampaign.php` | Campaign model with media, scopes, accessors, approve/reject helpers |
| `app/Models/AdCampaignLog.php` | Event log model (`UPDATED_AT = null`) |
| `app/Observers/AdCampaignObserver.php` | Auto-expire + cache invalidation on lifecycle events |
| `app/Services/AdCampaignService.php` | Placement resolution, tracking, stats, admin approval/rejection |
| `app/Jobs/TrackAdImpressionJob.php` | Queued impression counter + log writer with `WithoutOverlapping` |
| `reports/ad_campaigns_part1.md` | This report |

---

## Files Modified

### `app/Providers/AppServiceProvider.php`

**`register()`** — added singleton registration:

```php
$this->app->singleton(AdCampaignService::class);
```

**`boot()`** — added observer registration:

```php
AdCampaign::observe(AdCampaignObserver::class);
```

**Imports added:** `AdCampaign`, `AdCampaignObserver`, `AdCampaignService`

No other existing files were modified.

---

## Migration Status

```
✓ 2026_06_13_000001_create_ad_campaigns_table  — DONE
✓ 2026_06_13_000002_create_ad_campaign_logs_table — DONE
```

Both migrations ran successfully via `php artisan migrate`.

### `ad_campaigns` schema summary

- **Placement enum:** `hero_top`, `home_feed`, `category_page`, `listing_detail`, `search_results`
- **Status enum:** `draft` (default), `scheduled`, `active`, `paused`, `expired`
- **Approval enum:** `pending` (default), `approved`, `rejected`
- **Foreign keys:** `category_id` → `categories` (nullOnDelete), `created_by` → `users` (nullOnDelete)
- **Metrics:** `views_count`, `clicks_count` (unsigned, default 0)
- **Scheduling:** `starts_at`, `ends_at` (nullable timestamps)
- **Soft deletes** enabled

### `ad_campaign_logs` schema summary

- **Foreign keys:** `ad_campaign_id` (cascadeOnDelete), `user_id` (nullOnDelete)
- **Event enum:** `impression`, `click`
- **Timestamps:** `created_at` only (no `updated_at`)

---

## `approval_status` Flow

```
┌─────────┐     admin approves      ┌──────────┐
│ pending │ ──────────────────────► │ approved │
└─────────┘                         └──────────┘
     │                                    │
     │ admin rejects                      │ scopeActive() requires
     ▼                                    │ approval_status = 'approved'
┌──────────┐                              │ AND status = 'active'
│ rejected │                              │ AND date window valid
└──────────┘                              ▼
     │                              Shown on frontend
     │ rejected_reason stored
     ▼
Not served by getForPlacement()
```

### States

| `approval_status` | Meaning | Frontend visibility |
|-------------------|---------|---------------------|
| `pending` | Awaiting admin review (default on create) | Hidden — `scopeActive()` excludes |
| `approved` | Admin approved; eligible if also `active` + in date range | Visible via `AdCampaignService::getForPlacement()` |
| `rejected` | Admin rejected with `rejected_reason` | Hidden permanently until re-submitted/re-approved |

### Transitions

- **`approve()`** — sets `approval_status = 'approved'`, clears `rejected_reason`
- **`reject($reason)`** — sets `approval_status = 'rejected'`, stores `rejected_reason`
- **`approveCampaign()` / `rejectCampaign()`** — wrap the above in a DB transaction, log admin action, clear placement cache

### Critical gate

`scopeActive()` enforces **both** `status = 'active'` **and** `approval_status = 'approved'`. A campaign can be `status = 'active'` but still invisible if approval is `pending` or `rejected`.

---

## Component Overview

### AdCampaign Model

- **Media:** Spatie `ad_image` collection (single file) with `desktop` (1200×400), `tablet` (768×256), `mobile` (390×130) WebP conversions
- **Scopes:** `active`, `byPlacement`, `pending`, `approved`, `rejected`
- **Accessors:** `ctr`, `is_approved`, `is_pending`, `is_rejected`
- **Auto-expire:** Observer sets `status = 'expired'` when `ends_at` is in the past

### AdCampaignService

| Method | Behavior |
|--------|----------|
| `getForPlacement()` | 5-min cache, `active()` scope, priority DESC + random rotation |
| `trackImpression()` | 1-hour IP+UA dedup → dispatches `TrackAdImpressionJob` |
| `trackClick()` | 1-hour dedup, sync increment + log, returns target URL |
| `getDailyStats()` | Last N days grouped by date + event_type |
| `getTopCampaigns()` | Top N by clicks (CTR via accessor) |
| `getPendingCampaigns()` | Pending queue with creator + media |
| `approveCampaign()` / `rejectCampaign()` | Transactional admin actions + cache clear |

### TrackAdImpressionJob

- Implements `ShouldQueue`
- `WithoutOverlapping` middleware keyed on `campaignId + ip + uaHash`
- Increments `views_count` and creates impression log

### Cache Keys Invalidated by Observer

- `ad_campaign_hero_top`
- `ad_campaign_home_feed`
- `ad_campaign_listing_detail`
- `ad_campaign_search_results`
- `ad_campaign_category_page_{categoryId|'all'}`

---

## Test Results

```
Tests:    240 passed (727 assertions)
Duration: ~147s
```

All existing tests continue to pass. No new tests were added in Part 1 (controllers/UI are Part 2+).

---

## Issues & Conflicts Found

| Item | Severity | Notes |
|------|----------|-------|
| Existing `campaigns` table | None | Separate concern — CRM notification campaigns, not display ads. No naming collision. |
| `scopeActive()` date nullability | Low | Campaigns with `null` `starts_at` or `ends_at` will not match `active()` scope (SQL null comparison). Intentional per spec; Part 2 admin UI should enforce dates before activation. |
| Observer duplicate cache clears | Low | `saved()`, `created()`, and `updated()` all fire on create/update — cache is cleared multiple times per save. Harmless (idempotent `Cache::forget`). |
| Category page cache key mismatch | Low | Observer uses `category_page_{id\|'all'}`; service `getForPlacement()` uses `category_page_{categoryId}` (no `'all'` fallback). Consistent when `categoryId` is provided; document for Part 2. |
| No `AdCampaignFactory` | Info | Not required for Part 1; may be needed in Part 2/3 for tests. |

---

## Next Steps (Part 2+)

- Filament admin resource for campaign CRUD + approval workflow
- Frontend placement components + click/impression routes
- Factory + feature tests for ad campaign behavior
