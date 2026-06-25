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

**Bilingual infrastructure (Phase A):** All 3 layouts (`frontend`, `app`, `guest`) now set `<html dir>`/`lang` from `app()->getLocale()` (`rtl` for `ar`, `ltr` otherwise). The AR/EN toggle (POST `language.switch`) is present in all 3. Locale persists across devices: a nullable `users.locale` column (added via migration, in `$fillable`) is written on switch when authenticated, and `SetLocale` middleware now prefers `user->locale` → session → config. Note: `SetLocale` runs only on the `web` group, **not** on Filament `/admin` (its own middleware stack) — admin locale switching is deferred to Phase D. Inner hardcoded RTL bits (footer `dir`, search `direction:rtl`) are intentionally left for Phase B/C.

**Phase B.1 — category names (content translation):** Categories already store both `name_ar` **and** `name_en` (plain string columns, **not** Spatie `HasTranslations`; all 13 live categories have `name_en` populated) plus a locale-aware accessor `Category::getNameAttribute()` (returns `name_en` when locale ≠ `ar`). Public views now render the locale-aware `$category->name` instead of `->name_ar` in: `frontend/category.blade.php` (title/meta/breadcrumb/header/subcategories/search placeholder/empty state — the header subtitle now shows the *opposite*-language name to avoid duplication), `frontend/home.blade.php` (sticky category bar + category grid + footer tag links + the inline latest-listing category chip), and `frontend/listings/show.blade.php` (breadcrumb + meta only). The listing wizard (`frontend/listings/create.blade.php`) reads `cat.name` / `sub.name` from JSON; `HomeController::create()` appends the `name` accessor to the categories collection (and children) so the locale-aware value is serialized. **Intentionally left as `name_ar`:** the homepage `$iconMap` substring match (internal keyword lookup, language-independent), and car brand/model + location names (separate from categories, deferred). **Known coupling (deferred):** `frontend/partials/listing-card.blade.php` still uses `$listing->category->name_ar`; it is rendered on the in-scope category/home grids and should be switched in a follow-up.

**Phase B.2 — listing-card + home/category content + remaining RTL bits:** Closed the B.1 known coupling: `frontend/partials/listing-card.blade.php` (the shared card reused on home/category/search) now renders the locale-aware `$listing->category->name`, and its 3 hardcoded UI strings are translated — `featured` badge → `ui.sections.featured_plain`, currency → `ui.sections.currency`, price-on-contact → `ui.sections.price_on_contact`. **Location names now resolve to `Location::getNameAttribute()` (locale-aware `name`)** in `home.blade.php` (featured + latest grids) — `Location` already had the same `name_ar`/`name_en` accessor pattern as `Category`; the B.1 deferral on location names is now lifted *for the home grids only* (other location usages in `show.blade.php`/`search-results`/livewire remain `name_ar`, deferred). `home.blade.php` also translated: the seller "موثق" badge/tooltip → `ui.sections.verified`, the duplicate empty-state "تصفح حسب الفئة" → existing `ui.sections.browse_by_category`, the "ابحث في" heading → `ui.sections.search_in`, and the trend connector "في" → `ui.misc.in` (the trend *query terms* themselves stay Arabic — they are real search payloads, not UI). `category.blade.php` fully translated: title → `ui.category.title`, meta description → `ui.category.meta`, breadcrumb home → `ui.footer.link_home`, count suffix → `ui.sections.listing_count_suffix`, in-category search placeholder → `ui.category.search_placeholder`, search button → `ui.hero.search_btn`, empty title/subtitle/browse-other → `ui.category.empty_title`/`empty_subtitle`/`browse_other`, add-free → `ui.empty.add_free`. **RTL bits made conditional** (`app()->getLocale() === 'ar' ? 'rtl' : 'ltr'`): `layouts/frontend.blade.php` desktop + mobile search `direction`, the short "أضف" mobile CTA → `ui.nav.add_short`, and `frontend/category.blade.php` `<main dir>` (kept the attribute, made it conditional rather than dropping it). **New `ui` keys added (ar + en):** `nav.add_short`, `misc.in`, `sections.featured_plain`, `sections.verified`, `sections.search_in`, and a new `category` group (`title`, `meta`, `search_placeholder`, `empty_title`, `empty_subtitle`, `browse_other`). **Explicitly out of B.2 scope (next phase):** `frontend/search-results.blade.php`, `frontend/listing-details.blade.php` (legacy/likely-unused duplicate of `show.blade.php`), and the livewire grids. Tests: 263 passing, 0 failures (unchanged).

