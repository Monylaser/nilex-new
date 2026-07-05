# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.
Last full audit: 2026-07-05 (every fact below was verified against the actual source files on that date).

---

## 1. Project Overview

**Nilex (نايلكس)** is an Egyptian classified-ads marketplace. Target audience: Egyptian buyers and sellers (Arabic-first, fully bilingual AR/EN public UI). It features OTP-based auth (email or phone channel), a point-based economy, tiered plan entitlements, AI-powered listing generation (Gemini), Paymob payment integration, self-service ad campaigns, and a dual sale-confirmation + seller-reviews system.

The platform has **14 categories**. **"خردة وخامات" (Scrap & Raw Materials)** is a deliberate competitive differentiator — no major Egyptian classifieds platform serves this niche. Source the category list from the `categories` table / seeder, never hardcode it.

### Tech Stack (verified from composer.lock / package.json)

| Layer | Technology |
|---|---|
| Language | PHP `^8.3` |
| Framework | Laravel 13 (locked v13.13.0) |
| Admin | Filament 5.4.5 (at `/admin`) + FilamentShield 4.2 (RBAC) + FilamentTranslatable 3.0 |
| Reactive UI | Livewire 4.3 (transitive via Filament), Alpine.js 3.15 |
| CSS / Build | Tailwind CSS 3 + Vite 8 |
| DB | MySQL (local DB `nilex_platform`); tests use in-memory SQLite |
| Cache | `CACHE_STORE=file` locally (Redis planned for production) |
| Queue | `QUEUE_CONNECTION=sync` locally; priority channels defined for prod: `sms-high`, `email-high`, `auth-critical` (`config/auth-security.php`) |
| Search | Laravel Scout 11 — `SCOUT_DRIVER=collection` locally (Meilisearch client installed for prod) |
| Media | Spatie MediaLibrary 11 (auto-optimized, watermarked conversions) |
| i18n | Spatie laravel-translatable 6 (fallback wired manually — see §9) |
| AI | Google Gemini (`gemini-1.5-flash`, `GEMINI_API_KEY`) |
| Payments | Paymob (`https://accept.paymob.com/api`, test mode) |
| Auth (social) | Socialite 5.27 — only Google fully configured |
| Testing | Pest 4.7 primary, PHPUnit 12.5 fallback |

### Local Environment

- **Laragon on Windows**, served at `http://127.0.0.1:8000`, `APP_ENV=local`.
- **Shell is PowerShell — NEVER chain commands with `&&`.** Use `;` or separate invocations.
- `MAIL_MAILER=log`, SMS simulated to `storage/logs/laravel.log` (`SmsService` logs in local env).
- **Timezone: `Africa/Cairo`** (`APP_TIMEZONE` env, default in `config/app.php`). Locale default `ar`, fallback `en`.

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

---

## 2. Project Structure

### Controllers (`app/Http/Controllers`)

| Controller | Responsibility |
|---|---|
| `Frontend/HomeController` | Homepage (`index`, queries `hero_top` campaigns directly), listing wizard create/store (+3 pts) and edit/update (category locked, status→pending, no pts), `aiGenerate()` (Gemini), `pricing()` (point plans page), `search()` |
| `Frontend/CategoryController` | Category page; increments `views_count`, paginates published listings (eager-loads `category,location,user`) |
| `Frontend/LegalPageController` | Renders `LegalPage` by slug at `/{slug}` |
| `Frontend/PaymentController` | Points checkout → Paymob iframe (requires `refund_policy_accepted`); user-facing success/failed callback views (credit happens in webhook) |
| `ListingController` | Detail page + view tracking + similar listings; `revealPhone()` (auth, 401 guests), `trackWhatsappClick()`, `makeOffer()` |
| `ProfileController` | Profile edit/update (phone/email managed via OTP flows, not here); `destroy()` **anonymizes** (see §9) |
| `PhoneVerificationController` | Profile phone add+verify: `send()` stages `pending_phone`, `verify()` commits + one-time **+20** (`phone_bonus_claimed_at`) |
| `EmailVerificationProfileController` | Mirror flow for email: `pending_email`, one-time **+20** (`email_bonus_claimed_at`) |
| `FavoriteController` | AJAX favorite toggle (guest → 401) + `/dashboard/favorites` page |
| `MessageController` | Creates `Message`, fires `NewMessage` |
| `PaymobController` / `PaymobWebhookController` | HMAC webhooks → `PaymobWebhookService` (points) / `PaymobAdWebhookService` (ads) |
| `AdSpacesController` | Public `/ads/pricing` page |
| `AdTrackingController` | Ad impression (JSON) + click (redirect) tracking |
| `Dashboard/SellerAdCampaignController` | Seller self-service ad CRUD + Paymob checkout + `retryPayment()` |
| `Auth/*` | Breeze set + `OtpController` (registration OTP show/verify/resend — **no points**), `RegisteredUserController` (device limit, +20 welcome, campaign referral), `SocialiteController` (OAuth; +20 first-time) |

