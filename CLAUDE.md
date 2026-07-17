# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.
Last full audit: **2026-07-16** (pre-launch comprehensive audit; prior final launch-prep 2026-07-10).

### Pre-Launch Security Fixes — 2026-07-16

Critical findings from the 2026-07-16 audit, fixed before launch:

| Fix | Detail |
|---|---|
| **JSON-LD stored XSS** | `listings/show.blade.php` now embeds schema via Blade `@json($ld)` (HEX-escaped tags). `search-results.blade.php` geo i18n payload uses the same HEX flags (`JSON_HEX_TAG\|APOS\|AMP\|QUOT`). Tests: `ListingJsonLdXssTest`. |
| **Login email hardcode removed** | Deleted `admin@gmail.com` → `/admin` shortcut in `AuthenticatedSessionController`. OTP gate runs first; then `hasAnyRole(['super_admin','admin','moderator'])` → `/admin`. Regular users (including that email) go to dashboard. Tests: `AdminLoginRedirectTest`. |
| **`.gitignore` encoding** | `docs/architecture/.docgen/node_modules/` entry rewritten as clean UTF-8 (prior commit had UTF-16 nulls so ignore never matched). |
| **No `env()` outside `config/`** | Removed all `env()` fallbacks from `app/` (Paymob HMAC/iframe, SmsService, SmartAdCreator Gemini). SMS credentials live under `config/services.php` → `services.sms.*`. **Permanent rule:** never call `env()` inside `app/` — always `config()` with the key defined in a config file. |

No migrations in this pass. **Suite after fixes: 457 passed, 0 failures** (1401 assertions; +4 vs prior 453 baseline).

### Final Launch Prep — 2026-07-10

Comprehensive pre-deployment review pass (no deletions). **453 tests passing, 0 failures** (1387 assertions) at that date.



| Area | Outcome |
|---|---|
| **Re-skin** | **Fully completed** — zero `#1D9E75` / `#085041` in application code (`*.php`, `*.blade.php`, `*.css`, `*.js`). Legacy green remains only in archived `docs/` and `reports/` HTML (not served). |
| **i18n** | Footer social `aria-label`s, ad-spaces link, logo alt, WhatsApp prefill message, Socialite default name, and Paymob 503 message moved to AR/EN lang keys. Cookie consent + hero carousel already bilingual. |
| **Security** | `makeOffer()` rate-limited (5/min per user, mirrors `MessageController`). No `env()` in controllers. `points_balance` / `points` / `is_banned` guarded on `User`; `Listing` uses `$guarded` for `user_id`/`status`. |
| **Performance** | Extended `media` eager-load to listing detail + similar listings, seller dashboard listings, homepage category icons (+ wizard categories). P3/P5/P6/P7 deferred (documented §12). |
| **Deferred (not deleted)** | **L2** Socialite referral — comment in `SocialiteController::callback()`. **L6** `listing-details.blade.php` — header notes active route is `listings/show.blade.php`. |

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
| `Frontend/HomeController` | Homepage (`index`, queries `hero_top` campaigns directly), listing wizard create/store (+3 pts) and edit/update (category locked, status→pending, no pts), `aiGenerate()` (Gemini), `pricing()` (point plans page), `search()` with `ListingSort` + geo + search-priority boost |
| `Frontend/CategoryController` | Category page; increments `views_count`, paginates published listings with `ListingSort` + search-priority boost (default sort), eager-loads `category,location,user` |
| `Frontend/LegalPageController` | Renders `LegalPage` by slug at `/{slug}` |
| `Frontend/PaymentController` | Points checkout → Paymob iframe (requires `refund_policy_accepted`); user-facing success/failed callback views (credit happens in webhook) |
| `ListingController` | Detail page + view tracking + similar listings; `revealPhone()` (auth, 401 guests, 20/hr throttle), `trackWhatsappClick()` (auth, 401 guests, published-only), `makeOffer()` |
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

### Support (`app/Support`)

| Class | Role |
|---|---|
| `ListingSort` | Shared sort options: `latest` / `oldest` / `price_asc` / `price_desc`; `fromRequest()` defaults invalid to `latest` (defensive — HTTP controllers validate with `Rule::in()` first); `isValid()` helper; `apply()` with tie-breaker `id`. Used by `CategoryController` and `HomeController::search()`. UI: `partials/listing-sort-select.blade.php`. |

### Models (`app/Models`) — key facts

