# Phase 3A Execution Report — Seller Self-Service Advertising Dashboard

**Date:** 2026-06-13  
**Project:** Nilex Marketplace  
**Scope:** Phase 3A ONLY (seller dashboard — no Filament, placements, banner rendering, or tracking)

---

## Pre-Execution Validation

| Item | Status |
|------|--------|
| `AdCampaign` model | ✅ Exists — extended |
| `AdCampaignService` | ✅ Exists — unchanged |
| `AdCampaignPaymentService` | ✅ Exists — reused |
| `config/features.php` | ✅ Exists |
| `config/ad_pricing.php` | ✅ Exists — extended with `login_page` |
| `duration_days` on `ad_campaigns` | ❌ Did not exist → migration created and applied |

---

## Duration Migration Status

**Migration:** `2026_06_13_100004_add_duration_days_to_ad_campaigns_table.php`

| Column | Type | Nullable |
|--------|------|----------|
| `duration_days` | `unsignedInteger` | Yes |

**Model updated:** `duration_days` added to `$fillable` and cast as `integer`.

**Additional migration (required for `login_page` placement on MySQL):**  
`2026_06_13_100005_add_login_page_to_ad_campaigns_placement.php` — extends placement enum with `login_page`. Skipped on SQLite (no strict enum).

---

## Routes

Registered in `routes/advertising.php`, loaded from `bootstrap/app.php`.

**Middleware:** `auth`, `verified`, `self_service_ads`

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/dashboard/ads` | `dashboard.ads.index` | `SellerAdCampaignController@index` |
| GET | `/dashboard/ads/create` | `dashboard.ads.create` | `SellerAdCampaignController@create` |
| POST | `/dashboard/ads/create` | `dashboard.ads.store` | `SellerAdCampaignController@store` |
| GET | `/dashboard/ads/{campaign}` | `dashboard.ads.show` | `SellerAdCampaignController@show` |
| POST | `/dashboard/ads/{campaign}/retry-payment` | `dashboard.ads.retry-payment` | `SellerAdCampaignController@retryPayment` |

**Ownership:** `AdCampaignPolicy` restricts `show` and `retryPayment` to campaigns where `seller_id` matches the authenticated user. Index query is scoped to `seller_id = auth()->id()`.

**Feature gate:** `EnsureSelfServiceAdsEnabled` middleware returns 404 when `config('features.self_service_ads')` is `false`.

---

## Views

| View | Purpose |
|------|---------|
| `resources/views/dashboard/ads/index.blade.php` | Campaign list (RTL, seller layout via `<x-app-layout>`) |
| `resources/views/dashboard/ads/create.blade.php` | Create campaign form + inline Alpine price calculator |
| `resources/views/dashboard/ads/show.blade.php` | Campaign detail + retry payment button |

All views use `dir="rtl"` and reuse the existing seller layout (`layouts.app` via `x-app-layout`), matching `SellerLeads` patterns.

---

## Components

| Component | Purpose |
|-----------|---------|
| `resources/views/components/ad-price-calculator.blade.php` | Reusable Alpine.js price calculator reading from `config('ad_pricing')` |

The create page embeds equivalent Alpine logic inline (bound to form `placement` / `duration_days` selects). The standalone component is available for reuse.

**Price calculator displays:**
- Placement price (from `pricing[placement].prices[duration]`)
- Duration label (from `durations[duration].label_ar`)
- Total (placement price for selected duration)
- Currency from `config('ad_pricing.currency')`

---

## Validation Rules

**Form request:** `App\Http\Requests\Dashboard\StoreSellerAdCampaignRequest`

| Field | Rules |
|-------|-------|
| `title` | required, string, max:255 |
| `placement` | required, in: `hero_top`, `home_feed`, `category_page`, `login_page` |
| `category_id` | required_if placement = `category_page`, nullable, integer, exists:categories,id |
| `target_url` | required, url, max:2048 |
| `duration_days` | required, integer, in: keys of `config('ad_pricing.durations')` (7, 15, 30, 60) |
| `ad_image` | required, file, mimes:jpeg,jpg,png,webp,gif, max:2048 (2MB) |

Custom after-validation: rejects `category_id` when placement is not `category_page`.

Arabic validation messages included.

---

## Submission Flow

1. Seller submits create form → `StoreSellerAdCampaignRequest` validates input.
2. `AdCampaign` created with:
   - `payment_status` = `pending`
   - `approval_status` = `pending` *(DB enum; task spec `pending_approval` maps to existing `pending` value)*
   - `status` = `draft`
   - `seller_id`, `created_by`, `duration_days`, placement fields
3. Banner uploaded to Spatie Media Library collection `ad_image`.
4. `AdCampaignPaymentService::initiatePayment()` called with campaign, seller, and `duration_days`.
5. User redirected externally to Paymob checkout URL.

---

## Failed Payment Retry

- Visible on index (row action) and show page when `payment_status === 'failed'`.
- Route: `POST dashboard/ads/{campaign}/retry-payment`
- Authorized via `AdCampaignPolicy::retryPayment`.
- Reuses `AdCampaignPaymentService::initiatePayment()` with stored `duration_days`.

---

## Files Created

```
database/migrations/2026_06_13_100004_add_duration_days_to_ad_campaigns_table.php
database/migrations/2026_06_13_100005_add_login_page_to_ad_campaigns_placement.php
app/Http/Middleware/EnsureSelfServiceAdsEnabled.php
app/Policies/AdCampaignPolicy.php
app/Http/Requests/Dashboard/StoreSellerAdCampaignRequest.php
app/Http/Controllers/Dashboard/SellerAdCampaignController.php
routes/advertising.php
resources/views/dashboard/ads/index.blade.php
resources/views/dashboard/ads/create.blade.php
resources/views/dashboard/ads/show.blade.php
resources/views/components/ad-price-calculator.blade.php
reports/self_service_execution_phase3A.md
```

---

## Files Modified

```
app/Models/AdCampaign.php              — duration_days fillable + cast
config/ad_pricing.php                  — login_page placement + prices
bootstrap/app.php                      — advertising routes + self_service_ads middleware alias
```

---

## Out of Scope (Not Implemented)

- Filament admin changes
- Frontend banner placements / rendering
- Ad impression/click tracking changes
- Paymob webhook handling (Phase 2)

---

## Enable & Test

Set in `.env`:

```
SELF_SERVICE_ADS=true
```

Then visit `/dashboard/ads` as an authenticated, email-verified seller.

**Status:** Approved — Phase 3B+ verified in working tree (see `self_service_execution_phase3B.md` through `phase6.md`).