### Models (`app/Models`) — key facts

- **`Listing`** — `SoftDeletes`, Scout `Searchable`, `InteractsWithMedia`, `LogsActivity`. Statuses: `pending / published / rejected / flagged`. `STRIKE_REASONS`: `inappropriate_content`, `scam_fraud`, `prohibited_items`. Media collection **`images`** (never `listings`); conversions `thumb` (300 webp), `card` (600×450 webp), `full_hd` (1920×1080 webp) — all watermarked (`public/images/watermark.png`, bottom-right, 40% opacity), all `nonQueued`; the original stays clean and is never linked publicly. `FEATURE_COSTS` — see §3.
- **`User`** — `points` (source of truth) + `points_balance` (mirror). Verification columns: `is_phone_verified`, `phone_verified_at`, `email_verified_at`, `pending_phone`, `pending_email`, `otp_channel`, `phone_bonus_claimed_at`, `email_bonus_claimed_at`, `anonymized_at`. Ratings: `ratings_avg` / `ratings_count` (denormalized by `ReviewObserver`). Also `plan_tier`, `plan_type`, `locale`, `strike_count`, `is_banned`, device-fingerprint fields.
- **`AdCampaign`** — `SoftDeletes`, media collection **`ad_image`**, conversions `desktop` 1200×400 / `tablet` 768×256 / `mobile` 390×130. Scopes: `active`, `displayable`, `paid`, `pending`, `approved`, `rejected`, `byPlacement`.
- **`Category` / `Location` / `CarBrand` / `CarModel`** — plain `name_ar` + `name_en` columns with a locale-aware `getNameAttribute()` accessor (NOT Spatie translatable). City-level `Location.name_en` = Arabic by seeder design (data gap).
- **`SaleConfirmation`** — state machine `pending → confirmed / canceled`; `confirmed` locked permanently; `canInitiateForListing()` = one confirmed sale per listing. `listing()` is `withTrashed()`.
- **`Review`** — 1–5 rating, unique per `sale_confirmation_id`; `listing()` `withTrashed()`.
- **`Offer` / `SellerLead`** — both have `withTrashed()` listing relations (closed listings are soft-deleted). `Offer.responded_at` powers seller response rate.
- **`LegalPage`** — Spatie `HasTranslations` (`title`, `content`).
- **`PointPlan`** — DB-driven plan catalog (see §3). `PlanEntitlement` / `UserEntitlement` / `UserEntitlementUsage` back the entitlement system.
- Others: `Favorite`, `PointTransaction`, `Transaction`, `PaymentAttempt`, `AdCampaignLog`, `AdCampaignAuditLog`, `Campaign` + `CampaignLink` (referral), `Message`, `AuditLog`, `SiteSetting`, `SeoTemplate`, `ListingView/PhoneClick/WhatsappClick`.

### Services (`app/Services`, `app/Auth/Services`)

| Service | Role |
|---|---|
| `PointService` | Atomic `credit()` / `deduct()` / `transfer()` with `lockForUpdate`; syncs `points` + `points_balance`; creates `PointTransaction`. Registered singleton. |
| `EntitlementService` | 13 feature flags (§3); cache `entitlements:user:{id}` TTL 300s; `assignFromPlan()` on `PointsPurchased` event; legacy users grandfathered to `home_promotion` only. |
| `AdCampaignService` | Placement queries + cache (§4), approve/reject campaigns, impression/click tracking via queued jobs. |
| `AdCampaignPaymentService` | Ad checkout price from `config/ad_pricing.php`; merchant order id `nilex-ad:{campaignId}:{attemptId}`. |
| `PaymobService` | Auth → Order → Payment Key against `https://accept.paymob.com/api` (`/auth/tokens`, `/ecommerce/orders`, `/acceptance/payment_keys`); reads `config('services.paymob.*')`; shared by points + ads checkouts. |
| `PaymobWebhookService` / `PaymobAdWebhookService` | HMAC-validated fulfillment: credit points + fire `PointsPurchased` / mark campaign paid. |
| `OtpService` (`app/Auth/Services`) | `issue()` (channel-aware: email→`SendOtpEmailJob`, phone→`SendOtpSmsJob`), `verify()` (registration gate — stamps `email_verified_at` OR `is_phone_verified`+`phone_verified_at` by channel, **no points**), `issueForPhone()`/`verifyPhone()` + `issueForEmail()`/`verifyEmail()` (profile flows, side-effect-free verify), `ensureNotLocked()` progressive throttle. |
| `SmsService` | Local env: logs OTP to `laravel.log` and returns true. Production API is a placeholder — **no real SMS gateway yet**. |
| `SellerListingAnalyticsService` | Seller dashboards, competitor pricing, `responseRate()` (min 5 offers), monthly-report stats. |
| `DeviceLimitService` | Max 3 accounts per device (`device_id` / `fingerprint_hash` / `ip_address`). |
| `MonthlyReportService` | PDF/HTML report → `storage/app/reports/{userId}/`. |
| `SellerLeadService` / `ListingLeadTrackingService` | Lead creation from phone/WhatsApp/offer events; view dedup 24h, click dedup 1h. |
| `GeminiService` | `gemini-1.5-flash` for wizard AI generation. |

