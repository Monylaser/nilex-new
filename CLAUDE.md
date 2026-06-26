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

**Phase C.3 — profile page (`profile/edit.blade.php`):** Translated all 44 hardcoded strings in the only live profile view (the 3 dead Breeze `profile/partials/*` were left untouched) via a **new `ui.profile.*` group** (ar + en) sub-grouped `trust`/`info`/`ratings`/`password`/`danger`/`delete`. Covered: trust card badges + member-since/points/ratings placeholders, the profile-info form (avatar/name/phone/whatsapp/governorate/city/bio labels + placeholders + save), ratings section, password-change form, danger zone, and the delete-account modal. The two flash-message displays keep their stable session **keys** (`'profile-updated'`/`'password-updated'`) and only the displayed text is translated; `ProfileController` has no user-facing strings (changes none). Governorate/city are plain free-text inputs (not `Location`-model selects), so no accessor work. No JS strings (Alpine is only a `deleteOpen` boolean), so no `@json` bridge. Both `dir="rtl"` (page wrapper + modal) made conditional (`app()->getLocale() === 'ar' ? 'rtl' : 'ltr'`). **Global Carbon locale fix:** `SetLocale` middleware now also calls `Carbon::setLocale($locale)` right after `App::setLocale()`, so relative dates (`diffForHumans()`, e.g. the profile "member since", listing cards, dashboard) render in the active locale everywhere, not just on the profile page. Tests: 263 passing, 0 failures (unchanged).

**Phase C.4 — dashboard chrome / points / payment / legal-page chrome:** Translated the remaining authenticated-area views via three **new `ui.*` sub-groups** (ar + en): `ui.points.*` (`points/history.blade.php` — balance card, quick actions, transactions table, empty state, earn-points guide), `ui.payment.*` (`payment/success.blade.php` + `payment/failed.blade.php`), and `ui.pages.*` (`pages/show.blade.php` **chrome only** — breadcrumb, "official document" header, "last updated", "other docs"; the DB-driven Spatie translatable `$page->title`/`$page->content` are untouched). **Deleted the confirmed-dead `resources/views/dashboard.blade.php`** (the `/dashboard` route resolves to the Livewire `UserDashboard`, no `view('dashboard')` call anywhere; the file also had stale `->points`/`status==='active'` bugs). **Points-rule bug fix:** `points/history.blade.php` previously advertised **+10** for posting a listing (both the quick-action subtitle and the earn-guide) — corrected to **+3** to match the Point Economy Rules source of truth. **`pages/show.blade.php`** `dir="rtl"` made conditional and the hardcoded `og:locale=ar_EG` / `og:site_name=نايلكس` are now locale-aware (`ui.pages.og_locale` → `ar_EG`/`en_US`, `og_site_name` → نايلكس/`Nilex`); breadcrumb home reuses existing `ui.footer.link_home`. The history transaction date switched from `->format('d M Y')` to `->translatedFormat('d M Y')` so the month name follows the C.3 Carbon-locale fix. **Deferred (flag only, same as B.3d):** `$transaction->description` strings are hardcoded Arabic persisted at credit time (`HomeController`/`OtpController`/`RegisteredUserController`/`SocialiteController`) — not retroactively localizable; the `.prose-arabic` legal CSS stays RTL-styled. **Project-wide numeral rule:** all numbers must always render as Western/Latin digits (1,2,3) regardless of locale. PHP/Laravel already does this for dynamic numbers (`number_format()`/`{{ }}`); the only Arabic-Indic numerals were hand-typed literals — converted all 9 in `lang/ar/ui.php` (`panel_stat_*`, `hero.badge`, `hero.stat_*`, `footer.bio`, `footer.gift_teaser`, Arabic words/`ك` "thousand" letter kept, digits only changed), collapsed B.5's `pricing.blade.php` step-number ternary to a single Western `['1','2','3']`, and switched `home.blade.php`'s 3 hero-stat fallback defaults to Western. Tests: 263 passing, 0 failures (unchanged). **Phase C.4 complete.**

**Phase C.5 — self-service ad campaign management (`dashboard/ads/{index,create,show}.blade.php`):** Translated all ~65 seller-facing CRUD strings via a **new `ui.ads_dashboard.*` group** (ar + en) sub-grouped `common` (back links + a `:count`-placeholder `day_fallback`), `payment_status` (4) + `approval_status` (3) — both shared across index/show, `index`, `create`, and `show`. **Placement/duration labels reuse the exact B.5 `$localeLabel` closure pattern** (reads `label_ar`/`label_en` from `config/ad_pricing.php` by locale) — index/show now fetch the whole config *row* (`config("ad_pricing.placements.$key")`) instead of the old single `label_ar` string lookup, then pass it through `$localeLabel`. **`create.blade.php` Alpine calculator:** `durationLabel` getter now reads a locale key (`localeLabelKey: @js($isRtl ? 'label_ar' : 'label_en')`) with a `@js`-bridged `day_fallback` (`.replace(':count', …)`); **`Intl.NumberFormat('ar-EG')` → `'en-US'`** to comply with the C.4 Western-numeral rule. **`->name_ar` → `->name`** on both Category dropdowns (create + show; controller already loads `name_en`, B.1 accessor resolves). All 3 `dir="rtl"` made conditional (`$isRtl ? 'rtl' : 'ltr'`); index L113 `space-x-reverse` now `$isRtl`-gated. **Deferred to C.7 (flag only, untouched):** the ~13 hardcoded Arabic validation messages in `StoreSellerAdCampaignRequest` + the 1 in `SellerAdCampaignController::retryPayment()` — views render them verbatim via `$errors`, so view translation is presentation-only. Tests: 263 passing, 0 failures (unchanged).

