# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Nilex is an Egyptian classified ads marketplace built on Laravel 13 with a Filament 5 admin panel. It features OTP-based phone auth, a point-based economy, tiered plan entitlements, AI-powered listing generation (Gemini), Paymob payment integration, and self-service ad campaigns.

## Commands

```bash
# One-time setup (install, .env, key, migrate, npm install, build)
composer setup

# Concurrent dev server (PHP serve + queue:listen + Vite)
composer run dev

# Run all tests (Pest, clears config first)
composer test

# Run a single test class or method
./vendor/bin/pest --filter=AnalyticsAccessTest
./vendor/bin/pest --filter=test_guest_sees_locked_analytics

# Format PHP code
./vendor/bin/pint

# Security check
./vendor/bin/enlightn
```

## Architecture

### Tech Stack

- **Framework:** Laravel 13, Livewire, Alpine.js, Tailwind CSS 3
- **Admin:** Filament 5.4 (accessible at `/admin`) with FilamentShield (RBAC) and FilamentTranslatable (AR/EN)
- **Build:** Vite 8
- **DB:** SQLite (dev) — configure for production
- **Queue:** Redis with priority channels (`sms-high`, `email-high`, `auth-critical`) — set `QUEUE_CONNECTION=sync` for local testing
- **Search:** Laravel Scout + Meilisearch (use `SCOUT_DRIVER=collection` in tests)
- **AI:** Google Gemini API (`GEMINI_API_KEY`)
- **Payments:** Paymob (`/payments/callback` webhook, CSRF disabled, HMAC-validated)
- **Testing:** Pest 4.0 primary, PHPUnit 12.5 fallback; in-memory SQLite

### Domain Modules

**1. Auth & User Management** (`app/Auth/`)
- OTP phone verification (codes hashed, configurable via `config/auth-security.php`)
- Device fingerprinting with 3-account-per-device limit
- Social login: Google, Facebook, TikTok, Instagram (Socialite)
- Roles: `super_admin`, `admin`, `moderator`, `user`
- Middleware: `EnsureOtpIsVerified`, `EnsureUserIsNotBanned`, `SetLocale`

**2. Listing System**
- Status flow: `pending → published / rejected / flagged`
- Rejection reasons: inappropriate, scam, incomplete, wrong_category, prohibited, duplicate
- 3-strike system → auto-ban (`ListingObserver`)
- Media via Spatie MediaLibrary (auto-optimized on upload)

**3. Point Economy** (`app/Services/PointService.php`)
- Transactional credit/debit/transfer — balance kept in sync by `PointTransactionObserver`
- 4 plan tiers: Starter, Growth, Pro Seller, Business (Individual or Company type)
- Plans configured in `config/pricing.php`

**4. Entitlement System** (`app/Services/EntitlementService.php`)
- 13 feature flags gated by plan tier (analytics, charts, click data, featured/boost limits, search priority, business badge, etc.)
- Check access via `EntitlementService::canUseFeature()`
- Entitlements cached in Redis to avoid N+1; cache invalidated by `AdCampaignObserver`

**5. Ad Campaigns** (`app/Models/AdCampaign.php`)
- Toggle with `SELF_SERVICE_ADS=true`
- Lifecycle: `draft → active → expired`
- Impression/click tracking dispatched to queue asynchronously
- `TrackCampaign` middleware for attribution
- Pricing in `config/ad_pricing.php`

**6. Analytics** (`app/Services/SellerListingAnalyticsService.php`)
- Per-listing views, phone clicks, WhatsApp clicks
- Business-tier users get full dashboard + auto monthly PDF reports (generated 1st of month @ 02:00 via scheduler)

### Key Service Providers

- `AppServiceProvider` — registers singletons (PointService, EntitlementService), model observers, event listeners, view composers
- `AuthSecurityServiceProvider` — OTP & device fingerprinting config

### Observers (Model Hooks)

| Observer | Trigger |
|----------|---------|
| `PointTransactionObserver` | Updates `users.points_balance` |
| `ListingObserver` | Moderation workflow, media optimization |
| `AdCampaignObserver` | Cache invalidation |
| `ListingPhoneClickObserver` / `ListingWhatsappClickObserver` | Click tracking |
| `OfferLeadObserver` | Tracks buyer offers |

### Configuration Files

| File | Purpose |
|------|---------|
| `config/auth-security.php` | OTP length, expiry, device limits, queue channels |
| `config/pricing.php` | Point plan definitions |
| `config/ad_pricing.php` | Campaign pricing tiers |
| `config/services.php` | Third-party API keys (Gemini, Paymob, Socialite) |

### Testing Notes

Tests use in-memory SQLite, `QUEUE_CONNECTION=sync`, and `SCOUT_DRIVER=collection`. Telescope, Pulse, and Nightwatch are disabled in test env. See `phpunit.xml` for full env overrides.

### Localization

AR/EN strings live in `lang/{locale}/ui.php`. Use `__('ui.key')` in views. Filament forms support both languages via FilamentTranslatable.

## Point Plans & Pricing

| Plan | Points | Price (EGP) | Type |
|------|--------|-------------|------|
| Starter (البداية) | 100 | 49 | Individual |
| Growth (النمو) | 250 | 99 | Individual |
| Pro Seller (البائع المحترف) | 700 | 249 | Individual |
| Business (الشركات) | 1800–2000 | 499–599 | Company |

Plans are defined in `config/pricing.php`. Business tier unlocks all 13 entitlement flags.

## Platform Categories