### Jobs & Scheduler

Jobs: `TrackAdImpressionJob`, `TrackAdClickJob`, `InvalidateAdCampaignCacheJob`, `ProcessScheduledCampaigns` (CRM broadcasts), plus OTP delivery jobs in `app/Auth/Jobs` (`SendOtpSmsJob`, `SendOtpEmailJob`, `SendPhoneVerificationSmsJob`, `SendEmailVerificationOtpJob`) — all on `config('auth-security.queues.*')` channels with `afterCommit()`.

Schedule (`routes/console.php`) — exactly three entries:

```php
Schedule::job(ProcessScheduledCampaigns::class)->everyFiveMinutes()->withoutOverlapping();
Schedule::command('nilex:monthly-reports')->monthlyOn(1, '02:00')->withoutOverlapping();
Schedule::command('campaigns:expire')->hourly();
```

**There is NO daily-login points scheduler** — that reward is not implemented (see §8).

Artisan commands: `nilex:monthly-reports`, `campaigns:expire`, `users:backfill-verification {--execute}` (dry-run default), `listings:migrate-media-collection {--dry-run}`, `listings:regenerate-images {--listing=}`, `nilex:generate-seo-images {--force}`.

### Middleware (registered in `bootstrap/app.php`)

| Middleware | Alias / group | Purpose |
|---|---|---|
| `EnsureOtpIsVerified` | `otp.verified` | Redirects to `otp.notice` unless `is_phone_verified OR email_verified_at` |
| `EnsureUserIsNotBanned` | `not.banned`, appended to `web` | Logs out anonymized or banned users |
| `EnsureSelfServiceAdsEnabled` | `self_service_ads` | 404 when `config('features.self_service_ads')` is false |
| `SetLocale` | appended to `web` | `user->locale` → session → config; also `Carbon::setLocale()`. **NOT on Filament `/admin`** (Phase D deferred) |
| `TrackCampaign` | appended to `web` | Stores `?ref=` as session `campaign_code` for referral attribution |
| `PreventStorageCache` / `SecurityHeaders` | global append | No-cache headers / security headers |

CSRF exempt: `payments/callback`, `payment/webhook`, `webhooks/paymob/ads` (all HMAC-validated).

### Routes

- Public: `home`, `listings.search`, `pricing`, `category.show`, `listings.show`, reveal-phone / whatsapp-click / favorite POSTs (401 for guests), `ads.pricing`, ad impression/click, socialite `auth/{provider}` (`google|facebook|tiktok|instagram` allowed — only Google configured), `legal.show` catch-all (last), `language.switch`, Breeze guest routes.
- `['auth']`: OTP screens (`otp.notice/verify/resend`).
- `['auth','otp.verified']`: wizard create/edit, dashboard (Livewire `UserDashboard`), leads, business dashboard, profile + phone/email verification, points history, payment checkout, messages, offers, favorites, purchases (`dashboard.purchases`).
- `['auth','otp.verified','self_service_ads']`: `dashboard/ads/*` (routes in `routes/ads.php` + `routes/advertising.php`).

### Observers (registered in `AppServiceProvider`)

| Observer | Behavior |
|---|---|
| `PointTransactionObserver` | Sets `current_balance` on creating + flash message (balance itself is written by `PointService`) |
| `ListingObserver` | Auto-flags fraud keywords on create/update |
| `ListingPhoneClickObserver` / `ListingWhatsappClickObserver` / `OfferLeadObserver` | Create `SellerLead` rows |
| `ReviewObserver` | Recomputes `users.ratings_avg` / `ratings_count` (`updateQuietly`) |
| `AdCampaignObserver` | Auto-expires past `ends_at` on saving; queues cache invalidation on saved/deleted/restored |

