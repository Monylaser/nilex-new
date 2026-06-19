# Phase 5A Execution Report — Frontend Ad Placements & Public Pricing Page

**Date:** 2026-06-13  
**Project:** Nilex Marketplace  
**Scope:** Phase 5A ONLY (frontend placements, public pricing page, footer link)  
**Status:** Complete — STOP before Phase 5B

---

## Pre-Execution Validation

| Requirement | Status |
|-------------|--------|
| `AdCampaignService` exists | ✅ |
| `routes/advertising.php` exists | ✅ |
| `config/features.php` exists | ✅ |
| Placement retrieval logic (`getForPlacement`) | ✅ |

**Result:** Pre-check passed. Phase 5A was **not** blocked.

---

## Display Rules (Enforced)

All campaign retrieval flows through `AdCampaignService::getForPlacement()`. No direct `AdCampaign` queries in Blade.

A campaign renders only when:

```
payment_status = paid  OR  payment_status IS NULL (legacy admin)
AND approval_status = approved
AND status = active (when self_service_ads enabled)
AND starts_at <= now()
AND ends_at >= now()
AND deleted_at IS NULL
AND target_url IS NOT NULL
AND ad_image media exists
```

Post-cache validation via `AdCampaign::isDisplayable()` remains in the service layer.

**Phase 5A explicitly excludes:** impression tracking, click tracking, popup campaigns (deferred to Phase 5B).

---

## Components Created / Modified

### `resources/views/components/ad-banner.blade.php` (refactored)

| Parameter | Type | Required |
|-----------|------|----------|
| `placement` | string | ✅ |
| `categoryId` | int\|null | optional |
| `wrapperClass` | string\|null | optional — applied only when a campaign renders |

**Responsibilities:**

- Gated by `config('features.self_service_ads')`
- Fetches campaign via `AdCampaignService::getForPlacement($placement, $categoryId)`
- Renders responsive `<picture>` (desktop / tablet / mobile WebP)
- Links directly to `target_url` (no tracking routes — Phase 5B)
- Renders **nothing** when no qualifying campaign (no empty wrappers)

---

## Views Modified

| File | Change |
|------|--------|
| `resources/views/frontend/home.blade.php` | Hero top banner above search; home feed banner after listing #8 |
| `resources/views/frontend/category.blade.php` | Category page banner with `categoryId` |
| `resources/views/auth/login.blade.php` | Login page banner |
| `resources/views/frontend/ads/pricing.blade.php` | Dynamic pricing from `config('ad_pricing')` |
| `resources/views/components/footer.blade.php` | Footer link «المساحات الإعلانية» (feature-flagged) |
| `resources/views/layouts/app.blade.php` | Footer link gated |
| `resources/views/layouts/navigation.blade.php` | Nav link gated |

---

## Routes

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| `GET` | `/ads/pricing` | `ads.pricing` | `AdSpacesController@index` |

Route already registered in `routes/web.php`. Seller routes remain in `routes/advertising.php`.

---

## Placements Injected

| Placement | Cache Key | Location | Feature Flag |
|-----------|-----------|----------|--------------|
| `hero_top` | `ad_campaign_hero_top` | `home.blade.php` — above homepage search | ✅ |
| `home_feed` | `ad_campaign_home_feed` | `home.blade.php` — after listing #8 (`$loop->iteration % 8 === 0`) | ✅ |
| `category_page` | `ad_campaign_category_page_{id}` | `category.blade.php` — above listings grid | ✅ |
| `login_page` | `ad_campaign_login_page` | `login.blade.php` — above login form | ✅ |

### Layout safety

- No campaign → component outputs zero DOM nodes
- Optional `wrapperClass` (grid col-span, hero container) only emitted when a campaign exists
- Pagination on home feed unchanged — banner inserts inside the foreach loop at iteration 8, 16, …

---

## Public Pricing Page

**URL:** `/ads/pricing` (`ads.pricing`)  
**View:** `resources/views/frontend/ads/pricing.blade.php`

| Requirement | Implementation |
|-------------|----------------|
| Arabic + RTL | ✅ `dir="rtl"`, Arabic copy throughout |
| Tailwind | ✅ |
| Placements | ✅ `hero_top`, `home_feed`, `category_page`, `login_page` from config |
| Dimensions / formats | ✅ `dimensions`, `formats`, `max_size` per placement in `config/ad_pricing.php` |
| Durations | ✅ `config('ad_pricing.durations')` — 7, 15, 30, 60 days |
| Prices | ✅ `config('ad_pricing.placements.*.prices')` — never hardcoded |
| CTA «أعلن معنا الآن» | ✅ → `dashboard/ads/create` (auth) or `login` (guest) |

---

## Config Modified

`config/ad_pricing.php` — added per-placement metadata:

- `description_ar`
- `dimensions`
- `formats`
- `max_size`

---

## Footer Link

| Label | Route | Gated |
|-------|-------|-------|
| المساحات الإعلانية | `ads.pricing` | `config('features.self_service_ads')` |

Added to `resources/views/components/footer.blade.php`. Existing links in `layouts/app.blade.php` and `layouts/navigation.blade.php` also gated.

---

## Feature Flag Behaviour

When `SELF_SERVICE_ADS=false` (`config('features.self_service_ads')`):

- All four placement injections are inactive
- Footer / nav «المساحات الإعلانية» link hidden
- Site layout renders as before self-service placements were wired
- Pricing page remains accessible at `/ads/pricing` with email contact flow

When `SELF_SERVICE_ADS=true`:

- Placements active via `<x-ad-banner>`
- Footer link visible
- Pricing page shows self-service CTA and dynamic price table

---

## Intentionally NOT Implemented (Phase 5B)

- Impression tracking (`ads.impression`, beacon script)
- Click tracking (`ads.click` redirect route)
- Popup campaigns

---

## Manual Testing Checklist

### Feature flag OFF
- [ ] Home, category, login render with no ad DOM nodes
- [ ] Footer «المساحات الإعلانية» link absent
- [ ] `/ads/pricing` loads with email CTA

### Feature flag ON
- [ ] Paid + approved + in-range campaign shows on all 4 placements
- [ ] Unpaid / pending / expired campaigns never render
- [ ] No empty grid cells or wrapper divs when no campaign
- [ ] Home feed banner appears after listing #8 without breaking pagination
- [ ] Pricing table matches `config/ad_pricing.php`
- [ ] CTA «أعلن معنا الآن» → create (auth) or login (guest)
- [ ] Footer link → `/ads/pricing`

---

**STOP.** Awaiting approval before Phase 5B (impression/click tracking, popup campaigns).