- **`Listing`** — `SoftDeletes`, Scout `Searchable`, `InteractsWithMedia`, `LogsActivity`. Statuses: `pending / published / rejected / flagged`. `STRIKE_REASONS`: `inappropriate_content`, `scam_fraud`, `prohibited_items`. Media collection **`images`** (never `listings`); conversions `thumb` (300 webp), `card` (600×450 webp), `full_hd` (1920×1080 webp) — all watermarked (`public/images/watermark.png`, bottom-right, 40% opacity), all `nonQueued`; the original stays clean and is never linked publicly. `FEATURE_COSTS` — see §3.
- **`User`** — `points` (source of truth) + `points_balance` (mirror; **not** in `$fillable` — synced only by `PointService`). Verification columns: `is_phone_verified`, `phone_verified_at`, `email_verified_at`, `pending_phone`, `pending_email`, `otp_channel`, `phone_bonus_claimed_at`, `email_bonus_claimed_at`, `anonymized_at`. `$hidden` includes phone + device fields + 2FA placeholders. Ratings: `ratings_avg` / `ratings_count` (denormalized by `ReviewObserver`). Also `plan_tier`, `plan_type`, `locale`, `strike_count`, `is_banned`, device-fingerprint fields.
- **`AdCampaign`** — `SoftDeletes`, media collection **`ad_image`**, conversions `desktop` 1200×400 / `tablet` 768×256 / `mobile` 390×130. Scopes: `active`, `displayable`, `paid`, `pending`, `approved`, `rejected`, `byPlacement`. `display_duration_seconds` drives hero carousel slide timing.
- **`Category` / `Location` / `CarBrand` / `CarModel`** — plain `name_ar` + `name_en` columns with a locale-aware `getNameAttribute()` accessor (NOT Spatie translatable). City-level `Location.name_en` = Arabic by seeder design (data gap). `Location::getCachedAll()` exists but wizard still hits DB directly.
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
| `EntitlementService` | 13 feature flags (§3); cache `entitlements:user:{id}` TTL 300s; `assignFromPlan()` on `PointsPurchased` event; legacy users grandfathered to `home_promotion` only. `isLegacyGrandfathered()` hits DB on every `hasFeature()` call (not cached). |
| `AdCampaignService` | Placement queries + cache (§4), approve/reject campaigns, impression/click tracking via queued jobs. |
| `AdCampaignPaymentService` | Ad checkout price from `config/ad_pricing.php`; merchant order id `nilex-ad:{campaignId}:{attemptId}`. |
| `PaymobService` | Auth → Order → Payment Key against `https://accept.paymob.com/api` (`/auth/tokens`, `/ecommerce/orders`, `/acceptance/payment_keys`); reads `config('services.paymob.*')`; shared by points + ads checkouts. |
| `PaymobWebhookService` / `PaymobAdWebhookService` | HMAC-validated fulfillment: credit points + fire `PointsPurchased` / mark campaign paid. |
| `OtpService` (`app/Auth/Services`) | `issue()` (channel-aware: email→`SendOtpEmailJob`, phone→`SendOtpSmsJob`), `verify()` (registration gate — stamps `email_verified_at` OR `is_phone_verified`+`phone_verified_at` by channel, **no points**), `issueForPhone()`/`verifyPhone()` + `issueForEmail()`/`verifyEmail()` (profile flows, side-effect-free verify), `ensureNotLocked()` progressive throttle. |
| `SmsService` | Local env: logs OTP to `laravel.log` and returns true. Production API is a placeholder — **no real SMS gateway yet**. |
| `SellerListingAnalyticsService` | Seller dashboards, competitor pricing, `responseRate()` (min 5 offers), monthly-report stats. ⚠️ `getCompetitorPriceComparison()` N+1 AVG queries; `getMonthlyPerformance()` loads all view rows into PHP. |
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
| `EnsureOtpIsVerified` | `otp.verified` | Redirects to `otp.notice` unless `is_phone_verified OR email_verified_at`; sets `session('error', …)` on redirect |
| `EnsureUserIsNotBanned` | `not.banned`, appended to `web` | Logs out anonymized or banned users |
| `EnsureSelfServiceAdsEnabled` | `self_service_ads` | 404 when `config('features.self_service_ads')` is false |
| `SetLocale` | appended to `web` | `user->locale` → session → config; also `Carbon::setLocale()`. **NOT on Filament `/admin`** (Phase D deferred) |
| `TrackCampaign` | appended to `web` | Stores `?ref=` as session `campaign_code` for referral attribution |
| `PreventStorageCache` / `SecurityHeaders` | global append | No-cache headers / security headers; auth pages get `Cache-Control: no-store` |

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
| Referral signup | `CampaignLink.points_reward` (DB-driven) | `RegisteredUserController` — via `PointService::credit()` inside `DB::transaction` + `CampaignLink::lockForUpdate()` (fixed 2026-07-09) |
| Points purchase | plan's `points` | `PaymobWebhookService` (webhook, not the callback controller) |
| Admin adjustment | admin-entered ± | `UserResource::adjustPointsAction()` via `PointService`, description `'تعديل إداري: '…` |
| Daily login | **NOT IMPLEMENTED** | no scheduler, no credit logic exists — **do not advertise** |

### Featuring costs — `Listing::FEATURE_COSTS`

| Duration | Cost |
|---|---|
| 1 day | **40** pts |
| 3 days | **90** pts |
| 7 days | **170** pts |
| 14 days | **300** pts |

- `Listing::featureCost(int $days)` → `null` for unsupported durations (safe for UI).
- `Listing::featureCostStrict(int $days)` → throws for unsupported (programmatic flows).
- `$listing->featureWithPoints(int $days)` — checks entitlements + balance inside `DB::transaction` with `User::lockForUpdate()`; debits via `PointService::deduct()`; `recordUsage()` locks entitlement + usage rows under transaction (fixed 2026-07-09).
- Wizard reads costs from `@json(\App\Models\Listing::FEATURE_COSTS)` — single source. Pricing page Section 6 reads the same constant dynamically (fixed 2026-07-09).