### Notifications (all `ShouldQueue`, `database`+`mail` unless noted)

`ListingStatusNotification` (moderation), `SaleConfirmationRequested` (bilingual, locale-aware), `PointsPurchasedNotification`, `AdCampaignApproved/Rejected/PaymentReceivedNotification`, `MonthlyPerformanceReportNotification` (mail only), `CampaignNotification`.

---

## 3. Points & Rewards System (source of truth)

Any view displaying points MUST match these values — never hardcode different numbers.

### Earning (verified call sites)

| Action | Points | Where |
|---|---|---|
| Registration welcome (email/phone signup) | **+20** | `RegisteredUserController::store()` ← `config('pricing.registration_welcome_points', 20)` |
| First-time social signup | **+20** | `SocialiteController` (same config key) |
| Registration OTP confirmation | **0** | `OtpController::verify()` grants nothing |
| Profile phone verification (once per lifetime) | **+20** | `PhoneVerificationController::verify()`, guarded by `phone_bonus_claimed_at` |
| Profile email verification (once per lifetime) | **+20** | `EmailVerificationProfileController::verify()`, guarded by `email_bonus_claimed_at` |
| Listing created | **+3** | `HomeController::store()` |
| Referral signup | `CampaignLink.points_reward` (DB-driven) | `RegisteredUserController` — ⚠️ bypasses `PointService`: increments `points_balance` only + manual `PointTransaction` (known inconsistency, see §8) |
| Points purchase | plan's `points` | `PaymobWebhookService` (webhook, not the callback controller) |
| Admin adjustment | admin-entered ± | `UserResource::adjustPointsAction()` via `PointService`, description `'تعديل إداري: '…` |
| Daily login | **NOT IMPLEMENTED** | no scheduler, no credit logic exists |

### Featuring costs — `Listing::FEATURE_COSTS`

| Duration | Cost |
|---|---|
| 1 day | **40** pts |
| 3 days | **90** pts |
| 7 days | **170** pts |
| 14 days | **300** pts |

- `Listing::featureCost(int $days)` → `null` for unsupported durations (safe for UI).
- `Listing::featureCostStrict(int $days)` → throws for unsupported (programmatic flows).
- `$listing->featureWithPoints(int $days)` — checks `featured_listings_limit` + `monthly_boost_limit` entitlements + balance; ⚠️ debits via `$user->decrement('points')` + manual `PointTransaction` (type `feature_listing`), not `PointService::deduct()`. Extends `featured_until` when already featured. Best-effort after creation (silent failure never blocks the listing).
- The pricing page (`frontend/pricing.blade.php`) mirrors these values and must be kept in sync.

### Purchase Plans — DB-driven (`point_plans` table via `PointPlanSeeder`, NOT config)

| Plan | Points | Price (EGP) | tier_key | Type |
|---|---|---|---|---|
| البداية / Starter | 100 | 49 | `starter` | individual |
| النمو / Growth | **300** | 99 | `growth` | individual |
| البائع المحترف / Pro Seller | **850** | 249 | `pro_seller` | individual |
| الشركات / Business | **2500** | 499 | `business` | company |

`config/pricing.php` contains only `registration_welcome_points`, `plan_column_keys`, and a **UI-only** `feature_matrix` for the pricing-page comparison (some rows marked `coming_soon` / `admin_only`). Runtime entitlements come from `PlanEntitlementSeeder`.

### Entitlements per tier (`PlanEntitlementSeeder` — runtime truth)

| Feature flag | Starter | Growth | Pro Seller | Business |
|---|---|---|---|---|
| `featured_listings_limit` | 1 | 3 | 5 | 10 |
| `monthly_boost_limit` | 2 | 5 | 10 | 20 |
| `search_priority` | ✗ | ✓ | ✓ | ✓ |
| `home_promotion` | ✓ | ✓ | ✓ | ✓ |
| `business_badge` | ✗ | ✗ | ✗ | ✓ |
| `priority_support` | ✗ | ✗ | ✗ | ✓ |
| `analytics_access` | ✗ | ✓ | ✓ | ✓ |
| `analytics_charts` | ✗ | ✗ | ✓ | ✓ |
| `phone_clicks_access` | ✗ | ✗ | ✓ | ✓ |
| `whatsapp_clicks_access` | ✗ | ✓ | ✓ | ✓ |
| `event_views_access` | ✗ | ✓ | ✓ | ✓ |
| `business_dashboard` | ✗ | ✗ | ✗ | ✓ |
| `monthly_reports` | ✗ | ✗ | ✓ | ✓ |