The platform has 14 categories. **"خردة وخامات" (Scrap & Raw Materials)** is a deliberate competitive differentiator — no major Egyptian classifieds platform currently serves this niche.

The full category list should be sourced from the `categories` table / seeder rather than hardcoded here, as it may evolve.

## Point Economy Rules (source of truth)

These values are authoritative. Any view displaying points must match them — never hardcode different numbers.

| Action | Points | Where |
|--------|--------|-------|
| Phone OTP verified (once only) | +50 | `OtpController::verify()` |
| Listing created | +3 | `HomeController::store()` |
| Daily login | +1 | (scheduled) |
| Referral | +25 | (referral flow) |
| Registration welcome | +100 | `RegisteredUserController` |

**Featuring costs** — defined in `Listing::FEATURE_COSTS` (non-linear, not per-day):

| Duration | Cost | Supported |
|----------|------|-----------|
| 1 day | 25 pts | ✅ |
| 3 days | 60 pts | ✅ |
| 7 days | 120 pts | ✅ |
| 14 days | 220 pts | ✅ |

- Use `Listing::featureCost(int $days)` to get cost — returns `null` for unsupported durations (safe for UI rendering)
- Use `Listing::featureCostStrict(int $days)` to get cost — throws `InvalidArgumentException` for unsupported durations (use in programmatic flows)
- Use `$listing->featureWithPoints(int $days)` to feature (deducts points + checks entitlement limits; uses `featureCostStrict` internally)
- Featuring is best-effort after listing creation: failure is silent and does not block the listing

## Current Project Status (as of June 2026)

- **Tests:** 263 passing, 0 failures
- **SMS OTP:** Routed to `log` driver — no real SMS provider connected yet; OTPs appear in `storage/logs/laravel.log` during development
- **Payments:** Paymob integration is in **test mode** only — no live transactions
- **Deployment:** Not yet deployed to a production server; running locally only

### Car Listings (cars category)

- Public listing wizard renders dependent dropdowns for the `cars` category: Brand → Model (filtered by brand) → Fuel → Transmission (Alpine, mirrors the governorate→city pattern). Selecting the "أخرى/Other" brand reveals a free-text field for the brand name.
- Brand & Model persist to the `listings.car_brand_id` / `car_model_id` FK columns; Fuel/Transmission (and the manual `car_brand_other`) persist in `custom_fields_values`. `HomeController::store()` enforces these only when `category->slug === 'cars'` (model must belong to brand).
- Brand/model data lives in `car_brands` / `car_models` (25 brands, ~209 models) via `CarBrandSeeder`, now registered in `DatabaseSeeder`.
- Fixed: `/category/{slug}` 500 (LazyLoadingViolationException) by eager-loading `['category','location','user']` in `CategoryController::show()`.

### Listing Cards & Detail Page

- Listing media is stored under the Spatie collection **`images`** (in `HomeController::store()`). Always read it back with `getMedia('images')` / `getFirstMediaUrl('images')`.
- Fixed: listing detail page showed "لا توجد صور" because `show.blade.php` read `getMedia('listings')` (wrong collection) — corrected to `getMedia('images')`.
- The reusable `partials/listing-card.blade.php` is fully clickable: the whole card is wrapped in a single `<a>` to `listings.show` (no nested title anchor).

### Image Watermarking

- `Listing::registerMediaConversions()` applies the Nilex watermark (`public/images/watermark.png`, transparent, bottom-right, 40% opacity) to **all three** conversions: `thumb` (300px), `card` (600×450, new), and `full_hd` (1920px). Conversions are `nonQueued` (run synchronously on upload via GD).
- The **original** uploaded file is kept clean/unwatermarked and is never linked in any view — public views only render watermarked conversions (cards/home/search use `card`, detail pages use `full_hd`, dashboard/gallery thumbs use `thumb`).
- Backfill existing listings with `php artisan listings:regenerate-images` (optional `--listing=ID`) to regenerate conversions for media uploaded before watermarking was added.

## Ad Campaign System

Two parallel ad systems coexist:

**1. Admin-Managed Campaigns** — fully implemented
- Created and managed via Filament admin (`AdCampaignResource`)
- 5 placements: `hero_top`, `home_feed`, `category_page`, `login_page`, `popup`
- `popup` and `login_page` added via later migrations (`2026_06_13_100005`, `2026_06_13_100006`)
- Served by `AdCampaignService` with Redis-cached placement queries
- Impression & click tracking dispatched to queue asynchronously

**2. Self-Service Seller-Paid Ads** — fully implemented, feature-flagged
- Enabled via `SELF_SERVICE_ADS=true` in `.env` (maps to `config/features.php`)
- When disabled, `EnsureSelfServiceAdsEnabled` middleware blocks seller ad routes
- Seller flow: `SellerAdCampaignController` + `StoreSellerAdCampaignRequest`
- Payment via `AdCampaignPaymentService` + `PaymobAdWebhookService` (Paymob, currently test mode)
- Routes split across `routes/ads.php` and `routes/advertising.php`

**3. Public Ad Spaces Page** — implemented
- `/ads/pricing` → `AdSpacesController` → `frontend.ads.pricing` view
- Passes `selfServiceEnabled` flag and `config('ad_pricing')` to the view
- Linked from footer and navigation

## Planned Next Steps

- **Hosting:** Deploy via **Laravel Forge** (planned)
- **UI/UX:** Hire a designer from **خمسات (Khamsat)** for frontend redesign
- **SMS Provider:** Integrate an Egyptian SMS gateway — candidates are **Connekio** or **Sentry SMS** — to replace the log driver for OTP delivery