### Purchase Plans — DB-driven (`point_plans` table via `PointPlanSeeder`, NOT config)

| Plan | Points | Price (EGP) | tier_key | Type |
|---|---|---|---|---|
| البداية / Starter | 100 | 49 | `starter` | individual |
| النمو / Growth | **300** | 99 | `growth` | individual |
| البائع المحترف / Pro Seller | **850** | 249 | `pro_seller` | individual |
| الشركات / Business | **2500** | 499 | `business` | company |

Rebalanced July 2026 (`a266ff5`): welcome/verify bonuses raised 15→20; feature costs raised; plan credits adjusted (Growth 300, Pro 850, Business 2500).

`config/pricing.php` contains only `registration_welcome_points`, `plan_column_keys`, and a **UI-only** `feature_matrix` (some rows marked `coming_soon` / `admin_only`; comparison matrix removed from pricing page). Runtime entitlements come from `PlanEntitlementSeeder`.

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
| `monthly_reports` | ✗ | ✗ | ✗ | ✓ |

Check access via `EntitlementService::canUseFeature()` / `hasFeature()`. Cache: `entitlements:user:{id}`, 300s, cleared by `assignFromPlan()` / `recordUsage()`. Users with zero `user_entitlements` rows are grandfathered to `home_promotion` only.

---

## 4. Ad Campaign System

Two parallel systems share the `AdCampaign` model:
1. **Admin-managed** — created in Filament (`AdCampaignResource`); `seller_id`/`payment_status` null → treated as paid.
2. **Self-service seller-paid** — feature-flagged `SELF_SERVICE_ADS=true` (`config/features.php`); `SellerAdCampaignController` + Paymob.

### Placements (`config/ad_pricing.php` — 7 keys, prices in EGP for 7/15/30/60 days)

| Placement | Prices (7/15/30/60d) | Renders where |
|---|---|---|
| `hero_top` (1200×400) | 500/900/1500/2500 | Homepage — `HomeController::index()` queries directly; Alpine **carousel** in `home.blade.php` with per-campaign `display_duration_seconds` (NOT `<x-ad-banner>`) |
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
- Impression/click dedup keys `ad_imp_{id}_{ip}_{uaHash}` / `ad_clk_…`, TTL 60 min; tracking dispatched to queue. Dedup uses `Cache::has()` then `Cache::put()` (non-atomic race).
- `TrackCampaign` middleware handles `?ref=` attribution.
- **Hero path bypasses cache** — `HomeController::index()` queries `AdCampaign` directly, not `AdCampaignService::getForPlacement()`.

### Popup component (`components/ad-popup.blade.php`)

Centred modal card (`max-w-8xl`, `95vh`). Frequency-capped via `localStorage` key `popup_last_seen` (24h). 5-second countdown before X/skip become active; closing writes the timestamp. Image links through `route('ads.click')` when `target_url` set (`target_url` optional — popup renders without it). **No impression tracker** (unlike `<x-ad-banner>`). Skip/countdown/close strings via `ui.ad_popup.*` (bilingual AR/EN). Covered by `AdPopupTest` (11+ cases).

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

**Fully completed (2026-07-10).** Navy/teal (`nilex`, `nilex-teal`) applied across: homepage + shared chrome, listing cards, category/search/detail CTAs, both pricing pages' CTAs, trust card, footer, dashboard/profile/ads/Livewire CTAs, the **listing wizard** (teal chrome), auth pages + guest layout, points badge, Chart.js dashboards, legal prose links, footer accent bars, search/category breadcrumbs, cookie consent, pricing/ad-pricing pages, PDF report styles, SEO image generator, and profile avatar fallbacks. Residual legacy green `#1D9E75` / `#085041` eliminated from all runtime frontend code (2026-07-09); final pass verified no regressions in `*.php` / `*.blade.php` / `*.css` / `*.js`.

---

## 6. Fixed Development Rules

1. **PowerShell: never `&&`** — chain with `;` or run commands separately.
2. **AR/EN translation is mandatory for every new user-facing string from the first line** — public + authenticated area. Lang files: `lang/{ar,en}/{ui,wizard,listing,adspaces,server,auth,validation}.php` + root `ar.json`/`en.json`. Exception: `app/Filament/*` (admin) is Arabic-only by deliberate decision (Phase D deferred).
3. **Never delete code without explicit approval** — deprecate/flag instead, and ask.
4. **Discovery before implementation** — read the actual code/DB first; never assume from docs or memory. For risky data work, dry-run first (see `users:backfill-verification` pattern: read-only by default, `--execute` to write).
5. **`php artisan test` after every change** — suite must stay green (currently **453 passing, 0 failures**, 1387 assertions). Tests use in-memory SQLite, sync queue, `SCOUT_DRIVER=collection` (see `phpunit.xml`).
6. **Commit after each approved phase/step** — small, labeled commits.
7. **Western/Latin digits (1,2,3) everywhere, all locales** — never Arabic-Indic numerals in UI strings.
8. **Persisted `PointTransaction.description` strings stay Arabic** (written once at credit time — the documented permanent exception to rule 2).
9. **Media collections:** listings → `images`, campaigns → `ad_image`. Never read/write the legacy `listings` collection.
10. Sensitive flows never trust the client: re-verify ownership server-side (`where('user_id', Auth::id())`), IDOR-check ids against real DB relations, wrap multi-step writes in `DB::transaction`.