Check access via `EntitlementService::canUseFeature()` / `hasFeature()`. Cache: `entitlements:user:{id}`, 300s, cleared by `assignFromPlan()` / `recordUsage()`. Users with zero `user_entitlements` rows are grandfathered to `home_promotion` only.

---

## 4. Ad Campaign System

Two parallel systems share the `AdCampaign` model:
1. **Admin-managed** — created in Filament (`AdCampaignResource`); `seller_id`/`payment_status` null → treated as paid.
2. **Self-service seller-paid** — feature-flagged `SELF_SERVICE_ADS=true` (`config/features.php`); `SellerAdCampaignController` + Paymob.

### Placements (`config/ad_pricing.php` — 7 keys, prices in EGP for 7/15/30/60 days)

| Placement | Prices (7/15/30/60d) | Renders where |
|---|---|---|
| `hero_top` (1200×400) | 500/900/1500/2500 | Homepage — `HomeController::index()` queries directly; Alpine **carousel** in `home.blade.php` with per-campaign slide duration (NOT `<x-ad-banner>`) |
| `home_feed` (768×256) | 400/750/1200/2000 | `home.blade.php` — `<x-ad-banner>` every 8 listings, gated `@if(config('features.self_service_ads'))` |
| `category_page` (768×256) | 350/650/1000/1700 | `category.blade.php` — `<x-ad-banner :category-id>` |
| `login_page` (768×256) | 250/450/750/1200 | `auth/login.blade.php` |
| `popup` (768×512) | 600/1100/1800/3000 | `components/ad-popup.blade.php` via `<x-ad-popup />` in `layouts/frontend.blade.php` |
| `listing_detail` | 300/550/900/1500 | **Config/Filament only — no view renders it yet** |
| `search_results` | 300/550/900/1500 | **Config/Filament only — no view renders it yet** |

Seller self-service allows only the first 5 placements.

### Approval workflow

- Fields: `status` (`draft/scheduled/active/paused/expired`), `approval_status` (`pending/approved/rejected`), `payment_status` (`pending/paid/failed/refunded`, null for admin campaigns).
- Seller flow: `store()` creates `draft`+`pending`+`pending` → image upload → Paymob checkout (`AdCampaignPaymentService`) → webhook marks `paid` → admin approves in Filament → `AdCampaignService::approveCampaign()` sets `approval_status=approved`, `status=active`, `starts_at=now()`, `ends_at=now()+duration_days`, audit-logs, notifies seller. Reject stores `rejected_reason` + notifies.
- Expiry: `AdCampaignObserver::saving()` auto-expires past `ends_at`; `campaigns:expire` runs hourly.

### Cache behavior (`AdCampaignService`)

- TTL **300s**; keys `ad_campaign_{placement}`, category pages `ad_campaign_category_page_{categoryId|'all'}`.
- **Local env bypasses the cache entirely** — `getForPlacement()` hits the DB directly when `app()->environment('local')`, so no manual clearing is needed during development.
- Invalidation: `AdCampaignObserver` → `InvalidateAdCampaignCacheJob` → `flushCacheKeys()` on any save/delete/restore. Manual: `AdCampaignService::clearPlacementCache()`.
- Impression/click dedup keys `ad_imp_{id}_{ip}_{uaHash}` / `ad_clk_…`, TTL 60 min; tracking dispatched to queue.
- `TrackCampaign` middleware handles `?ref=` attribution.

### Popup component (`components/ad-popup.blade.php`)

Frequency-capped via `localStorage` key `popup_last_seen` (24h). 5-second countdown before X/skip become active; closing writes the timestamp. Image links through `route('ads.click')` when `target_url` set (target_url is optional). No impression tracker (unlike `<x-ad-banner>`).

---

## 5. Visual Identity (navy/teal — Lovable-final values)

Token name `nilex` kept, value remapped to navy. Filament `/admin` is fully excluded from the re-skin. Semantic colors are protected: `green/emerald` = success/verified, `red/rose` = error, `amber` = warning — never repurpose as brand.

### Palette (`tailwind.config.js`)

| Token | Hex |
|---|---|
| `nilex` DEFAULT / dark / light | `#11407A` / `#0B2F5C` / `#3D8BD4` (full 50→900 scale) |
| `nilex-teal` DEFAULT / deep / light | `#14A5A8` / `#0D7377` / `#14BDBC` |
| `nilex-orange` DEFAULT / light | `#E8431D` (prices) / `#FF9A4D` |
| `nilex-ink` | `#11203D` (titles/headings) |
| `nilex-bg` | `#F4F6FB` (page background) |
| shadows | `shadow-nilex` `0 4px 14px rgba(17,64,122,.22)`, `shadow-nilex-lg` |