**Phase B.3a — listing wizard Blade markup (presentation layer):** First of four sub-phases translating `frontend/listings/create.blade.php` (~232 strings split ~115 Blade markup / ~117 inside the inline Alpine `<script>`). B.3a covers **only the ~115 rendered markup strings**; the Alpine component (option arrays, client-side validation, AI/error messages) is untouched and deferred to B.3b/B.3c, and the server-side `HomeController::store()` validation messages to B.3d. New dedicated lang files `lang/{ar,en}/wizard.php` were created (not a `ui` sub-group, to avoid bloating `ui.php`) with the groups `page_title`, `header_*`, `steps`, `common`, `step1`–`step4`, `car`, `realestate`, `feature`. All markup now uses `__('wizard.xxx')`: page title, headers, `$stepLabels` `@php` array, every field label/placeholder/`<option>` prompt, the AI panel text, image step, review/summary cards, featuring panel, nav buttons, and the inline `x-text` ternaries (AI button, model/city placeholders, submit button, featuring `<option>` suffix) — quote-escaping verified safe (translations contain no single/double quotes). The page `dir` is now conditional (`app()->getLocale() === 'ar' ? 'rtl' : 'ltr'`) matching the B.2 RTL pattern. A `const NILEX_WIZARD_I18N = @json(__('wizard'));` was added next to `NILEX_CATEGORIES`/`NILEX_LOCATIONS` and is **wired but unused** until B.3b/B.3c consume it (note: `@json` emits `\u`-escaped Arabic — valid, decodes at runtime). Blade comments left Arabic (not rendered); localStorage drafts unaffected (only labels changed, not keys/values). Tests: 263 passing, 0 failures (unchanged). **Out of B.3a scope:** all JS/Alpine strings (B.3b options + condition value→label split, B.3c validation/AI/errors), `HomeController` server messages (B.3d), and `show.blade.php`'s duplicate label maps (B.4).

**Phase B.3b — listing wizard Alpine option arrays (58 labels):** Translated the 12 static option arrays in the `create.blade.php` Alpine component (`fuelOptions`, `transmissionOptions`, `carConditionOptions`, `propertyTypeOptions`, `listingTypeOptions`, `roomsOptions`, `bathroomsOptions`, `floorOptions`, `finishingOptions`, `compoundOptions`, `featureOptions`, `priceTypes`). Each option's `label` now reads from `NILEX_WIZARD_I18N` (the constant wired in B.3a) instead of a hardcoded Arabic string. New keys added to `lang/{ar,en}/wizard.php`: an `options` group (`fuel`, `transmission`, `condition`, `property_type`, `listing_type`, `rooms`, `bathrooms`, `floor`, `finishing`, `compound`, `price_type`) plus `feature.opt_{0,1,3,7,14}`. **Value-preservation:** 11 arrays use locale-neutral `value` keys (English/numeric/day) so only their `label` changed; **`carConditionOptions` is the exception** — its `value` stays a hardcoded Arabic literal (e.g. `حالة ممتازة`, stored in `custom_fields_values.condition` and depended on by `show.blade.php`, which has no value-map for `condition` and renders the raw stored value), only its `label` reads from `options.condition.{fabrica,excellent,good,fair,needs_maintenance}`. Numeric/`+` keys accessed via bracket notation (e.g. `options.rooms['6+']`). No change to submitted/stored values, so `show.blade.php`'s `$cfValueMaps` (B.4) stays aligned. Tests: 263 passing, 0 failures (unchanged). **Out of B.3b scope:** B.3c (client validation/AI/error strings), B.3d (`HomeController` server messages), B.4 (`show.blade.php` label maps).