---

## 7. Completed Features

**Auth & accounts:** OTP registration gate (4-digit, 5-min expiry, progressive throttle) with **email/phone channel separation** (§9); device fingerprinting (3 accounts/device); Google social login; ban system; account **anonymization** on delete; profile page with staged phone (`pending_phone`) and email (`pending_email`) OTP verification flows, each with a one-time +20 bonus; password reset; roles `super_admin/admin/moderator/user` via FilamentShield. **Security hardening (July 2026):** session fixation fix, OTP throttles, mass-assignment hardening, phone hidden from JSON, phone-reveal throttle (20/hr), search param validation, auth-page no-store cache, XSS/JSON-LD fix, pending-listing exposure fix, OTP removed from logs, socialite email-only verify, chat channel auth, email-change re-auth, HTTPS in prod.

**Listings:** multi-step Alpine wizard (create + edit; edit locks category, forces re-moderation, preserves slug, no points); cars + real-estate category fields; dynamic `custom_fields_schema`; AI generation via Gemini; watermarked image conversions; **server-side image validation on both `store()` and `update()`**; moderation queue with 3-strike auto-ban; soft deletes; closing flow; listing detail with lightbox, share, similar listings, JSON-LD; favorites; search (Scout) + category pages with **AR/EN sort dropdown** (`ListingSort`: latest/oldest/price asc/desc).

**Sales & trust:** dual sale confirmation → buyer notification → `/dashboard/purchases` → 1–5 star review; denormalized seller ratings; `<x-rating-stars>`; seller response rate (min 5 offers); seller leads with detail pages.

**Economy:** point economy (§3) with atomic `PointService`; plans + 13-flag entitlements; Paymob points checkout (test mode); admin points adjustment via `PointService`; **July 2026 rebalance** (welcome/verify +20, feature costs 40/90/170/300, plan credits updated); wizard feature costs wired to `Listing::FEATURE_COSTS`.

**Ads:** admin-managed + self-service campaigns (§4); **Alpine hero carousel** with per-campaign duration; **popup modal** (countdown, 24h cap, enlarged layout); public ad-spaces pricing page; impression/click tracking + attribution.

**Analytics:** per-listing views/phone/WhatsApp clicks; entitlement-gated seller analytics + charts; Business dashboard; monthly PDF reports (scheduler); admin widgets.

**i18n:** public frontend + authenticated area bilingual AR/EN (Phases A–C complete); locale on `users.locale`; Carbon locale synced; legal page titles translated. **Gaps remain** — see §11 (`lang/en/types.php`).

**Admin (Filament, Arabic-only):** listing resource + moderation infolist; `ListingPolicy` (moderators: view/approve/reject only); user management; ad campaign approval; audit logs; TrashedFilter + restore.

**Platform/UX fixes shipped:** OTP boxes LTR order, delete-modal Alpine scope, profile icon overlap, phone-verified badge semantics, footer plans column, admin image-collection unification, email-verify +20 flow, wizard location prefill, pricing page plan cards with entitlement bullets (matrix removed), `public/.well-known/security.txt`, **launch-prep (2026-07-10):** `points_balance` mass-assignment guard, hero carousel bilingual `aria-label`, invalid `sort` HTTP validation + Nilex 422 page, Socialite error display on login, `revealPhone()` null-phone guard, `store()` image-rejection test, Paymob iframe via config only. **Final launch-prep (2026-07-10):** footer/social i18n, locale-aware WhatsApp prefill, offer rate limit, extended media eager-load (detail/dashboard/categories), bilingual Paymob 503 message.

---

## 8. Deferred / Incomplete