### Key CSS classes (`resources/css/app.css`)

- **`.btn-nilex-primary`** — the primary CTA: gradient `linear-gradient(135deg, #0C7F82, #12B5B8)`, dual teal glow, hover `brightness(1.05)` + lighter gradient, `active:scale(0.98)`, focus `2px solid #14BDBC` offset 3px. Reusable via `<x-primary-button>`.
- Listing-card utilities (`@layer utilities`): `.card-listing` (14px radius, hover lift, teal-tinted border), `.card-image` (4/3 navy gradient placeholder `#3D8BD4→#11407A`), `.badge-new` (emerald gradient), `.badge-featured` (amber gradient), `.fav-icon-btn` (glass circle, orange heart hover), `.pill-category` (`#E6F1FB` bg, navy text), `.trust-badge` (frosted, teal text), `.price-tag` (`#E8431D`, fw-800).
- `.card-photo` is defined inline in `home.blade.php` (homepage grid photos), not in `app.css`.

### Fonts

**Cairo** only (Google Fonts `<link>` in all three layouts; `fontFamily.sans` in Tailwind config). No Tajawal.

### Re-skin status

Applied: homepage + shared chrome, listing cards, category/search/detail CTAs, both pricing pages' CTAs, trust card, footer, dashboard/profile/ads/Livewire CTAs, the **listing wizard** (teal chrome), and the **auth pages + guest layout** (all committed). Residual legacy green `#1D9E75` accents remain in: `components/points-badge.blade.php`, Chart.js dataset colors (user/business dashboards), `pages/show.blade.php` prose links, footer accent bars, and assorted breadcrumb/icon accents on category/search/detail/pricing pages.

---

## 6. Fixed Development Rules

1. **PowerShell: never `&&`** — chain with `;` or run commands separately.
2. **AR/EN translation is mandatory for every new user-facing string from the first line** — public + authenticated area. Lang files: `lang/{ar,en}/{ui,wizard,listing,adspaces,server,auth,validation}.php` + root `ar.json`/`en.json`. Exception: `app/Filament/*` (admin) is Arabic-only by deliberate decision (Phase D deferred).
3. **Never delete code without explicit approval** — deprecate/flag instead, and ask.
4. **Discovery before implementation** — read the actual code/DB first; never assume from docs or memory. For risky data work, dry-run first (see `users:backfill-verification` pattern: read-only by default, `--execute` to write).
5. **`php artisan test` after every change** — suite must stay green (currently **428 passing, 0 failures**). Tests use in-memory SQLite, sync queue, `SCOUT_DRIVER=collection` (see `phpunit.xml`).
6. **Commit after each approved phase/step** — small, labeled commits.
7. **Western/Latin digits (1,2,3) everywhere, all locales** — never Arabic-Indic numerals in UI strings.
8. **Persisted `PointTransaction.description` strings stay Arabic** (written once at credit time — the documented permanent exception to rule 2).
9. **Media collections:** listings → `images`, campaigns → `ad_image`. Never read/write the legacy `listings` collection.
10. Sensitive flows never trust the client: re-verify ownership server-side (`where('user_id', Auth::id())`), IDOR-check ids against real DB relations, wrap multi-step writes in `DB::transaction`.

---

## 7. Completed Features

**Auth & accounts:** OTP registration gate (4-digit, 5-min expiry, progressive throttle) with **email/phone channel separation** (§9); device fingerprinting (3 accounts/device); Google social login; ban system; account **anonymization** on delete (row survives, PII wiped, `anonymized_at`, Socialite re-entry blocked, middleware kills stale sessions); profile page with staged phone (`pending_phone`) and email (`pending_email`) OTP verification flows, each with a one-time +20 bonus; password reset; roles `super_admin/admin/moderator/user` via FilamentShield.

**Listings:** multi-step Alpine wizard (create + edit share one view; edit locks category, forces re-moderation, preserves slug, no points); cars category (25 brands / ~209 models, dependent dropdowns, year/condition/mileage/color) and real-estate (property/listing type, rooms, floor with free-text "other", finishing, area, compound; no top-level condition); dynamic `custom_fields_schema` per category; AI generation via Gemini; watermarked image conversions; moderation queue (`/admin/moderation`) with unified approve/reject (`ListingModeration` support class: audit log + owner notification + auto-strike), 3-strike auto-ban; soft deletes; closing flow (sold-on-platform → buyer selection / sold-external / canceled); listing detail page with lightbox, share button, similar listings, schema.org JSON-LD; favorites; search (Scout) + category pages.