**Phase B.3c — listing wizard remaining JS strings (final JS sub-phase, ~38 strings):** Translated all remaining hardcoded Arabic strings in the `create.blade.php` Alpine `<script>` to read from `NILEX_WIZARD_I18N`. New groups added to `lang/{ar,en}/wizard.php`: `validation` (17 fixed per-field messages + `field_required` with a `:field` placeholder for the dynamic `customFieldsSchema` required-field loop), `ai` (5: `prompt_too_short`, `success`, `failed`, `invalid_prompt`, `connection_failed`), `errors` (7: `max_images`, `unsupported_format` + `image_too_large` use a `:name` placeholder for filename interpolation, `session_expired`, `unexpected`, `network`, `fix_errors`), and `checklist` (6). **Interpolation pattern:** JS uses `.replace(':field', …)` / `.replace(':name', file.name)` to inject dynamic values into translated templates. **Shared key:** the 419 "session expired" message (previously duplicated across AI and submit handlers with differing punctuation) is now a single `errors.session_expired` (period normalized) used in both. **`conditionLabel()`** (the simple new/used toggle, distinct from B.3b's 5-value `carConditionOptions`) reuses existing `step2.condition_new`/`condition_used` with the emoji appended in JS — no new keys. Tests: 263 passing, 0 failures (unchanged). **Remaining:** only B.3d (`HomeController` server-side validation messages) before wizard translation is complete.

**Phase B.3d — HomeController server-side messages (final B.3 sub-phase, wizard translation complete):** Replaced the literal Arabic strings the controller passed directly into `validate()` (bypassing Laravel's `lang/validation.php`, which doesn't exist here) with `__('wizard.server.*')`. New `server` group in `lang/{ar,en}/wizard.php` (12 keys: `price_max`, `car_brand_required`, `car_model_required`, `car_model_not_in_brand`, `fuel_required`, `transmission_required`, `year_required`, `condition_required`, `car_brand_other_required`, `property_type_required`, `listing_type_required`, plus `field_required` with a `:field` placeholder, `created_success`, `ai_failed`). Covered in `HomeController::store()`: the `price.max` message, all car/real-estate conditional validation messages, and the success flash (`created_success`, shown only on the non-AJAX fallback path — the AJAX response returns `{success,featured,redirect}` with no message). The **dynamic `custom_fields_schema` required-field message** is now locale-aware: it resolves `label_en`→`label_ar`→`name` by locale and uses the `field_required` `:field` template (dropping the old hardcoded Arabic " مطلوب" suffix; note schema rows currently only carry `label_ar`, so EN falls back to the Arabic label but with an English suffix). `HomeController::aiGenerate()`'s failure `message` now uses `server.ai_failed` (it's surfaced to the user via the wizard's `data.message || NILEX_WIZARD_I18N.ai.failed` fallback). **Safe by design:** the wizard's `mapServerErrors()` routes 422 errors by **field key**, not message content, so translating the message *values* is presentation-only. **Deferred (out of scope):** the point-transaction reason string at `HomeController.php:221` (`'مكافأة نشر إعلان جديد: '…`) stays Arabic — it's persisted once at credit time (not dynamically locale-aware) and is only shown on the still-untranslated `points/history.blade.php`. Tests: 263 passing, 0 failures (unchanged). **B.3 (the entire listing wizard) is now fully bilingual.**

**Phase B.4 — listing detail page (`frontend/listings/show.blade.php`):** Translated all ~42 page-chrome strings (price/contact cards, breadcrumb, gallery, seller, meta box, mobile bar, offer modal, the JS offer-error fallback, and `<title>`/og meta). New dedicated files `lang/{ar,en}/listing.php` with a `detail` group (page strings + two cf-label overrides `label_color`/`label_compound`); generic bits reuse `ui.sections.currency` + `ui.footer.link_home`. **Spec maps now reuse the SAME wizard keys (no duplicate literals):** `$cfValueMaps` was deleted entirely and replaced with `__('wizard.options.'.$key.'.'.$val)` lookups (fallback to the raw value for free-text/numeric fields); `$cfLabels` reuses the 13 exact-match `wizard.car.*`/`wizard.realestate.*` label keys; `$cfSuffix` reuses `wizard.car.mileage_unit`/`wizard.realestate.area_unit`. **`condition` fix:** the custom car-`condition` value is stored as an Arabic literal (B.3b), so it's reverse-mapped via `array_flip(__('wizard.options.condition', [], 'ar'))` → neutral key → `__('wizard.options.condition.*')` for locale-aware *display* (stored value unchanged); previously it leaked Arabic in EN. **Locale-aware names:** added a `getNameAttribute()` accessor to `CarBrand` + `CarModel` (same no-fallback pattern as `Category`/`Location`), and switched all remaining `->name_ar` on this page to `->name` (`carBrand`, `carModel`, `location`, `province`). Brand/model + governorate names have real `name_en` (seeded) so they translate; **city names have `name_en = name_ar` (Arabic) by seeder design — a data gap, not a code gap**, so EN shows Arabic city names until real `name_en` is seeded (no code change needed then). The schema `labelMap` is now locale-aware too (`label_en`→`label_ar`→`name`, matching B.3d). RTL was already handled (breadcrumb `dir` conditional from an earlier phase; the rest inherits the layout `<html dir>`). Tests: 263 passing, 0 failures (unchanged). **B.4 hotfix:** the offer-error fallback was first written as `@json(__('listing.detail.offer_error'))` inside the double-quoted `x-data` attribute — `@json` emits double quotes, which prematurely closed the attribute and dumped the rest of `submitOffer()` as visible page text; fixed by switching to the single-quoted `'{{ __('listing.detail.offer_error') }}'` pattern used elsewhere in the file.

**Phase B.5 — public ad spaces page (`frontend/ads/pricing.blade.php`), completing Phase B:** Translated all ~33 page-chrome strings (meta title/description, hero, ad-space card headings, pricing-table headings/`Space` column, both "how it works" 3-step flows, and the CTA banner). New dedicated files `lang/{ar,en}/adspaces.php` with groups `meta`, `hero`, `spaces`, `pricing`, `how.{self,email}.*`, `cta`. **Placement/duration labels stay config-driven:** `config/ad_pricing.php` gained `label_en` (all placements + durations) and `description_en` (the 5 shown placements) **alongside** the existing `label_ar`/`description_ar` (untouched — protects the shared Dashboard/`SellerAdCampaignController` consumers that still read `label_ar`); the Blade now picks ar/en via two `@php` closures (`$localeLabel`/`$localeDesc`) keyed on `app()->getLocale()`. Page is purely static/config-driven (prices `number_format`'d from config) — **no model/accessor work**. RTL made conditional: `<main dir>` and the pricing table `text-right`/`text-left` now follow `$isRtl`; the Arabic-Indic step numerals `١٢٣` switch to Western `1 2 3` in EN. `mailto:ads@nilex.com` left locale-neutral (only link text translated). The two how-it-works `@if($selfServiceEnabled)` branches were collapsed into one loop driven by a `$flow = self|email` key. Tests: 263 passing, 0 failures (unchanged). **Phase B (public frontend translation) is now complete.**

**Phase C — authenticated-area translation (in progress):** Covers everything an authenticated user sees that Phase B did not. Sequenced C.1 → C.7. Scoping discovery confirmed ~400–430 strings total; three files are **dead and intentionally skipped** (not deleted yet — a delete decision is deferred to C.4): `resources/views/dashboard.blade.php` (the `/dashboard` route resolves to the Livewire `UserDashboard`, not this view), `auth/unified.blade.php` (references a non-existent `social.redirect` route — would error if rendered), and the 3 Breeze `profile/partials/*` (the live `profile/edit.blade.php` is a custom page that never `@include`s them).

**Phase C.1 — foundation (navigation + app layout + framework lang files):** First and foundational sub-phase. **(1) Published the two missing framework lang files** that previously fell back to English regardless of locale: `lang/{ar,en}/auth.php` (Laravel defaults `failed`/`password`/`throttle`) and `lang/{ar,en}/validation.php` (full Laravel default ruleset + a project-specific `attributes` map covering `name`, `email`, `phone`, `whatsapp`, `password`, `contact`, `identifier`, `otp`, `governorate`, `city`, `bio`, `avatar`, `title`, `price`, `category_id`, `target_url`, `ad_image`, `duration_days`, `placement`). These now drive every auth/profile/ads form's framework validation + login-failure messages bilingually — **this unblocks C.2/C.3.** **(2) `layouts/navigation.blade.php`** — translated all ~11 rendered strings (incl. the previously English `{{ __('Profile') }}`/`{{ __('Log Out') }}` which had no lang file) to new `ui.nav.*` keys: `home`, `ad_spaces`, `admin_panel`, `login_full`, `register`, `profile`, `logout`. **(3) `layouts/app.blade.php`** — translated the notification-bell dropdown (new `ui.notifications.{title,default,empty}` group), the quick-search placeholder (new `ui.nav.search_placeholder_full`), and the entire legal footer (reused existing `ui.footer.*` where text matched: `quick_links`, `link_home`, `link_dashboard`, `link_login`, `legal_pages`, `copyright`, `contact`; added new `ui.footer.{brand,tagline,link_search,link_register_short,privacy,terms}`). **RTL fixes:** the footer's hardcoded `dir="rtl"` (line ~136, which Phase A did **not** fix — only the `<html>` tag) and the search input's `direction: rtl` + `text-right` (line ~99) are now conditional on `app()->getLocale() === 'ar'`. The language-toggle button label (`'ar' ? 'EN' : 'ع'`) is intentionally left as-is (it shows the *opposite* language). New `ui` keys added (ar + en): `nav.{home,ad_spaces,admin_panel,login_full,register,profile,logout,search_placeholder_full}`, a new `notifications` group, and `footer.{brand,tagline,link_search,link_register_short,privacy,terms}`. Tests: 263 passing, 0 failures (unchanged).

**Phase C.2 — auth screens (login/register/otp/forgot/reset + guest layout):** Translated all live auth views (the dead `auth/unified.blade.php` was skipped). All ~55 view strings now use a **new `ui.auth.*` group** (ar + en) — `auth.php` was left as framework-only (`failed`/`password`/`throttle`). The 5 Arabic views (`login`, `register`, `verify-otp`, `forgot-password`, `reset-password`) plus `layouts/guest.blade.php`'s brand panel (tagline + 3 trust points + 3 stats) and Arabic `<title>` fallback are covered. The 2 Breeze views (`confirm-password`, `verify-email`) use bare-English `__()` keys, so **new `lang/{ar,en}.json` files** were created with their exact key→translation maps (zero Blade changes to those 2 files). `register.blade.php`'s JS password-strength labels are wired via the **`@json` bridge** (`const NILEX_AUTH_STRENGTH = @json(__('ui.auth.strength'))`, B.3a pattern). **OAuth buttons** (Google/Facebook/TikTok/Instagram) left as fixed brand labels — only the divider text translated. **RTL bits made conditional** (`app()->getLocale() === 'ar' ? rtl/right : ltr/left`): inner `dir="rtl"` on `verify-otp`/`forgot`/`reset`, the `text-align/text-right` headers on `login`/`register`/`forgot`/`reset`/guest panel. Phone/email/OTP inputs stay `dir="ltr"` by design. **OtpController's 6 hardcoded Arabic server messages were NOT touched — deferred to C.7** (the view only renders them via `session()`/`@error`). Tests: 263 passing, 0 failures (unchanged).

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
- Public wizard now mirrors the admin (`CarFields`/`RealEstateFields`) dropdowns with identical option values:
  - **Cars** (slug `cars`): added Year (select, current→1970), Condition (select, Arabic values), Mileage (number), Color (text) on top of Brand/Model/Fuel/Transmission. Year + Condition are required (client + `HomeController::store` server-side).
  - **Real estate** (slug `real-estate`): full set added — Property type, Listing type, Rooms, Bathrooms, Floor, Finishing, Area, Compound — all in `custom_fields_values`. Property type + Listing type are required.
  - Categories detected by `slug` (`cars`/`real-estate`, both have 0 subcategories); option codes match admin exactly so admin- and user-created listings stay consistent.
  - `show.blade.php` now humanizes these coded `custom_fields_values` keys into Arabic labels/values (e.g. `property_type: apartment` → `نوع العقار: شقة`), since these categories have no `custom_fields_schema`.
  - Fixed admin `DynamicFields::STATIC_CATEGORY_IDS` (was `[1,12,20]`; cars is id 2 and 20 doesn't exist → corrected to `[1,2]`).
- Fixed: submitting a listing with a huge price (e.g. 43.5 billion) crashed with a generic toast — a MySQL `22003` out-of-range error on `listings.price` (`decimal(12,2)`, max `9,999,999,999.99`). Added `max:9999999999.99` to the `price` rule in `HomeController::store()` (Arabic message "السعر المدخل كبير جداً، يرجى التحقق من الرقم") and a matching client-side cap (`NILEX_MAX_PRICE`) + `max` attr in `create.blade.php` for instant feedback. Not a points issue — crash occurred at `$listing->save()` before any point credit.

### Listing Cards & Detail Page

- Listing media is stored under the Spatie collection **`images`** (in `HomeController::store()`). Always read it back with `getMedia('images')` / `getFirstMediaUrl('images')`.
- Fixed: listing detail page showed "لا توجد صور" because `show.blade.php` read `getMedia('listings')` (wrong collection) — corrected to `getMedia('images')`.
- The reusable `partials/listing-card.blade.php` is fully clickable: the whole card is wrapped in a single `<a>` to `listings.show` (no nested title anchor).
- The detail page has a native Alpine.js image lightbox (no new JS dependency). Clicking the main image or any thumbnail opens a fullscreen `full_hd` overlay that reuses the existing `activeIdx`/`images` state. Supports prev/next arrows + counter (when >1 image), touch swipe, click-backdrop/X to close, and keyboard Escape/←/→.

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
- Fixed: "Failed to authenticate with Paymob." on ad checkout. `PaymobService` used wrong endpoints — `/auth/login` → corrected to `/auth/tokens`, and `/ecommerce/payment_links/payment_keys` → `/acceptance/payment_keys`. Now reads creds via `config('services.paymob.*')` (not raw `env()`, so `config:cache`-safe) and logs the full status + body on any failed call (`Log::error`) instead of silently returning null. Same `PaymobService` is shared by the points checkout (`PaymentController`), so both flows are fixed. Also corrected `PaymobService::$baseUrl` from `https://egypt.paymob.com/api` to the working `https://accept.paymob.com/api` — the full Paymob flow (Auth → Order → Payment Key) is now verified working end-to-end. Also removed a stray Arabic `ال` prefix from `PAYMOB_HMAC_SECRET` in `.env`. Note: `PAYMOB_IFRAME_ID` still needs a real numeric ID from the Paymob dashboard (currently placeholder).

**3. Public Ad Spaces Page** — implemented
- `/ads/pricing` → `AdSpacesController` → `frontend.ads.pricing` view
- Passes `selfServiceEnabled` flag and `config('ad_pricing')` to the view
- Linked from footer and navigation

## Legal Pages

- `LegalPage` model (Spatie translatable `title`/`content` as JSON), served at `/{slug}` via `LegalPageController` (`legal.show`), rendered by `resources/views/pages/show.blade.php`. Footer auto-lists all active pages.
- Seeded slugs (`LegalPageSeeder`): `privacy-policy`, `terms-and-conditions`, `acceptable-use-policy`, `about-us`, `contact-us`, `refund-policy`. (`cookies-policy` via `CookiePolicySeeder`.)
- Added **`refund-policy`** ("سياسة الاسترجاع والاسترداد", Arabic only) — required by the Paymob merchant agreement. The points checkout (`pricing.blade.php`) now has a single mandatory agreement checkbox above the plans grid; an Alpine `refundAccepted` flag disables all "شحن الرصيد" buttons until checked, and each form posts a hidden `refund_policy_accepted=1`. `PaymentController::checkout()` enforces it server-side (`accepted` rule, Arabic error) as a fallback.

## Planned Next Steps

- **Hosting:** Deploy via **Laravel Forge** (planned)
- **UI/UX:** Hire a designer from **خمسات (Khamsat)** for frontend redesign
- **SMS Provider:** Integrate an Egyptian SMS gateway — candidates are **Connekio** or **Sentry SMS** — to replace the log driver for OTP delivery