| Item | Status |
|---|---|
| **Phase D — Filament admin translation** | Deferred in full by business decision (staff are Arabic speakers). |
| **Daily login +1 points** | Not implemented (no scheduler/logic). Pricing Section 6 no longer advertises it (**Fixed 2026-07-09**). |
| **Points for positive review** | Deliberately removed from earn-guide; needs product decisions before wiring in `ReviewObserver`. |
| **Referral credit inconsistency** | **Fixed 2026-07-09** — now routes through `PointService::credit()` with campaign row lock. |
| **`featureWithPoints()` points race** | **Fixed 2026-07-09** — `DB::transaction` + `lockForUpdate` + `PointService::deduct()`. Entitlement `recordUsage()` row lock also fixed 2026-07-09. |
| `listing_detail` / `search_results` placements | Priced in config + selectable in Filament but no view renders them. |
| Seller-side `cancelBySeller()` UI | Model method exists; no UI action yet. |
| Auto-reject pending offers on listing close | Pending offers on closed listings stay `pending`. |
| Notifications: mark-as-read / "view all" page | Bell dropdown only. |
| Facebook / Instagram / TikTok login | Routes allow them; only Google configured. |
| `lang/en/types.php` | Missing (`lang/ar/types.php` exists AR-only). |
| **`search-results.blade.php` i18n** | **Fixed 2026-07-09** — moved to `ui.search.*` AR/EN keys. |
| **Custom error pages** | **Fixed 2026-07-09** — custom Nilex-styled pages added for `403/404/419/422/429/500` under `resources/views/errors/` with AR/EN `ui.errors.*` translations. GET validation failures render `errors/422` (2026-07-10). |
| `PAYMOB_IFRAME_ID` | Referenced by `config/services.php` but absent from `.env`. |
| Legal page `content` EN | Arabic-only by decision; EN visitors see Arabic body via `ar` fallback. |
| Re-skin residual green | **Fully completed 2026-07-10** — verified absent from all runtime frontend code; emerald retained for semantic success only. |
| **Offer spam — no rate limit** | **Fixed 2026-07-10** — `makeOffer()` 5 offers/min per user via `RateLimiter` (parity with `MessageController`). |
| **Footer i18n gaps** | **Fixed 2026-07-10** — social `aria-label`s, ad-spaces link, logo alt via `ui.footer.*` / `ui.nav.ad_spaces`. |
| **Detail/dashboard media N+1** | **Fixed 2026-07-10** — `ListingController::show()` + `UserDashboard` + homepage/wizard category icons eager-load `media`. |
| **Search-priority sort** | Fixed 2026-07-09 — boost applies in SQL `ORDER BY` before pagination in `HomeController::search()` and `CategoryController::show()` (default sort only). |
| **Scout production readiness** | `collection` driver locally; Meilisearch + queue indexing needed for prod. |

---

## 9. Sensitive Technical Architecture

### OTP channel separation (email vs phone)

- `users.otp_channel` records the active OTP's channel. Registration `OtpService::verify()` stamps **`email_verified_at`** (email channel) or **`is_phone_verified` + `phone_verified_at`** (phone channel) — never cross-stamps.
- **All four gates** accept either channel (`is_phone_verified || email_verified_at`): `EnsureOtpIsVerified`, `OtpController::show()`, `RedirectIfAuthenticated`, `AuthenticatedSessionController::store()`.
- Profile flows stage new contacts in `pending_phone` / `pending_email`. Bonuses guarded by permanent `phone_bonus_claimed_at` / `email_bonus_claimed_at`.
- `email_verified_at` writes need `forceFill()` (not in `$fillable`).
- `users:backfill-verification` fixed historical mis-flagged rows.

### Points double-column

`users.points` is the **only real balance**; `points_balance` is a mirror synced by `PointService::record()`. Never write either column directly — always go through `PointService`. All production credit/debit paths now use `PointService` (referral + featuring fixed 2026-07-09). `transfer()` locks both user rows in ascending `id` order to prevent deadlocks.

### Spatie MediaLibrary collections

- `Listing` → **`images`**; conversions `thumb`/`card`/`full_hd`, all watermarked + `nonQueued`; original never rendered publicly.
- `AdCampaign` → **`ad_image`**; conversions `desktop`/`tablet`/`mobile`.
- Category icons are media-library-backed.
- **Public listing grids eager-load `media`** (homepage, category, search, detail, similar listings, seller dashboard) so `getFirstMediaUrl()` resolves from memory — P1 fixed 2026-07-09; extended 2026-07-10.

### Soft-delete couplings

`Listing` uses `SoftDeletes`. Relations that must stay `withTrashed()`: `SaleConfirmation::listing()`, `Review::listing()`, `Offer::listing()`, `SellerLead::listing()`.

### AdCampaignService cache

§4 — local env always bypasses; production invalidation is observer→job driven.

### Timezone & locale

`Africa/Cairo` app-wide. Default locale `ar`, Laravel fallback `en`, Spatie translatable fallback **`ar`** — wired manually in `AppServiceProvider::boot()`.

### Misc gotchas

- Blade: never put the literal token `@php` inside a `{{-- --}}` comment.
- JSON-LD rendered with escaped slashes (no `JSON_UNESCAPED_SLASHES`) to prevent `</script>` breakout XSS.
- Livewire modals: prefer `@if($flag)` server-rendered wrappers over `x-show`+`@entangle`.
- Paymob endpoints: `/auth/tokens`, `/acceptance/payment_keys`; always via `config('services.paymob.*')` (never `env()` in controllers).
- OTP flex container needs explicit `dir="ltr"`.
- `ListingController::canViewListing()` gates non-published listings (owner/admin only).
- `users.otp_code` remains in `$fillable` because `OtpService` writes via `update()` — acceptable while only trusted services call it; consider `forceFill()` + removal from `$fillable` in a future hardening pass.

---

## 10. Launch Notes (as of July 2026)