**Sales & trust:** dual sale confirmation (`SaleConfirmation` state machine) → buyer notification → `/dashboard/purchases` confirm screen → 1–5 star review with comment; denormalized `users.ratings_avg/count` via `ReviewObserver`; `<x-rating-stars>` partial-fill component (trust card + dashboard); seller response rate (min 5 offers); seller leads (phone reveal / WhatsApp / offer) with lead detail pages.

**Economy:** point economy (§3) with atomic `PointService`; plans + 13-flag entitlements; Paymob points checkout (test mode, verified end-to-end) with refund-policy consent; admin points adjustment action (credit/debit + reason, graceful insufficient-balance).

**Ads:** admin-managed + self-service campaigns (§4); hero carousel with per-campaign duration; popup modal with countdown + 24h cap; public ad-spaces pricing page; impression/click tracking + attribution.

**Analytics:** per-listing views/phone/WhatsApp clicks; entitlement-gated seller analytics + charts; Business dashboard; monthly PDF reports (scheduler); admin widgets (category performance excludes soft-deleted).

**i18n:** entire public frontend + authenticated area bilingual AR/EN (localization Phases A–C complete); locale persisted on `users.locale`; Carbon locale synced; Spatie translatable fallback → `ar`; legal pages (7 seeded slugs) with translated titles.

**Admin (Filament, Arabic-only):** listing resource + moderation queue with infolist review screen (full description + image gallery); fixed `ListingPolicy` (moderators: view/approve/reject only); user management (ban, strikes, points adjustment); ad campaign approval; audit logs; TrashedFilter + restore (force-delete = super_admin only).

**Platform/UX fixes shipped:** OTP boxes LTR order, delete-modal Alpine scope, profile icon overlap, phone-verified badge semantics, footer plans column, duplicate homepage search, admin image-collection unification (`listings:migrate-media-collection`), `email-verify` +20 flow, wizard location prefill.

---

## 8. Deferred / Incomplete