**Phase C.6 — Livewire components (`user-dashboard`, `listing-grid`, `smart-ad-creator`, `business-dashboard`):** Translated the 4 remaining Livewire views via three **new `ui.*` groups** (ar + en): `ui.dashboard.*` (user-dashboard — welcome/greeting `:name`, stat cards, listings table, offers, empty state, `wire:confirm` dialogs, tooltips, 2 Chart.js labels), `ui.listing_grid.*` (filters, GPS, 2 JS `alert()` messages), and `ui.ad_creator.*` (smart-ad-creator). **Chart.js labels:** the `<script>` blocks live in `@section('footer-scripts')` of `.blade.php` files (Blade-compiled), so labels use plain inline `'{{ __('ui.dashboard.chart_views') }}'` / `chart_whatsapp_clicks` — **no `@json` bridge needed**; the 2 chart keys + a `listing_count_suffix` (`إعلان`) are **shared** by both user-dashboard and business-dashboard. JS `alert()` strings (quote-free) and the Alpine `x-text` fallback inline `'{{ __() }}'` the same way. The 2 delete-confirm dialogs are **kept separate** (`confirm_delete` mobile vs `confirm_delete_listing` desktop, different wording). Reused existing keys: `ui.sections.verified`, `ui.sections.currency` (all `ج.م` incl. business-dashboard L74/L138), `ui.leads.nav_link`. **`->name_ar` → `->name`:** user-dashboard category cell, listing-grid Category + CarBrand dropdowns (CarBrand accessor from B.4). smart-ad-creator textarea `dir="rtl"` made conditional. **smart-ad-creator is an admin (Filament) component** (rendered in `filament/modals/ai-container.blade.php`) — strings are translated now but **won't take visual effect until Phase D enables `SetLocale` on `/admin`** (the lang-file comment flags this); Phase D then only flips the switch, no further translation. **Deferred to C.7 (flag only, untouched):** all server-side messages — `UserDashboard.php` (5 offer/feature/delete flashes), `SmartAdCreator.php` (`$messages` + `errorMessage` literals; the Gemini `getSystemPrompt()` Arabic stays as a functional AI prompt), `BusinessDashboard.php` (CSV `name_ar` + competitor `category`). Blade/JS comments left Arabic (not rendered). Tests: 263 passing, 0 failures (unchanged).

**Phase C.7 — deferred server-side messages (final Phase C sub-step; authenticated area now fully bilingual):** Translated every deferred hardcoded Arabic server-side message into a **new top-level `lang/{ar,en}/server.php`** (sub-grouped `auth`, `ads`, `payment`, `dashboard`, `offer`, `message`, `ai`) via native `__('server.*', [...])` with Laravel's `:placeholder` interpolation (pure-PHP, no JS `.replace()` bridge). Covered: `OtpController` (validation/flash/error) + `OtpService` throttle; `RegisteredUserController` contact/uniqueness + `DeviceLimitService`; `SocialiteController` (error/limit/success); `SellerAdCampaignController` + the 8 previously-**English** `AdCampaignPaymentService` `RuntimeException`s (now bilingual); `PaymentController` refund msg; `UserDashboard` (5 flashes) + `Listing::featureWithPoints`/`featureCostStrict` exceptions; `SmartAdCreator` (`messages()`, errorMessage, progress-step texts — admin component, effective only after Phase D flips `/admin` `SetLocale`). **Also swept in (not in original list):** ban gate (`EnsureUserIsNotBanned`), OTP gate (`EnsureOtpIsVerified`), offer JSON (`ListingController`), `MessageController` self-message. **`StoreSellerAdCampaignRequest`** now uses the **native** path — `messages()` deleted, relying on C.1's `validation.php` default rules + `attributes`, plus one `custom.category_id.required` entry; the programmatic `withValidator` message moved to `server.ads.category_only_category_page`. **`name_ar`→locale-aware fixes:** `BusinessDashboard` CSV + `SellerListingAnalyticsService` competitor (`->name`) and the `getCategoryPerformance` raw join (conditional `name_ar`/`name_en` column by locale). **Permanently deferred (unchanged):** persisted point-transaction reason strings (`PointService::credit` reasons, referral/gift/welcome — stored once at credit time), `SmartAdCreator::getSystemPrompt()` + Gemini prompt text + Arabic regex parsers (functional AI/parsing, not UI), the `SocialiteController` default user name `مستخدم نايلكس` and `SmartAdCreator` AI data-defaults (persisted/content values, not chrome). **Out of scope (recommended follow-up phase):** `app/Notifications/*` (mail/DB notifications) and all `app/Filament/*` (Phase D). Tests: 263 passing, 0 failures (unchanged). **Phase C is complete — the entire authenticated area is bilingual except the intentionally-permanent exceptions above.**

**Phase D — Filament admin panel (assessed in full, then DEFERRED by deliberate business decision):** Phases A, B.1–B.5, and C.1–C.7 are **complete and committed** — the entire public frontend and authenticated user-facing area (auth, profile, dashboard, points, payment, ad management, Livewire components) is bilingual. Phase D (translating the Filament v5.4 admin panel) was **fully scoped** via a comprehensive Discovery report: a fresh re-scan found **56 files** under `app/Filament/` containing hardcoded Arabic, totaling **~720–760 translatable strings** (≈ the size of Phase B + Phase C combined) — concentrated in Resources (~35%), Schemas/Forms/Fields (~21%), Tables (~17%), Pages (~13%), and Widgets (~12%). **Decision: Phase D is deferred in its entirety (D.0 → D.6), including D.0 (wiring `SetLocale` onto `/admin`).** No changes to `AdminPanelProvider` or any file in `app/Filament/`. **Rationale (business, not technical):** the admin panel serves **internal staff only**, who are **all Arabic speakers** today, so there is **no immediate commercial value** in translating it — or even enabling locale switching on it. The admin panel therefore **remains fully Arabic** (both the framework chrome and our custom labels/content) exactly as-is. **No outstanding technical debt:** Filament 5.4 **ships complete Arabic translations for its own framework chrome** (Save/Create/Delete/pagination/modals/search/etc.) bundled in `vendor/filament/*/resources/lang/ar/` and auto-loads them from `app()->getLocale()` — so nothing is broken or half-done on the framework side; the panel renders correctly in Arabic because `config('app.locale')` defaults to `ar`. **Revisit only if the business need actually changes** (e.g. onboarding a non-Arabic-speaking admin); at that point the deferred Discovery report (mechanism: add `SetLocale` to the panel middleware stack since `users.locale` already exists and Filament localizes for free) becomes the starting point. **Note:** `smart-ad-creator` (an admin Filament component) was pre-translated in C.6 in anticipation — those keys exist but stay invisible until/unless `/admin` locale switching is ever wired. **This officially closes the full localization project (Phases A–D).**

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