### Done
- **Tests: 453 passing, 0 failures** (1387 assertions; Pest; in-memory SQLite).
- Entire public + authenticated frontend bilingual AR/EN (known gap: `lang/en/types.php` only); RTL/LTR correct.
- Paymob integration verified end-to-end **in test mode** (points + ad checkouts).
- Moderation pipeline complete with notifications + audit trail.
- **Visual identity (navy/teal) fully completed** across frontend incl. wizard + auth pages (verified 2026-07-10).
- Image watermarking + backfill command; SEO JSON-LD; OG images command.
- **Security patch series** (July 2026): XSS, listing exposure, OTP logging, session fixation, throttles, mass assignment, JSON serialization, phone-reveal throttle, store() image validation, offer rate limit.
- Listing sort system; hero carousel; popup modal; email verification +20; point economy rebalance.
- **Launch-prep code hygiene (2026-07-10):** remaining Low audit items L1/L3/L4/L5/L7 fixed; M5/M12 fixed; M13 improved (Nilex 422 page for GET validation); payment callback pages on `layouts.frontend` (M9/M10 fixed 2026-07-09).
- **Final launch-prep (2026-07-10):** footer/social i18n, media eager-load extensions, locale-aware WhatsApp prefill, bilingual gateway error messages, offer throttle parity with messages.

### Not done yet (launch blockers / decisions)
| Item | Current state |
|---|---|
| **SMS gateway** | `SmsService` logs OTPs locally; production API is a placeholder. No `SMS_*` keys in `.env`. **Must configure before production OTP/SMS.** |
| **Paymob live mode** | Test credentials only; `PAYMOB_IFRAME_ID` must be set in `.env` for checkout (config reads via `config/services.php`; missing value aborts 503). |
| **Hosting / deployment** | Not deployed — local Laragon only. Plan: Laravel Forge. |
| **Production infra switches** | Queue `sync`→Redis, cache `file`→Redis, Scout `collection`→Meilisearch, `MAIL_MAILER` `log`→real SMTP. |
| **Performance indexes** | **Fixed 2026-07-09** — migration `2026_07_09_000001_add_performance_indexes_to_core_tables.php` (see §12). |
| **High-severity bugs** | All patched as of 2026-07-09; launch-prep fixed remaining Medium/Low items M5/M12 and Low L1–L5/L7. |
| **UI/UX designer pass** | Planned hire via خمسات (Khamsat). |
| **Search page i18n** | **Fixed 2026-07-09** — bilingual AR/EN with `ui.search.*`. |
| **Socialite referral (L2)** | Social signup still skips referral attribution — product decision deferred. |
| **Extra social logins** | Facebook/Instagram/TikTok routes exist; only Google configured in `.env`. |

---

## 11. Audit Findings — Bugs & UX (2026-07-09)

**0 Critical security bugs.** All previously critical items patched (see changelog below).

### High

| ID | Issue | Location | Status |
|---|---|---|---|
| H1 | **Referral credit bypasses `PointService`** | `RegisteredUserController.php` | **Fixed 2026-07-09** |
| H2 | **`featureWithPoints()` points race** | `Listing.php` | **Fixed 2026-07-09** |
| H3 | **`featureWithPoints()` entitlement race** | `Listing.php` + `EntitlementService.php` | **Fixed 2026-07-09** |
| H4 | **Search page entirely hardcoded Arabic** | `search-results.blade.php` | **Fixed 2026-07-09** |
| H5 | **Search-priority re-sort post-pagination** | `HomeController::search()` | **Fixed 2026-07-09** |
| H6 | **OTP gate error not shown** | Middleware + auth view | **Fixed 2026-07-09** |
| H7 | **No custom error pages** | `resources/views/errors/` absent | **Fixed 2026-07-09** |

### Medium

| ID | Issue | Location | Status |
|---|---|---|---|
| M1 | **`points_balance` desync after featuring** | `Listing::featureWithPoints()` | **Fixed 2026-07-09** |
| M2 | **Referral `used_count` race** | `RegisteredUserController` + `CampaignLink` | **Fixed 2026-07-09** |
| M3 | **IDOR in `buyerLeads()`** — no `listing.user_id === Auth::id()` check (unlike `confirmSaleToBuyer()`) | `UserDashboard.php` ~162–177 | **Fixed 2026-07-09** — ownership re-verified via `Listing::where('user_id', Auth::id())`; `abort(403)` on mismatch |
| M4 | **WhatsApp click tracking unauthenticated** — no auth/status gate; metric inflation possible | `ListingController::trackWhatsappClick()` | **Fixed 2026-07-09** — `auth` middleware + published-only guard; 401 guests, 404 non-published |
| M5 | **Null phone in `revealPhone()`** — `ltrim(null)` on missing phone | `ListingController.php` ~87–88 | **Fixed 2026-07-10** — null-safe phone lookup; `whatsapp_url` null when no phone; no lead row recorded |
| M6 | **`featureWithPoints()` not atomic** | `Listing.php` | **Fixed 2026-07-09** |
| M7 | **Message spam — no rate limit** | `MessageController.php` | **Fixed 2026-07-09** — 5 messages/min per authenticated user via `RateLimiter`; JSON 429 or redirect with `ui.messages.rate_limit_exceeded` |
| M8 | **Pricing Section 6 inaccurate** — advertised daily +1 (not implemented), hardcoded referral +25, omitted email +20, hardcoded feature costs | `pricing.blade.php` ~291–337 | **Fixed 2026-07-09** — bilingual `ui.pricing.earn.*` / `spend.*`; config-backed earn values; `Listing::FEATURE_COSTS` loop; referral from active `CampaignLink`; daily login removed |
| M9 | **Payment failed CTA mismatch** — label says "Back to Home", href is `dashboard` | `payment/failed.blade.php` | **Fixed 2026-07-09** — primary CTA links to `route('home')`; secondary retry links to pricing |
| M10 | **Payment callbacks use Breeze layout** — not `layouts.frontend` | `payment/success.blade.php`, `failed.blade.php` | **Fixed 2026-07-09** — both pages extend `layouts.frontend` with Nilex branding |
| M11 | **Ad popup skip strings hardcoded Arabic** | `ad-popup.blade.php` | **Fixed 2026-07-09** — bilingual `ui.ad_popup.*` keys for skip, countdown, and close |
| M12 | **Socialite errors not displayed on login** — `withErrors(['error'])` but no `@error('error')` | `SocialiteController` → `login.blade.php` | **Fixed 2026-07-10** — alert block for `error` + `contact` keys |
| M13 | **Search GET validation returns 422 page** — no inline form feedback | `HomeController::search()` | **Improved 2026-07-10** — Nilex-styled `errors/422` for GET validation (tampered query params); JSON clients get 422 validation JSON; form POST validation unchanged (redirect-back) |
| M14 | **Category pages lack search-priority boost** — inconsistent with search | `CategoryController.php` | **Fixed 2026-07-09** — same `LEFT JOIN user_entitlements` + `CASE WHEN` ordering as `HomeController::search()` on default sort |
| M15 | **Offer spam — no rate limit** | `ListingController::makeOffer()` | **Fixed 2026-07-10** — 5 offers/min per authenticated user via `RateLimiter`; JSON 429 with `server.offer.rate_limit_exceeded` |