| Item | Status |
|---|---|
| **Phase D — Filament admin translation** | Deferred in full by business decision (staff are Arabic speakers). Filament ships its own AR chrome; `SetLocale` is NOT on `/admin`. `smart-ad-creator` keys pre-translated but invisible until wired. |
| **`store()` image validation gap** | ⚠️ SECURITY: `update()` validates `images.*` (`image|mimes:jpeg,png,webp|max:5120`, `max:10`) but **`store()` still has NO server-side image rules** — must get the exact same rules + `wizard.server.*` messages. |
| **Daily login +1 points** | Not implemented (no scheduler/logic). Do not advertise it in any view. |
| **Points for positive review** | Deliberately removed from earn-guide; needs product decisions (definition of "positive", anti-abuse, who's rewarded) before wiring in `ReviewObserver`. |
| **Referral credit inconsistency** | `RegisteredUserController` referral bypasses `PointService` (increments `points_balance` only, not `points`) — should be unified through `PointService::credit()`. |
| `listing_detail` / `search_results` placements | Priced in config + selectable in Filament but no view renders them. |
| Seller-side `cancelBySeller()` UI | Model method exists; no UI action yet. |
| Auto-reject pending offers on listing close | Pending offers on closed listings stay `pending`. |
| Notifications: mark-as-read / "view all" page | Bell dropdown only. |
| Facebook / Instagram / TikTok login | Routes allow them; `config/services.php` has no facebook/instagram blocks and TikTok has no env keys — only Google works. |
| `lang/en/types.php` | Missing (`lang/ar/types.php` exists AR-only). |
| Pricing feature-matrix rows | `basic_ctr`, `business_dashboard`, `lead_funnel`, `advanced_ctr`, `monthly_reports` marked `coming_soon` in `config/pricing.php` (UI matrix). |
| `PAYMOB_IFRAME_ID` | Referenced by `config/services.php` but absent from `.env` (needs real ID from Paymob dashboard). |
| Legal page `content` EN | Arabic-only by decision; EN visitors see Arabic body via the `ar` fallback. City `Location.name_en` also Arabic (data gap). |
| Re-skin residual green | See §5 — points-badge, Chart.js colors, prose links, footer accents. |

---

## 9. Sensitive Technical Architecture

### OTP channel separation (email vs phone)

- `users.otp_channel` records the active OTP's channel. Registration `OtpService::verify()` stamps **`email_verified_at`** (email channel) or **`is_phone_verified` + `phone_verified_at`** (phone channel) — never cross-stamps. Legacy in-flight OTPs infer channel from `phone ? 'phone' : 'email'`.
- **All four gates** accept either channel (`is_phone_verified || email_verified_at`): `EnsureOtpIsVerified`, `OtpController::show()`, `RedirectIfAuthenticated`, `AuthenticatedSessionController::store()`.
- Profile flows stage new contacts in `pending_phone` / `pending_email` (live values never overwritten until OTP confirms; TOCTOU uniqueness re-check on commit). Bonuses guarded by permanent `phone_bonus_claimed_at` / `email_bonus_claimed_at` (once per lifetime, never re-granted on number/email change).
- `email_verified_at` writes need `forceFill()` (not in `$fillable`).
- `users:backfill-verification` fixed historical mis-flagged rows (email users with `is_phone_verified=true`).

### Points double-column

`users.points` is the **only real balance**; `points_balance` is a mirror synced by `PointService::record()`. Never write either column directly — always go through `PointService` (the admin form used to write `points_balance` raw; that was a silent no-op bug, fixed).

### Spatie MediaLibrary collections

- `Listing` → **`images`** (read with `getMedia('images')` / `getFirstMediaUrl('images')`); conversions `thumb`/`card`/`full_hd`, all watermarked + `nonQueued`; original kept clean and never rendered publicly. Legacy `listings` collection is dead (`listings:migrate-media-collection` moved it).
- `AdCampaign` → **`ad_image`**; conversions `desktop`/`tablet`/`mobile`.
- `Category` icons are media-library-backed.
- Test-helper gotcha: fake media rows must set BOTH `disk` and `conversions_disk` to `'public'` or `getUrl('card')` throws.

### Soft-delete couplings

`Listing` uses `SoftDeletes`; closed/deleted listings survive for sale/review history. Relations that must stay `withTrashed()`: `SaleConfirmation::listing()`, `Review::listing()`, `Offer::listing()`, `SellerLead::listing()`. Event-based analytics joins need explicit `whereNull('listings.deleted_at')` (global scope doesn't apply from the event side). `restrictOnDelete` on sale/review FKs + user **anonymization** (not deletion) protect history: `ProfileController::destroy()` wipes PII, randomizes password, nulls `provider_id` (blocks Socialite re-entry), soft-deletes listings, stamps `anonymized_at` — row and ID survive.

### AdCampaignService cache

§4 — remember: **local env always bypasses**; production invalidation is observer→job driven; there is no config flag to disable it.

### Timezone & locale

`Africa/Cairo` app-wide (`APP_TIMEZONE`). Default locale `ar`, Laravel fallback `en`, but Spatie translatable fallback is **`ar`** — wired manually in `AppServiceProvider::boot()` because the installed package ignores `config/translatable.php`.

### Misc gotchas

- Blade: never put the literal token `@php` inside a `{{-- --}}` comment (raw-block extraction runs before comment stripping and swallows content).
- JSON-LD on the detail page is rendered with escaped slashes (no `JSON_UNESCAPED_SLASHES`) to prevent `</script>` breakout XSS.
- Livewire modals: prefer `@if($flag)` server-rendered wrappers over `x-show`+`@entangle` (proxy-truthiness/morph bugs), and class-driven pseudo-radios over real inputs (Idiomorph preserves live `.checked`).
- Paymob endpoints: `/auth/tokens` (not `/auth/login`), `/acceptance/payment_keys`; always via `config('services.paymob.*')`, never raw `env()`.
- OTP flex container needs explicit `dir="ltr"` (RTL flex reverses visual order).

---

## 10. Launch Notes (as of July 2026)

### Done
- **Tests: 428 passing, 0 failures** (Pest; in-memory SQLite).
- Entire public + authenticated frontend bilingual AR/EN; RTL/LTR correct.
- Paymob integration verified end-to-end **in test mode** (points + ad checkouts).
- Moderation pipeline complete with notifications + audit trail.
- Visual identity (navy/teal) applied across frontend incl. wizard + auth pages.
- Image watermarking + backfill command; SEO JSON-LD; OG images command.

### Not done yet (launch blockers / decisions)
| Item | Current state |
|---|---|
| **SMS gateway** | `SmsService` logs OTPs to `storage/logs/laravel.log` locally; production API URL is a placeholder. Candidates: Connekio or Sentry SMS (Egyptian gateways). No `SMS_*` keys in `.env`. |
| **Paymob live mode** | Test credentials only; `PAYMOB_IFRAME_ID` missing from `.env`. |
| **Hosting / deployment** | Not deployed anywhere — local Laragon is the only environment. Plan: Laravel Forge. |
| **Production infra switches** | Queue `sync`→Redis (priority channels already defined), cache `file`→Redis, Scout `collection`→Meilisearch, `MAIL_MAILER` `log`→real SMTP. |
| **UI/UX designer pass** | Planned hire via خمسات (Khamsat). |
| Store() image validation | Must ship before launch (see §8 — security). |