- **Tests:** 322 passing, 0 failures
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
- **Share button (detail page only):** an additive, self-contained Alpine component lives in the title+meta card of `show.blade.php` (left column → visible on mobile + desktop). It has its **own `x-data`** (all names `share*`-prefixed, e.g. `shareMenuOpen`/`shareNative`/`copyLink`) — **zero shared state / no name collision** with the page-level `x-data`. On click it tries the **Web Share API** (`navigator.share`, ideal on mobile) and falls back to a small menu: **WhatsApp** (`wa.me/?text=`), **Facebook** (`facebook.com/sharer`), and **Copy link**. No new JS library — Alpine only. URL/title are injected via `@js(url()->current())` / `@js($listing->title)`. Copy uses `navigator.clipboard` (requires a **secure context / HTTPS** — won't work on local plain-HTTP dev, works on the real SSL server) with a `document.execCommand('copy')` textarea fallback. Strings live in the new `ui.share.*` group (ar+en: `button`, `heading`, `whatsapp`, `facebook`, `copy`, `copied`).
- **Similar listings (detail page, additive):** the bottom of `show.blade.php` shows a "إعلانات مشابهة / Similar listings" section. The query lives **inline in `ListingController::show()`** (not a View Composer / `@php`): same `category_id`, excludes the current `id`, `status = Listing::STATUS_PUBLISHED` only, ordered by **same `province_id` first** (`orderByRaw('CASE WHEN province_id = ? THEN 0 ELSE 1 END')`) then `latest()`, `limit(6)`. **Price range is intentionally NOT a similarity factor** (it shrinks results and would hide the section too often). It eager-loads `['category','location','user']` to avoid N+1 (mirrors `CategoryController::show()`), and is passed to the view as `$similarListings`. The section **reuses `partials/listing-card.blade.php` as-is** (passing `isFeatured => $similar->is_featured`) in a 2/3-col grid and **renders only `@if($similarListings->isNotEmpty())`** — no empty state when there are none. Heading key: `listing.detail.similar_heading` (ar+en). Cap is **6** to fill a 3-col row evenly without overwhelming the page. Covered by `tests/Feature/Listings/SimilarListingsTest.php` (same-category/published-only, current-listing excluded, other-category/pending excluded, province ordering, hidden-when-empty, 6-cap).

### SEO — schema.org JSON-LD (detail page only)

- **Additive, presentation-only:** a single `<script type="application/ld+json">` was added **inside the existing `@push('meta')`** of `show.blade.php`. No existing meta tag (`og:*`, `twitter:*`, `description`), no controller, and no other file were changed. The structured data is built in a self-contained `@php` block in the meta stack (it cannot reuse the content block's `$displayFields`, because `@push('meta')` renders *before* `@section('content')`).
- **Schema strategy (hybrid):** every category uses **`Product`**; **cars** (`category->slug === 'cars'`) use the multi-type **`["Product","Car"]`** — in schema.org `Car` is a subtype of `Product`, so it keeps full Product/Offer rich-result eligibility while adding vehicle properties. Real estate (and any other coded-spec category) stays `Product` with specs emitted as **`additionalProperty`** (`PropertyValue`). `RealEstateListing` was rejected because it descends from `WebPage`/`CreativeWork` and produces **no price rich result** in organic Google results.
- **Fields (Google Rich Results compliant):** `name` (title), `description` (`strip_tags`, capped 5000), `image` (array of absolute `full_hd` URLs, omitted when none), `category` (locale-aware name), `sku` (`NILEX-{id}`), `itemCondition` (`NewCondition`/`UsedCondition` from the top-level `condition` column; omitted when null), and `offers` → `price` (numeric, no separators: `number_format($price, 2, '.', '')`), `priceCurrency` `"EGP"`, `availability` `InStock`, `url`, and `seller` (Person = listing owner). Cars additionally emit `brand`/`model` (from `carBrand`/`carModel` or the manual `car_brand_other`), `vehicleModelDate` (year), `mileageFromOdometer` (`QuantitativeValue`, `unitCode: KMT`), `fuelType`/`vehicleTransmission` (coded values translated via `wizard.options.*`), and `color`.
- **Deliberate omissions:** `offers` is **omitted entirely when `price_type === 'on_contact'`** (no real numeric price to advertise). No `aggregateRating`/`review` (ratings exist only at the seller level, not per-listing — and self-serving review markup is penalized by Google). No `gtin`/`mpn` (N/A for used classifieds). `priceValidUntil` not set (optional; Google warning only). Any car-specific field is skipped when its data is absent (guarded with `empty()` checks), so older listings simply produce a leaner-but-valid `Product`.
- **Security:** rendered via `json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)` **without** `JSON_UNESCAPED_SLASHES` — slashes stay escaped so a `</script>` inside a user's title/description **cannot break out** of the `<script>` block (XSS-safe).
- **Blade gotcha fixed during implementation:** the explanatory `{{-- … --}}` comment must **not** contain the literal token `@php`. Blade extracts `@php … @endphp` raw blocks (`storeUncompiledBlocks`) *before* it strips comments (`compileComments`), so an `@php` inside a comment pairs with the next real `@endphp` and silently swallows everything between (this manifested as `Undefined variable $fullUrls`). The comment was reworded to avoid `@php`/`@section`/`@push` literals.
- Tests: 268 passing, 0 failures (the 5 `SimilarListingsTest` + all detail-page renders exercise the new markup).

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

## Favorites System

An **additive** feature (no existing logic was modified) letting users save listings to revisit later.

**Data model**
- Table `favorites` (`2026_06_26_000001_create_favorites_table`): `user_id` + `listing_id` (both FK `cascadeOnDelete`), `timestamps`, **`unique(['user_id','listing_id'])`** to prevent duplicates + an index on `user_id`.
- `App\Models\Favorite` — `$fillable = ['user_id','listing_id']`, `belongsTo` `user()` / `listing()`.

**Model relations / helpers (additive only)**
- `User`: `favorites()` (HasMany), `favoriteListings()` (BelongsToMany via `favorites` pivot, `withTimestamps`), `favoritedListingIds()` and `isFavorited(Listing|int)`.
- `Listing`: `favorites()` (HasMany), `isFavorited(?User $user = null)` (delegates to `User::isFavorited`).
- **N+1 avoidance:** `favoritedListingIds()` is **memoized on the User instance** (`$favoritedListingIdsCache`) — one query per request regardless of how many cards render. The card partial calls `auth()->user()->isFavorited($listing->id)`, so existing controllers (home/category/search) were **not** modified to preload anything.

**Controller / routes** (`App\Http\Controllers\FavoriteController`)
- `POST /listings/{listing}/favorite` → `toggle()`, name `listings.favorite`. Defined **outside** the auth group (next to `listings.reveal-phone`); checks `Auth::check()` internally and returns **401** for guests (mirrors `ListingController::revealPhone`). Returns JSON `{ favorited: true|false }`.
- `GET /dashboard/favorites` → `index()`, name `dashboard.favorites`, placed **inside** the existing `['auth','otp.verified']` group (same protection as other dashboard pages). Paginates `favoriteListings()` (eager-loads `category,location,user`), ordered by `favorites.created_at` desc.

**Views**
- Heart toggle is a **self-contained Alpine island** (own `x-data`, no shared state):
  - `frontend/partials/listing-card.blade.php` — absolutely positioned over the image (`top-2 start-2`); since the card root is a single `<a>`, the button uses `@click.prevent.stop`. Guests → `/login` (`@guest` redirect + 401 handling, revealPhone pattern). Filled red heart when favorited.
  - `frontend/listings/show.blade.php` — a `fav*`-prefixed island next to the share button in the title card (Save/Saved pill).
- `dashboard/favorites.blade.php` — new "My Favorites" page (`<x-app-layout>`, conditional `dir`), **reuses `listing-card.blade.php`** in a grid + empty state + pagination (mirrors `dashboard/ads/index` styling).
- Discoverability: a red "مفضلتي" link was added to `user-dashboard` next to "حملاتي"/"العملاء".

**i18n:** new `ui.favorites.*` group (ar+en) — `title`, `subtitle`, `nav_link`, `back_dashboard`, `save`, `saved`, `add_tooltip`, `remove_tooltip`, `empty_title`, `empty_subtitle`, `browse_cta`.

**Tests:** `tests/Feature/Favorites/FavoriteToggleTest.php` (Pest, 6 tests) — add, toggle-off, duplicate prevention (unique constraint), guest 401, favorites page shows only the user's saved listings, and the `User::isFavorited` helper. Suite: **274 passing, 0 failures** (was 268; +6).

## Seller Response Rate

An **additive** public trust signal showing how reliably a seller responds to received offers. Built on a **dedicated `responded_at` timestamp** — deliberately **not** `updated_at` (which is a fragile proxy: any future row update would move it and silently corrupt historical accuracy).

**Data model**
- Migration `2026_06_26_000002_add_responded_at_to_offers_table` adds `offers.responded_at` (timestamp, nullable, after `status`; `Schema::hasColumn` guarded).
- `App\Models\Offer`: `responded_at` added to `$fillable` + a `casts()` returning `'responded_at' => 'datetime'`.

**Capture point (the only logic change)**
- `UserDashboard::acceptOffer()` / `rejectOffer()` — the existing single `$offer->update([...])` line in each was extended in-place to `'responded_at' => $offer->responded_at ?? now()`. The `?? now()` guard writes the timestamp **once** (first decision) and **never overwrites** it on any later status change. No other lines in those methods were touched.

**Service (`SellerListingAnalyticsService`, additive)**
- `RESPONSE_RATE_MIN_OFFERS = 5` — the statistical minimum.
- `responseRate(User $seller): ?float` — denominator = offers received (`receiver_id`) with `status != 'canceled'`; numerator = those with `status NOT IN ('pending','canceled')` (i.e. accepted **or** rejected). **`canceled` is excluded from BOTH** (a cancel is a buyer action, not a seller response opportunity). Returns `null` when the (non-canceled) denominator is `< 5` ("insufficient data" — a seller with one offer shouldn't show "100%"). Otherwise `round(%, 1)`.
- `averageResponseTime(User $seller): ?float` — mean of `(responded_at − created_at)` **in seconds** over offers that have a `responded_at`; honors the same `< 5` threshold and returns `null` when there's no responded offer. **Computed but not yet shown in the UI** (reserved for a later iteration).

**View (`resources/views/components/seller-trust-card.blade.php`)**
- This card (rendered on `frontend/listings/show.blade.php`) was **fully Arabic-hardcoded** and is now **fully translated** — all strings moved to a new `ui.seller_trust.*` group (ar+en): `seller`, `phone_verified`, `phone_unverified`, `member_since` (`:time`), `active_listings` (`:count`), `no_ratings`, `response_rate`, `response_rate_value` (`:rate`), `response_insufficient`.
- A new response-rate row resolves the rate live via `app(SellerListingAnalyticsService::class)->responseRate($seller)`; shows the percentage when available, or the **"insufficient data"** message (never a misleading number) below the threshold.

**Tests:** `tests/Feature/Offers/ResponseRateTest.php` (Pest, 10 tests) — rate calc at/above threshold, 100% case, per-seller scoping, `null` below minimum, `canceled` excluded from numerator+denominator **and** from the threshold count, `averageResponseTime` averaging + threshold, and the `responded_at` write semantics (set on first decision, **not** overwritten on a repeat). Suite: **284 passing, 0 failures** (was 274; +10).

## Listing Soft Deletes

**Why:** A foundation step before the upcoming **sale-confirmation + ratings** system. A rating will permanently reference the listing it was about, so a listing must **survive in the DB after the seller "deletes" it** — the previous behavior was a **hard delete** (`$listing->delete()` with no `SoftDeletes`), which would orphan/erase any future rating. This change is purely foundational: **no sale-confirmation/ratings tables or features were added here.**

**Model + schema**
- `App\Models\Listing` now uses `Illuminate\Database\Eloquent\SoftDeletes` (same pattern as `AdCampaign`). Added `'deleted_at' => 'datetime'` to `casts()`.
- Migration `2026_06_26_000003_add_deleted_at_to_listings_table` adds `deleted_at` via `$table->softDeletes()` (`Schema::hasColumn` guarded; `down()` uses `dropSoftDeletes`). No index added (consistent with `AdCampaign`; the `listings` table has no composite indexes — only implicit FK indexes + a single `price_type` index).

**Behavior change (the only logic change to deletion)**
- `UserDashboard::deleteListing()` (`app/Livewire/Frontend/UserDashboard.php`) — the `clearMediaCollection('images')` call was **removed**. The seller-facing "delete" is now a **soft delete**, and the listing's **media (images) are intentionally preserved** so a deleted listing can still be rendered in a later record (sale/rating history). Spatie only auto-purges media on **force delete**, not soft delete, so the rows survive until an explicit force delete.

**Public surfaces (no code change needed — verified)**
- All public reads go through Eloquent, so the SoftDeletes global scope excludes trashed automatically: home, category page, search (Scout `collection`/Meilisearch — `scout.soft_delete=false` ⇒ a soft delete fires the `deleted` event and Scout `unsearchable()` removes it from the index), and the detail page (`listings.show` route-model-binding returns **404** for trashed).

**Filament admin (`ListingResource` / `ListingTable`)**
- Added `Filament\Tables\Filters\TrashedFilter` to the table filters (Filament v5's TrashedFilter is self-contained: it strips the `SoftDeletingScope` via `baseQuery(...)` and toggles `withTrashed`/`onlyTrashed`/`withoutTrashed` — no `getEloquentQuery()` override or separate page needed).
- Added `RestoreAction` and `ForceDeleteAction` to the row `ActionGroup`. **`ForceDeleteAction` is restricted to `super_admin`** (`->visible(fn () => Auth::user()?->hasRole('super_admin'))`) because force delete is irreversible; `RestoreAction` is available to any panel user (admin/moderator/super_admin). The default `ListListings` tabs/badges (all/pending/published/flagged/rejected) now naturally exclude trashed via the global scope.

**Analytics scope fix (`CategoryPerformanceWidget`)**
- The 4 aggregation subqueries (`buildAggregatedCategoriesSubquery`) start from the **event** models (`ListingView`/`ListingPhoneClick`/`ListingWhatsappClick`/`Offer`) and `join` `listings`, so the Listing SoftDeletes global scope does **not** apply. Added an explicit `->whereNull('listings.deleted_at')` to each so deleted-listing events are excluded from admin category analytics. (All other listing analytics — `CategoriesChartWidget`, `GovernoratesChartWidget`, `SellerListingAnalyticsService::getCategoryPerformance` — use `Listing::query()` as the base, so the scope already applies; no change needed.)

**Tests:** `tests/Feature/Listings/ListingSoftDeleteTest.php` (Pest, 9 tests) — delete is now soft (row survives + `deleted_at` set, found only via `withTrashed`), owner-only IDOR still rejected, trashed listing hidden from home/search/category and 404 on detail, **media rows preserved** after delete, admin `TrashedFilter` query semantics (default hides / `withTrashed` reveals / `onlyTrashed` isolates), and `CategoryPerformanceWidget` aggregation excludes soft-deleted. Suite: **293 passing, 0 failures** (was 284; +9).

**Deferred (noted, not done here):** `RestoreAction` is currently visible to all panel users; if finer-grained control is desired later it can be gated via **FilamentShield** permissions per role (the `ForceDeleteAction` super_admin gate is already in place). No bulk Restore/ForceDelete actions were added (single-record row actions only).

## Listing Closing Flow (Step 1 — closing-type modal)

**Why:** A **foundation step** for the upcoming **sale-confirmation + ratings** system. Before a seller "deletes" a listing they must now declare **how** it was closed, so a future sale/rating record can be tied to the right outcome (and the right buyer). This is **Step 1 only** — the closing-type selection modal; the buyer-selection sub-flow and any new DB tables are deliberately **not** built yet.

**Replaces the native `wire:confirm`.** Browser-native `window.confirm()` (`wire:confirm`) can only show plain text + OK/Cancel — it cannot host a 3-option choice or a buyer list. It was therefore removed **from the two delete buttons only** (mobile card + desktop table in `user-dashboard.blade.php`); the **feature buttons keep their `wire:confirm`** (`confirm_feature`) untouched. The old `ui.dashboard.confirm_delete` / `confirm_delete_listing` keys are now unused but left in place.

**UI (single shared modal):** A custom Livewire + Alpine modal is rendered **once** outside the mobile/desktop `@foreach` loops (no per-row duplication). Both delete buttons now call `wire:click="openClosingModal($listing->id)"`. The modal (`x-show` entangled to `closingModalOpen`, backdrop-click + Escape close, conditional `dir`) presents **three radio-card options**:
1. **`sold_platform`** — "تم البيع عن طريق المنصة" → **stub** in this step (no delete; Step 2 will show the buyer list).
2. **`sold_external`** — "تم البيع خارج المنصة" → soft-deletes (normal flow).
3. **`canceled`** — "إلغاء بدون بيع" → soft-deletes (normal flow).

The confirm button is **disabled until a type is selected** (`@disabled(is_null($closingType))`).

**Livewire state (kept inside `UserDashboard`, no new component):** Livewire 4 multi-step lives comfortably in the existing component. Added public props `closingModalOpen` (bool), `closingListingId` (?int — **the single source of truth** for which listing is being closed), `closingListingTitle` (?string, display only), `closingType` (?string). Methods: `openClosingModal(int $id)` (verifies ownership via `where('user_id', Auth::id())->findOrFail`, pins the id, resets type), `closeClosingModal()` (resets all four props), `confirmClosing()` (**re-verifies ownership** off `closingListingId` — never trusts the client; soft-deletes for `sold_external`/`canceled`, no-op stub for `sold_platform`). The existing `deleteListing()` is unchanged.

**Buyer-list source (confirmed, used in Step 2 — not built yet):** The `SellerLead` model already captures per-listing contacts. The Step 1 stub carries `closingListingId` so Step 2's buyer query will be **strictly scoped to the exact listing being closed** (`SellerLead::where('listing_id', $closingListingId)->whereIn('source_type', ['phone_reveal','offer'])->whereNotNull('buyer_id')`) — **not** all of the seller's contacts across all listings.

**Wording note (UX):** because deletion is a **soft delete** (the row survives; restore is **admin-only**, not seller-facing), the option descriptions deliberately say **"سيُزال الإعلان من المنصة"** ("the listing will be removed from the platform"), never "حذف", to avoid implying a seller-accessible undo.

**i18n:** all new strings are bilingual from the start under `ui.dashboard.*` (ar+en): `close_modal_title`, `close_modal_subtitle` (`:title` placeholder), `close_opt_{sold_platform,sold_external,canceled}` + matching `_desc`, `close_confirm`, `close_cancel`. No hardcoded text in any language.

**Tests:** `tests/Feature/Listings/ListingClosingModalTest.php` (Pest, 5 cases) — `openClosingModal` sets state + pins the exact id, IDOR rejected (other user's listing), `confirmClosing` soft-deletes for **both** `sold_external` and `canceled` (parametrized), and `sold_platform` does **not** delete (Step 1 stub). Suite: **298 passing, 0 failures** (was 293; +5).

**Deferred (next phase):** the buyer-selection sub-flow for `sold_platform`, the sale-confirmation/ratings DB schema, and persisting the chosen closing reason — all to come **after** the UI direction is fully locked.

**Bug fix:** replaced `x-data="{ open: @entangle('closingModalOpen') }"` + `x-show="open"` with direct `x-show="$wire.closingModalOpen"` on the 3 `x-show` nodes — `@entangle` returned a proxy object (always truthy) causing the modal to open on page load, an empty subtitle, and Idiomorph skipping the `@disabled` update inside the Alpine scope.

**Bug fix 2 (close + toggle):** the `x-show`/`x-transition` nodes were further replaced with a Livewire-native `@if($closingModalOpen)` wrapper (morph adds/removes the node → cancel/backdrop close deterministically; Escape kept via a tiny `x-data="{}"` + `@keydown.escape.window`; fade animation intentionally dropped for reliability), and the option radios switched from `wire:model.live` to `wire:click="toggleClosingType($type)"` (sets `$closingType`, or **clears it to `null` when the already-selected option is re-clicked** — toggle UX) guarded by a `CLOSING_TYPES` allow-list. Suite: **301 passing** (was 298; +3).

**Bug fix 3 (toggle-off visuals):** after a toggle-off the real `<input type="radio">` stayed visually filled (its live `.checked` DOM property diverges from the `checked` *attribute* / `defaultChecked`, and Idiomorph preserves live form-control state), and the confirm button stayed enabled (`wire:loading.attr="disabled"` restored the pre-request enabled state, clobbering the morph's `@disabled`). Fixed by making the options **non-form** elements — `<button type="button" role="radio" aria-checked>` with a **class-driven dot** rendered purely from `$closingType === $closeOption` (same server-class pattern as the card highlight, no live form property) — and by **removing `wire:loading.attr="disabled"`** so `@disabled(is_null($closingType))` solely governs the confirm button (`confirmClosing()` is already server-guarded).

## Sale Confirmation & Reviews — Data Layer (Step 2 foundation)

The **DB/model foundation** for the dual sale-confirmation + ratings system. **UI is a later phase** — the buyer-selection Modal Step 2 (wired to the existing `sold_platform` stub in `UserDashboard::confirmClosing()`) and the rating form are **not built here**; neither are their `ui.sale_confirmation.*` / `ui.reviews.*` lang groups nor the `seller-trust-card` rating display (all deliberately deferred to the UI phase, where their keys will be added AR/EN from the first line).

**Tables (migrations `2026_06_26_000004/000005/000006`):**
- **`sale_confirmations`** — `listing_id`, `seller_id` (denormalized from `listing.user_id`), `buyer_id`, `status`, `seller_confirmed_at`, `buyer_confirmed_at`, `canceled_at`, `canceled_by`. **`unique(['listing_id','buyer_id'])`** (one request per buyer per listing) + indexes on `status`/`seller_id`/`buyer_id`.
- **`reviews`** — `sale_confirmation_id` (**unique** → one review per sale), `reviewer_id` (buyer), `reviewee_id` (seller, **denormalized + indexed** — the rating-aggregate axis), `listing_id` (**denormalized + indexed**, immutable — lets "all reviews for a listing/seller" run single-table, no join), `rating` (unsignedTinyInteger 1–5), `comment` (nullable text, **stored now, displayed later**).
- **`users`** — `ratings_avg` (decimal(3,2) nullable) + `ratings_count` (unsignedInteger default 0), denormalized cache.

**State machine (`SaleConfirmation` model):** seller always initiates (selecting the buyer = seller's confirmation), so a row is born `pending` with `seller_confirmed_at` set. `pending → confirmed` via `confirmByBuyer()` (sets `buyer_confirmed_at` + `status`); `pending → canceled` via `cancelBySeller(?int)`. **Both transitions are allowed only from `pending`** — `confirmed` is **locked permanently** (no cancel after full confirmation), and a `canceled` sale can't be confirmed. `isFullyConfirmed()` = both timestamps set (timestamps are source of truth; `status` is the indexed mirror). `canInitiateForListing(int)` enforces **one confirmed sale per listing** (a `pending`/`canceled` row does NOT block; only a `confirmed` one does) — the future Step 2 action calls it before creating.

**`restrictOnDelete` everywhere + SoftDeletes coupling:** `listing_id`/`seller_id`/`buyer_id` on `sale_confirmations` and `sale_confirmation_id`/`reviewer_id`/`reviewee_id`/`listing_id` on `reviews` are all **`restrictOnDelete`** (only `canceled_by` is `nullOnDelete`). The `listing()` relation on both models is **`->withTrashed()`** because the listing is soft-deleted right after closing — without it the relation would return `null`. `restrictOnDelete` on `listing_id` also blocks an admin `ForceDeleteAction` on a listing that has a sale, protecting rating history.

**Account-deletion guard (the key safety mechanism — Discovery finding):** `User` has **no SoftDeletes**, so `ProfileController::destroy()`'s `$user->delete()` is a **hard delete**, and `listings.user_id` is **`onDelete('cascade')`** (a seller's listings are physically cascade-deleted on account deletion, bypassing SoftDeletes entirely). With `restrictOnDelete` on the new tables, any user who is party to a sale/review would hit an unhandled `SQLSTATE 1451` 500. So `destroy()` now calls **`$user->hasSalesOrReviews()`** (true if the user is seller/buyer on any `sale_confirmation` OR reviewer/reviewee on any `review`, **any status incl. pending/canceled** — deliberately matching the DB constraint exactly) and, if true, redirects back with a translated `server.account.delete_blocked` (AR/EN) error in the `userDeletion` bag **before** logout/delete. (Account-deletion policy decided as **block**, not soft-delete-user or anonymize.)

**Ratings denormalization (`ReviewObserver`, registered in `AppServiceProvider`):** mirrors `PointTransactionObserver`'s `points_balance` pattern. On review `created/updated/deleted` it recomputes `AVG(rating)`/`COUNT(*)` for the `reviewee_id` and writes `users.ratings_avg`/`ratings_count` via **`updateQuietly`** (no observer loop); on `updated` it also recomputes the **previous** `reviewee_id` if it changed. `ratings_avg` resets to `null` when the last review is removed. Lets the future `seller-trust-card` read the aggregate with **zero extra queries**.

**IDOR note (enforced in the deferred Step 2 action, not here):** when wiring `sold_platform`, the buyer must be validated against `SellerLead` (`listing_id` + `buyer_id` + `source_type IN (phone_reveal, offer)` + non-null `buyer_id`) server-side, plus `buyer_id !== seller_id` and listing-ownership re-check.

**Tests:** `tests/Feature/Sales/SaleConfirmationDataLayerTest.php` (Pest, 21 cases) — creation, `unique(listing_id,buyer_id)`, same-buyer-different-listing, `withTrashed` listing relation survives soft-delete, full state machine (pending→confirmed, pending→canceled, no-cancel-after-confirmed, no-confirm-after-canceled), one-buyer-per-listing guard (pending doesn't block, confirmed does), `ReviewObserver` avg/count on created/updated/deleted + null-reset, and the account-deletion guard (allows clean users; blocks seller-in-pending, buyer-in-canceled, reviewer/reviewee-with-review). Suite: **322 passing, 0 failures** (was 301; +21).

**Deferred (next phase — UI):** Modal Step 2 buyer-selection wired to `confirmClosing()`'s `sold_platform` stub (with the `SellerLead` IDOR validation + `canInitiateForListing` guard), the buyer-side confirm/seller-side cancel actions calling `confirmByBuyer()`/`cancelBySeller()`, the rating form, the `seller-trust-card` rating display, and the `ui.sale_confirmation.*` / `ui.reviews.*` lang groups (AR/EN).

## Listing Closing Flow (Step 2 — buyer-selection sub-flow)

Wires the previously-stubbed **`sold_platform`** option to a buyer-selection step that creates the `SaleConfirmation` row (data layer above) and closes the listing. **No new migrations** — the `sale_confirmations`/`reviews`/`users.ratings_*` schema was already created in the data-layer step; this is purely the UI + Livewire action layer.

**Same modal, two steps (no second modal).** Added `public int $closingStep = 1` + `public ?int $selectedBuyerId = null` to `UserDashboard`. The existing `@if($closingModalOpen)` modal body is now split into `@if($closingStep === 1)` (the unchanged 3-option type picker) and `@if($closingStep === 2)` (buyer list) — Livewire morph swaps the body, so backdrop/Escape/cancel close deterministically (same reliability rationale as the Step 1 bugfixes). Chosen over a second modal for the least navigation/confusion. `closeClosingModal()`/`openClosingModal()` now also reset `closingStep`/`selectedBuyerId`.

**Transition:** `confirmClosing()`'s `sold_external`/`canceled` cases are unchanged (soft-delete + close). The `sold_platform` case **no longer no-ops** — it sets `closingStep = 2` (and clears `selectedBuyerId`); **still no delete here.** A `backToStep1()` action returns to the type picker (clearing the buyer choice).

**Buyer query (`buyerLeads()`):** `SellerLead::where('listing_id', $closingListingId)->whereIn('source_type', ['phone_reveal','offer'])->whereNotNull('buyer_id')->with('buyer')->latest()->get()->unique('buyer_id')->values()`. Strictly scoped to the listing being closed; `whatsapp_click` is **excluded** (not a "buyer" signal); `unique('buyer_id')` collapses a buyer who both revealed phone **and** sent an offer into one row (eligible sources live in `UserDashboard::BUYER_LEAD_SOURCES`). Fetched in `render()` **only** when `closingModalOpen && closingStep === 2` (else empty collection — no wasted query). Each row shows the **buyer name** + a verified tick (`is_phone_verified`, reusing `ui.sections.verified`) + contact type (phone/offer) + `created_at->diffForHumans()`. **Offer amount is intentionally NOT shown** (simplification; can be added later). Buyer rows use the same server-rendered class-driven toggle-card pattern as Step 1 (`wire:click="selectBuyer($id)"`, re-click clears) — no real radio, avoiding the morph `.checked` divergence.

**Empty state:** when `buyerLeads()` is empty (nobody revealed phone / sent an offer for this listing) the list is replaced with a clear message (`ui.sale_confirmation.no_buyers_*`) and the confirm button is hidden — only "back" remains. Never a silent empty list.

**Confirm (`confirmSaleToBuyer()`):** guards in order — (1) re-verify **ownership** (`Listing::where('user_id', Auth::id())->findOrFail($closingListingId)` — never trusts the client), (2) reject **seller===buyer**, (3) **IDOR** check that `selectedBuyerId` is a real eligible `SellerLead` for **this** listing, (4) `SaleConfirmation::canInitiateForListing()` (a `confirmed` sale blocks; closes modal with `already_confirmed`). On pass: a single **`DB::transaction`** does `SaleConfirmation::updateOrCreate(['listing_id','buyer_id'], [seller_id, status=pending, seller_confirmed_at=now(), null timestamps])` **then** `$listing->delete()` (soft) — atomic so a failed create never leaves a half-closed listing. A `// TODO` marks the deferred buyer notification. Flash `server.sale_confirmation.created` + `closeClosingModal()`.

**i18n (bilingual from the first line):** new `ui.sale_confirmation.*` (ar+en) — `step2_title`, `step2_subtitle` (`:title`), `buyer_via_phone`, `buyer_via_offer`, `no_buyers_title`, `no_buyers_subtitle`, `back`, `confirm_select`; new `server.sale_confirmation.*` (ar+en) — `created`, `invalid_buyer`, `already_confirmed`, `select_required`.

**Tests:** `tests/Feature/Sales/SaleConfirmationStep2Test.php` (Pest, 11 cases) — sold_platform→step 2 (no delete), buyer list eligibility/scoping/de-dup, empty list, create-pending+soft-delete on confirm, IDOR (buyer with no lead / lead on another listing / listing owned by someone else), `canInitiateForListing` block, seller≠buyer, `selectBuyer` toggle-off, `backToStep1`. Suite: **333 passing, 0 failures** (was 322; +11).

**Deferred (next phase):** the **buyer notification** (`SaleConfirmationRequested`) + the **buyer-side confirmation screen** (calling `confirmByBuyer()`) — deliberately built together as the next step, since a notification with no landing screen is half a feature; plus the seller-side `cancelBySeller()` action, the rating form, and the `seller-trust-card` rating display.

**Bug fix (soft-deleted listing relations — `Offer`/`SellerLead`):** Step 2's soft-delete of a listing surfaced a regression introduced by the `Listing` SoftDeletes feature: a still-`pending` `Offer` on the just-closed listing made `/dashboard` 500 (`Attempt to read property 'title' on null` at `user-dashboard.blade.php:259` `$offer->listing->title`) because `Offer::listing()` lacked `->withTrashed()` (unlike `SaleConfirmation`/`Review`). This **also** silently broke the Step 2 "confirm" button — `confirmSaleToBuyer()` committed fine, but the post-action Livewire re-render hit the same null and 500'd, so the UI showed nothing. **Fix:** added `->withTrashed()` to both `Offer::listing()` (the crash fix — resolves both symptoms, since the only `$offer->listing` *relation* read in the whole codebase is that one dashboard line) and `SellerLead::listing()` (consistency — the leads pages were already null-safe with `?->`, so this only upgrades the degraded "—" to the real closed-listing title). **No migration** — Eloquent relation change only. Why the original Step 2 tests missed it: they create `SellerLead` rows directly (no real `Offer`), so `incomingOffers` was empty; the new regression test creates a **real `Offer`** (which auto-creates the lead via `OfferLeadObserver`), closes via `sold_platform`, and asserts the confirm round-trip + a fresh dashboard mount both render without 500. Tests: **334 passing, 0 failures** (was 333; +1).

## UI Fixes — Homepage Search & Footer Plans Column

Two small **corrective** frontend fixes (no logic/route changes, no new lang keys):

**1. Duplicate homepage search bars (Bug 1):** The homepage showed two search inputs — one in the shared navbar (`layouts/frontend.blade.php`) and one in the hero (`frontend/home.blade.php`) — both plain GET forms to `route('listings.search')` with param `q` (independent, no shared state). The **navbar search is now hidden on the homepage only** by wrapping **both** navbar forms (desktop ~L57 and mobile-dropdown ~L134) in `@unless(request()->routeIs('home')) … @endunless`. The hero search becomes the sole homepage search; on all other pages (category, search-results, listing-detail) the navbar search stays visible unchanged.

**2. Empty "خطط النقاط" footer column (Bug 2):** `components/footer.blade.php` Col 4 had a header but a blank body — even though `$footerPlans = PointPlan::active()->orderBy('price')->get()` was **already queried at the top of the file but never used**, and the lang keys (`ui.footer.points_suffix`/`view_all_plans`/`view_point_plans`, AR/EN) already existed. Wired the column to loop over `$footerPlans`: each plan shows the **locale-aware name** (`name_ar` for `ar`, else `name_en` with `name_ar` fallback) + its point count (`number_format($plan->points)` + existing `ui.footer.points_suffix`), each linking to `route('pricing')` (no per-plan anchor — the pricing page has none), followed by a "view all plans" link (`ui.footer.view_all_plans`). Empty-state fallback shows the existing `view_point_plans` link. **No `name` accessor on `PointPlan`** (unlike Category/Location), hence the manual locale ternary. Tests: **322 passing, 0 failures** (unchanged — presentation-only).

## Footer Legal Pages — EN labels fix + global translation fallback

**Bug:** In EN locale the footer "Legal Pages" column rendered 6 file icons with **blank text labels**. Root cause was a **data gap** (not a code bug, and unrelated to the navbar/footer-plans edit): `LegalPageSeeder` wrote each `title` as a **plain Arabic string**, so Spatie `HasTranslations` stored `{"ar":"…"}` with **no `en` key**; `{{ $lp->title }}` (`components/footer.blade.php:148`) returned `''` in EN. (`CookiePolicySeeder` was unaffected — it already wrote explicit `['ar'=>…,'en'=>…]` arrays.)

**Discovery surprise:** the installed `spatie/laravel-translatable` (via `laravel-package-tools`) **does not read a `config/translatable.php` file at all** — its `Translatable` config object is a bare `new Translatable` (no config binding), and the trait only consults `config('app.fallback_locale')` (= `en`, also empty here) / `config('app.locale')`. So a published `config/translatable.php` alone would be a **no-op dead file**.

**Fix (Option 3 — both layers):**
1. **Global fallback safety net.** Created `config/translatable.php` with `use_fallback_locale => true` + `fallback_locale => 'ar'`, and — because the package ignores that file — **wired it into the Spatie `Translatable` singleton in `AppServiceProvider::boot()`** (`->fallback(fallbackLocale: config('translatable.fallback_locale'))`, guarded by `config('translatable.use_fallback_locale')`). Now any translatable attribute (any model, any page) missing the active locale falls back to `ar` instead of rendering blank. `HasTranslations::useFallbackLocale()` already defaults to `true`; the previous blank was because the resolved fallback was `app.fallback_locale=en` (also missing).
2. **Real EN titles.** Converted the 6 `LegalPageSeeder` titles from plain strings to explicit `['ar'=>…,'en'=>…]` arrays (Privacy Policy / Terms and Conditions / Acceptable Use Policy / About Us / Contact Us / Refund and Return Policy). Re-ran the seeder (idempotent via `updateOrCreate` on `slug`).

**Scope note:** page **`content`** is deliberately left Arabic-only for now (separate future decision); thanks to the new `fallback_locale => 'ar'`, EN visitors see the Arabic body (readable) instead of an empty page — same data-gap-with-fallback pattern as the B.4 city-names gap. `cookies-policy` is not seeded in the current DB (footer shows exactly the 6 `LegalPageSeeder` pages). Tests: **322 passing, 0 failures** (unchanged).

## Planned Next Steps

- **Sale-confirmation follow-ups:** (1) **Auto-reject pending offers on listing close** — when a listing is closed via `sold_platform`/`sold_external`/`canceled`, its still-`pending` `Offer`s become moot and should be auto-rejected (inside the same transaction) so they stop showing in the seller's "incoming offers" pointing at a removed listing (small standalone task, after this fix is visually verified). (2) Buyer notification (`SaleConfirmationRequested`) + buyer-side confirmation screen + rating form (the Step 2 deferred phase).
- **Hosting:** Deploy via **Laravel Forge** (planned)
- **UI/UX:** Hire a designer from **خمسات (Khamsat)** for frontend redesign
- **SMS Provider:** Integrate an Egyptian SMS gateway — candidates are **Connekio** or **Sentry SMS** — to replace the log driver for OTP delivery