### Low

| ID | Issue | Location | Status |
|---|---|---|---|
| L1 | `points_balance` remains `$fillable` on User | `User.php` | **Fixed 2026-07-10** — removed from `$fillable`; mirror synced only via `PointService` |
| L2 | Socialite signup skips referral flow | `SocialiteController.php` | Deferred — product decision |
| L3 | No dedicated test for `store()` image rejection (update tested in `ListingEditTest`) | tests | **Fixed 2026-07-10** — `ListingEditTest` store non-image 422 case |
| L4 | `env()` fallback in payment checkout | `PaymentController.php` ~68 | **Fixed 2026-07-10** — `config('services.paymob.iframe_id')` only; aborts 503 when blank |
| L5 | Hero carousel dot `aria-label="Slide N"` English-only | `home.blade.php` | **Fixed 2026-07-10** — `ui.hero.carousel_slide_aria` AR/EN |
| L6 | Dead file `listing-details.blade.php` (hardcoded Arabic if ever routed) | views | **Deferred** — file header documents active route is `listings/show.blade.php`; not deleted (awaiting explicit approval) |
| L7 | Invalid `sort` query silently falls back to `latest` | `ListingSort::fromRequest()` | **Fixed 2026-07-10** — HTTP validated in `CategoryController` + `HomeController::search()`; `isValid()` helper; defensive fallback documented; tests for 422 JSON + Nilex 422 page |
| L8 | Footer social `aria-label`s + ad-spaces link hardcoded Arabic | `footer.blade.php` | **Fixed 2026-07-10** — `ui.footer.social_*`, `ui.nav.ad_spaces`, `ui.footer.brand` |

### Security fixes since 2026-07-05 audit (verified in git)

| Commit | Fix |
|---|---|
| `8539b51` | XSS (JSON-LD), pending listing exposure, OTP removed from logs |
| `ce57f76` | Session fixation, OTP throttles, seeder password, mass-assignment hardening |
| `85be990` | Socialite email-only verify, broadcast channel auth, email change re-auth, HTTPS prod |
| `a8b8fcf` | Phone hidden from JSON, phone-reveal throttle, search validation, generic DB errors |
| `4480f7d` | Extended `$hidden` (phone, device, 2FA placeholders) |
| `ff579e6` | Auth-page `Cache-Control: no-store` |
| `06d6cc3` | **Server-side image validation on `store()`** |
| `7d0e11d` | Admin points adjustment via `PointService` |

### Feature changelog since 2026-07-05 audit

| Area | Change |
|---|---|
| Sort | `ListingSort` support class + AR/EN dropdown on category + search (`a6d9316`, `ListingSortTest`) |
| Hero | Alpine carousel, per-campaign duration, arrow nav removed (`ba47d46`, `637f18e`) |
| Popup | Full-screen → centred modal, countdown, 24h cap, enlarged layout, 11+ tests |
| Email verify | Profile flow +20 pts, guarded by `email_bonus_claimed_at` (`c31a5b2`) |
| Points | Rebalance: welcome/verify 20, feature 40/90/170/300, plan credits updated (`a266ff5`) |
| Pricing | Plan cards with entitlement bullets; comparison matrix removed |
| Wizard | Feature costs from `Listing::FEATURE_COSTS` JSON |
| Security | `public/.well-known/security.txt` added |
| Points race | `PointService` transfer deadlock fix; referral + `featureWithPoints()` routed through locked transactions; `EntitlementService::recordUsage()` row locks (2026-07-09) |
| Launch prep | L1/L3–L5/L7/L8 Low fixes; M5/M12/M15 fixes; M13 422 page; 453 tests (2026-07-10) |
| Final launch prep | Footer i18n, media eager-load extensions, offer throttle, WhatsApp prefill i18n, Paymob 503 i18n (2026-07-10) |

---

## 12. Audit Findings — Performance (2026-07-09)

### Critical / High

| Priority | Issue | Location |
|---|---|---|
| **P1** | **Media N+1 on public listing grids** | `HomeController`, `CategoryController`, `listing-card.blade.php`, `search-results.blade.php` | **Fixed 2026-07-09** — `with('media')` on homepage, search, category. **Extended 2026-07-10** — `ListingController::show()` (detail + similar), `UserDashboard`, homepage/wizard category icons. |
| **P2** | **Missing `listings.status` indexes** — no index on `status`, `(status, created_at)`, `(user_id, status)`, `(category_id, status)` | migrations | **Fixed 2026-07-09** — `2026_07_09_000001_add_performance_indexes_to_core_tables.php` adds: `listings` — `(status)`, `(status, created_at)`, `(user_id, status)`, `(user_id, created_at)`, `(category_id, status)`, `(is_featured, featured_until)`, `(deleted_at)`; `offers` — `(receiver_id, status)`; `media` — `(model_type, model_id, collection_name)` |
| **P3** | **UserDashboard query storm** — 5+ separate count queries + chart queries + unbounded `incomingOffers->get()` every render | `UserDashboard.php` |
| **P4** | **Scout `collection` driver** — full in-memory scan; unusable at scale until Meilisearch | `config/scout.php` |
| **P5** | **Competitor pricing N+1** — `Listing::avg('price')` per listing in loop | `SellerListingAnalyticsService.php` ~226–251 |
| **P6** | **Business dashboard unbounded `get()`** — all seller listings loaded | `BusinessDashboard.php` |
| **P7** | **Homepage hero bypasses ad cache** + sends `Cache-Control: no-store` for entire page | `HomeController::index()` |

### Medium

| Issue | Location |
|---|---|
| Entitlement `hasFeature()` per seller badge on listing cards | `business-badge.blade.php` |
| Category icons media N+1 | `home.blade.php` | **Fixed 2026-07-10** — `with('media')` on homepage + wizard category queries |
| Detail page missing `category` eager-load | `ListingController.php` | **Fixed 2026-07-10** — `category` + `media` in `show()` |
| `getMonthlyPerformance()` loads all view rows into PHP | `SellerListingAnalyticsService.php` |
| Filament listings table no default eager load | `ListingResource` / `ListingTable.php` |
| Scout: price/category filters applied post-search in SQL callback (sparse pages) | `HomeController::search()` vs `ListingGrid.php` |
| Ad impression dedup non-atomic (`has` + `put`) | `AdCampaignService.php` |
| Wizard location tree ignores `Location::getCachedAll()` | `HomeController.php` |

### Index additions — **applied 2026-07-09** (`2026_07_09_000001_add_performance_indexes_to_core_tables.php`)

| Table | Index | Query paths served |
|---|---|---|
| `listings` | `(status)` | Filament moderation tabs, `StatsOverviewWidget` published count |
| `listings` | `(status, created_at)` | Homepage `latestListings`, category/search default sort |
| `listings` | `(user_id, status)` | Seller dashboard status badge counts |
| `listings` | `(user_id, created_at)` | Seller dashboard paginated listing list (`latest()`) |
| `listings` | `(category_id, status)` | Category page `where status=published` |
| `listings` | `(is_featured, featured_until)` | Homepage featured carousel, `scopeFeatured()`, `activeFeaturedCount()` |
| `listings` | `(deleted_at)` | Soft-delete global scope on every listing query |
| `offers` | `(receiver_id, status)` | Dashboard pending incoming offers inbox |
| `media` | `(model_type, model_id, collection_name)` | Spatie `images` collection lookup per listing card (morph index lacks `collection_name`) |

`user_entitlements` already has `UNIQUE (user_id, feature_key)` plus single-column indexes on `user_id` and `feature_key` — sufficient for `EntitlementService` lookups and search-priority JOINs; no additional index added.

---

## 13. Audit Findings — Consistency (2026-07-09)

| Area | Inconsistency |
|---|---|
| Point mutations | All paths use `PointService` with `lockForUpdate()` (fixed 2026-07-09) |
| Search vs category | Search-priority entitlement boost on both search and category pages (default sort only) |
| Search implementations | `HomeController::search()` vs `ListingGrid.php` — different Meilisearch filter placement |
| Pricing marketing | Section 6 reads config + `Listing::FEATURE_COSTS` + active `CampaignLink` rewards (fixed 2026-07-09) |
| Translation coverage | Category/detail/profile/search/ad-popup/footer/cookie consent bilingual |
| Payment UX | Success/fail pages use frontend layout + Nilex branding (fixed 2026-07-09) |
| Ad tracking | Banners track impressions; popup does not |
| Feature cost display | Wizard and pricing Section 6 both use `Listing::FEATURE_COSTS` |
