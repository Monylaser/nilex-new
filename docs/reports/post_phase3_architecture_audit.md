# Post-Phase-3 Architecture Audit

**Project:** Nilex Marketplace  
**Date:** 2026-06-11  
**Auditor role:** Senior Laravel Architect  
**Scope:** Full project review after Phase 1, 2A, 2B, 3A, 3B, 3C  
**Mode:** Read-only audit — no code, migrations, or business-logic changes were made

---

## Executive Summary

Nilex has evolved from a listings marketplace into a **dual-analytics platform**: monetization BI (Phase 1) and lead-funnel BI (Phases 2A–3C). The architecture is sound for MVP scale: event-sourced lead tracking with deduplication, SQL-aggregated Filament widgets, transactional points/payments, and a mature Filament admin surface.

**Strengths**

- Clear separation between **revenue analytics** (`transactions`) and **lead analytics** (event tables).
- `ListingLeadTrackingService` centralizes dedup rules; admin widgets consistently read event tables, not legacy counters.
- Paymob webhook handling includes HMAC verification, amount checks, and idempotent fulfillment.
- Auth layer (`app/Auth`) provides hashed OTP, rate limiting, device limits, and queued delivery.

**Critical gaps**

- **Seller-facing analytics still use legacy columns** (`views_count`, `whatsapp_clicks`) while admin BI uses event tables — data divergence risk for sellers.
- **Test suite is broken** (143/145 tests fail): Phase 3A index migration uses MySQL `SHOW INDEX` on SQLite test DB.
- **Dashboard widget registration** combines `discoverWidgets()` with an explicit `widgets()` array — risk of duplicate widgets and sort-order collisions.
- **Category hierarchy is modeled but unused** in listing flows; category analytics will under-report when subcategories are adopted without rollup.

**Overall posture:** Production-capable for early launch with `super_admin`-driven BI, but **Phase 4 should prioritize seller analytics alignment, test repair, and operational hardening** before scaling traffic or subcategory rollout.

---

## Findings

### 1. Completed Modules

#### Phase 1 — Revenue & Monetization Analytics

| Deliverable | Status | Notes |
|-------------|--------|-------|
| `MonetizationOverviewWidget` | ✅ | Total revenue, transactions, AOV, points sold from `transactions` |
| `DailyRevenueWidget` | ✅ | Today vs yesterday growth |
| `RevenueAlertWidget` | ✅ | Daily threshold alert (500 EGP hardcoded) |
| `WeeklyRevenueChart` | ✅ | 7-day revenue line chart |
| `MonthlyRevenueWidget` | ✅ | MoM growth |
| `ConversionMetricsWidget` | ✅ | Registered → paying user conversion |
| `StatsOverviewWidget` refactor | ✅ | Removed incorrect `point_transactions` revenue stat |
| `BestSellingPlansChart` | ✅ | Pre-existing; aligned to `transactions` |

**Data source:** `Transaction::completed()` (`status = 'completed'`), joined to `point_plans` for points sold.

#### Phase 2A — Lead Tracking Infrastructure (Foundation)

| Deliverable | Status | Notes |
|-------------|--------|-------|
| `listing_views` table | ✅ | `listing_id`, `user_id`, `ip_address`, `created_at` |
| `listing_phone_clicks` table | ✅ | 1h user dedup |
| `listing_whatsapp_clicks` table | ✅ | 1h user dedup |
| `ListingView`, `ListingPhoneClick`, `ListingWhatsappClick` models | ✅ | BelongsTo listing/user |
| `ListingLeadTrackingService` | ✅ | 24h view dedup (user/IP); 1h click dedup |
| `Listing` event relationships | ✅ | `views()`, `phoneClicks()`, `whatsappClicks()` |
| `User` event relationships | ✅ | `listingViews()`, `listingPhoneClicks()`, `listingWhatsappClicks()` |
| Analytics indexes migration | ✅ | `created_at` indexes (MySQL-specific implementation — see debt) |

#### Phase 2B — Lead Tracking Wired to User Actions

| Deliverable | Status | Notes |
|-------------|--------|-------|
| `ListingController::show()` → `recordView()` | ✅ | Gated `views_count` increment (view tracking fix) |
| `revealPhone()` → `recordPhoneClick()` | ✅ | Auth required (401 for guests) |
| `trackWhatsappClick()` route + controller | ✅ | `POST /listings/{listing}/whatsapp-click` |
| Frontend `show.blade.php` integration | ✅ | WhatsApp click AJAX tracking |
| `makeOffer()` | ✅ | Pre-existing; writes to `offers` |

#### Phase 3A — Lead Funnel Analytics

| Deliverable | Status | Notes |
|-------------|--------|-------|
| `LeadFunnelWidget` | ✅ | Views → Phone → WhatsApp → Offers funnel + 3 CTR stats |
| Date-range filter component | ✅ | Today / 7d / 30d (shared by 3B/3C) |
| `super_admin` authorization | ✅ | Consistent with revenue widgets |

#### Phase 3B — Top Listings Analytics

| Deliverable | Status | Notes |
|-------------|--------|-------|
| `TopListingsWidget` | ✅ | TableWidget; event-table aggregation; top 10 |
| Ranking | ✅ | Offers → CTR → Views |
| Verification | ✅ | Transactional script + phase report PASS |

#### Phase 3C — Category Performance Analytics

| Deliverable | Status | Notes |
|-------------|--------|-------|
| `CategoryPerformanceWidget` | ✅ | `GROUP BY listings.category_id` (Option A) |
| Total Leads definition | ✅ | Phone + WhatsApp (offers separate) |
| Ranking | ✅ | Total Leads → CTR → Views |
| Verification | ✅ | `scripts/verify_category_performance_widget.php` PASS |

#### Platform Foundations (Parallel / Pre-Phase Work)

| Module | Status | Key components |
|--------|--------|----------------|
| **Design system (UI Phase 1)** | ✅ | Nilex brand tokens, `app.css`, Tailwind `nilex` palette |
| **Listings core** | ✅ | CRUD, moderation, fraud auto-flag, Scout search, Spatie media + watermark |
| **Categories & locations** | ✅ | Hierarchy API, governorates, dynamic custom fields |
| **Auth & security** | ✅ | Breeze, OTP, Socialite, device limits, ban middleware |
| **Points economy** | ✅ | `PointService` (transactional), feature listing, `PointTransaction` ledger |
| **Payments (Paymob)** | ✅ | Checkout, HMAC webhook, idempotent fulfillment |
| **Offers** | ✅ | Make/accept/reject on user dashboard |
| **Messaging** | ✅ | Real-time events; no listing context on messages |
| **Campaigns** | ✅ | Admin campaign resources; `TrackCampaign` session middleware |
| **Filament admin** | ✅ | 15+ resources, Shield permissions, translatable plugin |
| **Legal / SEO** | ✅ | Legal pages, SEO templates, site settings |
| **Observability** | ⚠️ Partial | Activity log, audit logs, Telescope (dev); no Horizon in prod checklist |

---

### 2. Remaining Technical Debt

| ID | Debt | Impact | Origin |
|----|------|--------|--------|
| TD-01 | **Dual analytics sources** — seller dashboard sums `views_count` / `whatsapp_clicks`; admin uses event tables | Sellers see different numbers than ops BI | Phase 2B partial sync |
| TD-02 | **Legacy `whatsapp_clicks` column never updated** by `trackWhatsappClick()` | Column stale; misleading in seller UI | Phase 2B scope |
| TD-03 | **`points` + `points_balance` dual columns** | Sync drift if code bypasses `PointService` | Historical |
| TD-04 | **MySQL-only index migration** (`SHOW INDEX`) breaks SQLite tests | Entire Pest suite fails (143 tests) | Phase 3A |
| TD-05 | **`featureWithPoints()` returns void** but `UserDashboard` treats return as boolean | Success path always shows "insufficient points" flash | Pre-existing |
| TD-06 | **`ListingController::index()` uses `status = 'active'`** vs canonical `published` | Dead code path if route ever used | Pre-existing |
| TD-07 | **Widget duplicate registration** — `discoverWidgets()` + explicit `widgets()` array | Possible duplicate dashboard widgets | Admin panel config |
| TD-08 | **`$sort` collisions** — e.g. `DailyRevenueWidget` & `ListingsChart` both `2`; `WeeklyRevenueChart` & `CategoriesChartWidget` both `4` | Unpredictable widget order | Phase 1 |
| TD-09 | **`RevenueAlertWidget` threshold hardcoded** (500 EGP) | Not configurable without code change | Phase 1 |
| TD-10 | **`Listing::featureWithPoints()` bypasses `PointService`** | Inconsistent ledger, no `lockForUpdate` | Pre-existing |
| TD-11 | **Category flat usage** — hierarchy unused in listing forms/search | Future rollup required for accurate category BI | Schema vs product |
| TD-12 | **Filament brand still violet/purple** while frontend is Nilex green | Brand inconsistency in admin | UI vs admin theming |
| TD-13 | **Only `UserFactory` exists** — no Listing/Category factories | Tests create models manually; brittle | Test infra |
| TD-14 | **`PointTransaction::reference()` morph** without `reference_type` column in fillable/schema audit | Morph may be incomplete | Pre-existing |

---

### 3. Missing Relationships

| Model | Missing relationship | Severity | Notes |
|-------|---------------------|----------|-------|
| `User` | `offersSent()`, `offersReceived()` | Medium | `Offer` has inverse; user-centric queries use raw `Offer::where` |
| `User` | `messagesSent()`, `messagesReceived()` | Low | `Message` has sender/receiver |
| `Message` | `listing()` BelongsTo | High (product) | Messages not tied to listings — limits inquiry analytics |
| `Campaign` | `links()` HasMany → `CampaignLink` | Medium | Campaign attribution incomplete |
| `CampaignLink` | No Eloquent relationships | Low | Standalone referral codes |
| `Category` | Aggregated listing rollup helpers | Medium | Only `listings()` direct; no `allListingsIncludingChildren()` |
| `Listing` | ~~`offers()`~~ | **Resolved** | Added in current codebase (`hasMany Offer`) |
| `PointTransaction` | Proper morph setup (`reference_id` + `reference_type`) | Medium | `morphTo()` present; schema alignment unclear |
| `Transaction` | No `pointTransactions()` link | Low | Separate monetization vs ledger |
| `ListingImage` | Unused if Spatie Media is canonical | Low | Two image systems coexist |

---

### 4. Analytics Architecture Review

#### 4.1 Data layers

```
┌─────────────────────────────────────────────────────────────────┐
│                     ADMIN FILAMENT BI                           │
├──────────────────────────┬──────────────────────────────────────┤
│  Revenue layer           │  Lead funnel layer                   │
│  transactions (completed)│  listing_views                       │
│  + point_plans           │  listing_phone_clicks                │
│                          │  listing_whatsapp_clicks             │
│                          │  offers                              │
└──────────────────────────┴──────────────────────────────────────┘
                              ▲
                              │ ListingLeadTrackingService
                              │ (dedup + insert)
┌─────────────────────────────┴───────────────────────────────────┐
│  LEGACY DENORMALIZED COUNTERS (seller UI)                       │
│  listings.views_count (synced on new views only)                │
│  listings.whatsapp_clicks (NOT synced from event table)         │
│  categories.views_count (category page visits — separate metric) │
└─────────────────────────────────────────────────────────────────┘
```

#### 4.2 Event tracking service

`ListingLeadTrackingService` is the **correct write boundary**:

- Views: 24h dedup per user OR IP
- Phone/WhatsApp: 1h dedup per authenticated user only (guest clicks not deduped by user)
- Phone reveal requires auth — intentional lead-quality filter

**Gap:** WhatsApp tracking does not require auth on `trackWhatsappClick()` — bot/script inflation possible.

#### 4.3 Widget query patterns

| Widget | Queries per load | Pattern | Assessment |
|--------|------------------|---------|------------|
| `LeadFunnelWidget` | 4 | Separate `COUNT(*)` per table | Acceptable at MVP; could unify |
| `TopListingsWidget` | 1 | Correlated subqueries + `fromSub` | Strong — verified single query |
| `CategoryPerformanceWidget` | 1 | Grouped subqueries + `leftJoinSub` | Strong — verified single query |
| Revenue widgets | 1–2 each | Aggregates on `transactions` | Good |
| `CategoriesChartWidget` | 1 | Listing count by category (inventory, not leads) | Different metric — label clearly |

#### 4.4 Metric definition inconsistencies (documented, not bugs)

| Metric | TopListingsWidget | CategoryPerformanceWidget | LeadFunnelWidget |
|--------|-------------------|---------------------------|------------------|
| Primary ranking | Offers → CTR → Views | Total Leads → CTR → Views | Funnel steps |
| CTR formula | phone / views | phone / views | Multiple step CTRs |
| "Leads" | Implicit (offers weighted) | phone + whatsapp | Separate steps |

**Recommendation:** Publish an internal **Analytics Dictionary** so product and engineering share definitions.

#### 4.5 Authorization

All analytics widgets use `canView()` → `super_admin` only. **Admin** and **moderator** roles see Filament but not BI — intentional for MVP.

---

### 5. Dashboard Architecture Review

#### 5.1 Admin dashboard (`AdminPanelProvider`)

**Registration order (explicit `$sort`):**

| Sort | Widget | Type |
|------|--------|------|
| 1 | MonetizationOverviewWidget | Stats |
| 2 | DailyRevenueWidget | Stats |
| 3 | RevenueAlertWidget | Stats |
| 4 | WeeklyRevenueChart | Chart |
| 5 | MonthlyRevenueWidget | Stats |
| 6 | ConversionMetricsWidget | Stats |
| 7 | LeadFunnelWidget | Stats (funnel) |
| 8 | TopListingsWidget | Table (full width) |
| 9 | CategoryPerformanceWidget | Table (full width) |
| 10 | StatsOverviewWidget | Stats |
| — | ListingsChart, GovernoratesChart, CategoriesChart, BestSellingPlans | Discovered + explicit |

**Issues:**

1. **`discoverWidgets()` + explicit list** — Filament may register widgets twice unless discovery is disabled or widgets are excluded from discovery.
2. **Sort collisions** — multiple widgets share sort values; Filament order may not match intended narrative.
3. **No dashboard sections/tabs** — revenue and lead widgets interleaved; cognitive load for operators.
4. **No lazy loading** — each widget fires queries on dashboard load; ~15+ widgets for `super_admin`.

#### 5.2 Seller dashboard (`UserDashboard` Livewire)

- Stats from **legacy counters** on `listings` table
- Offers workflow functional (accept/reject)
- Feature listing via points (buggy boolean check on `featureWithPoints`)
- No event-table analytics, no period filters, no CTR

#### 5.3 Frontend home dashboard

- Scout-powered search, featured listings, category grid
- Separate from admin BI — appropriate separation

---

### 6. Performance Concerns

| Area | Risk | Severity | Detail |
|------|------|----------|--------|
| Admin dashboard load | High (at scale) | 15+ widgets × multiple queries per `super_admin` visit |
| Event table growth | Medium | `listing_views` unpartitioned; no archival policy |
| `LeadFunnelWidget` | Low–Medium | 4 sequential counts; no composite funnel query |
| Scout/Meilisearch | Medium | Index sync on listing changes; reindex strategy unclear |
| Media conversions | Medium | `nonQueued()` image processing on upload — blocks request |
| Category `allChildren()` | Low today | Recursive eager load dangerous at scale |
| Dedup existence checks | Medium | Per-event `exists()` query before insert on every view |
| MySQL date filters | Low | Indexed `(listing_id, created_at)` supports widget queries |

**Positive:** Top 10 widgets cap in SQL; no PHP aggregation loops for 3B/3C.

---

### 7. Security Concerns

| Area | Status | Detail |
|------|--------|--------|
| Paymob HMAC | ✅ Good | Signature + amount verification + idempotent processing |
| CSRF exempt webhooks | ✅ Expected | `/payments/callback`, `/payment/webhook` only |
| OTP security | ✅ Remediated | Hashed OTP, rate limits, lockout (see production readiness report) |
| Phone reveal auth | ✅ | Guests cannot harvest phone numbers |
| WhatsApp click tracking | ⚠️ | No auth required — inflatable metric |
| Ban middleware | ✅ | `EnsureUserIsNotBanned` on web group |
| CSP | ❌ TODO | Not implemented (production checklist) |
| `Listing` `$guarded = []` | ⚠️ | Mass assignment wide open on model |
| Filament access | ✅ | Role-gated `canAccessPanel` |
| Analytics visibility | ✅ | `super_admin` only on widgets |
| Activity/audit logging | ✅ | Spatie activity log + custom audit logs |

---

### 8. Testing Coverage Review

#### 8.1 Test inventory

| Area | Files | Automated coverage |
|------|-------|-------------------|
| Auth (OTP, device limit, social) | 10+ | Strong intent |
| Listings workflow | 2 | Moderate (approval, boost, phone reveal) |
| Search | 1 | Strong (Scout collection driver in tests) |
| Dashboard stats | 1 | Legacy counters only |
| Chat | 1 | Present |
| AI/Gemini | 1 | Present |
| Fraud detection | 1 | Present |
| **Lead tracking / funnel widgets** | **0** | **None** |
| **Revenue widgets** | **0** | **None** |
| **Paymob webhook** | **0** | **None in Pest** |
| Cypress E2E | 4 files | Auth flows (per production report) |

**Total Pest tests:** ~145 cases across 24 files.

#### 8.2 Current test health

```
Tests: 143 failed, 2 passed (as of 2026-06-11)
Root cause: database/migrations/2026_06_11_000002_add_lead_funnel_analytics_indexes.php
             uses MySQL SHOW INDEX on SQLite :memory: test database
```

**Impact:** CI signal is broken; regressions in Phases 2B–3C are not guarded by automated tests.

#### 8.3 Verification scripts (manual)

| Script | Purpose |
|--------|---------|
| `scripts/verify_category_performance_widget.php` | Category widget metrics |

Phase 3B used transactional verification (script removed after report). **Manual verification exists but is not in CI.**

---

### 9. Recommended Next Business Features

| Feature | Business value | Technical complexity | Priority |
|---------|------------------|----------------------|----------|
| **Seller lead analytics dashboard** | Sellers see views/clicks/offers per listing — increases trust and upsell to featured ads | Medium — read event tables per `user_id` listings | **High** |
| **Offer notifications** | Faster seller response → higher conversion | Low — notification on `Offer::created` | **High** |
| **Subcategory listing flow** | Better discovery in cars/real estate — unlocks category rollup BI | Medium — form UX + search filters | **High** |
| **In-app messaging per listing** | Contextual buyer-seller chat tied to listing | Medium — add `listing_id` to messages | **High** |
| **Configurable revenue targets** | Ops can set daily/monthly goals without deploys | Low — `site_settings` or env | **Medium** |
| **Seller WhatsApp analytics** | Align seller stats with admin BI | Low — aggregate `listing_whatsapp_clicks` | **Medium** |
| **Campaign attribution reporting** | Measure marketing ROI from `campaign_code` session | Medium — persist attribution on registration/conversion | **Medium** |
| **Saved searches / alerts** | Retention for buyers | Medium — new tables + notifications | **Medium** |
| **Mobile PWA / push** | Egypt mobile-first market | High | **Low** (post-MVP) |
| **Parent category rollup in admin BI** | Accurate category performance when subcategories launch | Medium — SQL rollup or `root_category_id` | **Medium** (defer until subcategories live) |

---

### 10. Recommended Phase 4 Roadmap

Phase 4 is **recommended** — focus shifts from "build BI" to "align stakeholders, harden ops, and monetize insights."

#### Phase 4A — Stabilization (Weeks 1–2)

| Item | Business value | Technical complexity | Priority |
|------|------------------|----------------------|----------|
| Fix index migration for cross-DB compatibility | Restores CI; prevents prod migration failures on non-MySQL | Low | **High** |
| Add Pest tests for lead tracking + widgets | Prevents analytics regressions | Medium | **High** |
| Resolve widget registration duplication | Clean admin UX | Low | **High** |
| Fix `UserDashboard::featureListing()` boolean bug | Sellers can feature listings correctly | Low | **High** |
| Migrate seller stats to event tables (or sync job) | Single source of truth across platform | Medium | **High** |
| Add auth or rate limit to WhatsApp click endpoint | Metric integrity | Low | **Medium** |

#### Phase 4B — Seller Experience (Weeks 3–4)

| Item | Business value | Technical complexity | Priority |
|------|------------------|----------------------|----------|
| Seller analytics panel (per-listing funnel) | Drives featured listing purchases | Medium | **High** |
| Offer email/push notifications | Faster deal closure | Low–Medium | **High** |
| Listing-scoped messaging | Better buyer experience | Medium | **High** |
| Deprecate or sync `whatsapp_clicks` column | Data consistency | Low | **Medium** |

#### Phase 4C — Scale & Ops (Weeks 5–6)

| Item | Business value | Technical complexity | Priority |
|------|------------------|----------------------|----------|
| Dashboard widget lazy loading / sections | Faster admin at scale | Medium | **Medium** |
| Event table archival (views > 90 days) | DB cost control | Medium | **Medium** |
| Queue media conversions | Faster listing publish | Low | **Medium** |
| Horizon + production monitoring | Queue reliability | Medium | **Medium** |
| CSP + TrustProxies | Security hardening | Low–Medium | **High** |
| Analytics dictionary doc | Cross-team clarity | Low | **Low** |

#### Phase 4D — Growth (Weeks 7–8, optional)

| Item | Business value | Technical complexity | Priority |
|------|------------------|----------------------|----------|
| Subcategory support + category rollup BI | Marketplace depth | High | **Medium** |
| Campaign conversion dashboard | Marketing ROI | Medium | **Medium** |
| A/B featured listing pricing | Revenue optimization | High | **Low** |

---

## Risks

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Seller vs admin metric mismatch erodes trust | High | High | Phase 4A seller stats alignment |
| Broken test suite hides regressions | **Current** | High | Fix migration; add lead tracking tests |
| Event table bloat slows widgets | Medium (6+ months) | Medium | Archival job; consider daily aggregates |
| WhatsApp click inflation | Medium | Medium | Auth/rate limit on tracking endpoint |
| Subcategory launch without rollup | Medium | Medium | Defer subcategories OR ship rollup with launch |
| Paymob webhook misconfiguration | Low | Critical | Staging integration tests; monitoring alerts |
| `points` bypass via `featureWithPoints` | Low | Medium | Route all deductions through `PointService` |
| Duplicate admin widgets | Medium | Low | Audit Filament registration |
| Media sync processing under load | Medium | Medium | Queue conversions |
| No CSP in production | Medium | Medium | Phase 4C security hardening |

---

## Recommended Roadmap (Summary)

```
Now ──────────────────────────────────────────────────────────────► Launch + Scale

Phase 4A (Stabilize)     Phase 4B (Seller)      Phase 4C (Ops)        Phase 4D (Growth)
├─ Fix tests/CI          ├─ Seller analytics    ├─ Lazy widgets       ├─ Subcategories
├─ Widget registration   ├─ Offer notifications ├─ Event archival     ├─ Campaign BI
├─ Analytics alignment   ├─ Listing messages    ├─ Horizon/CSP        └─ Pricing experiments
└─ Security quick wins   └─ WhatsApp sync       └─ Media queues
```

**Gate for public marketing push:** Complete Phase 4A minimum (CI green, seller/admin metric alignment, feature listing fix).

---

## Appendix A — File Reference Map

| Layer | Key paths |
|-------|-----------|
| Lead tracking service | `app/Services/ListingLeadTrackingService.php` |
| Tracking controller | `app/Http/Controllers/ListingController.php` |
| Event models | `app/Models/ListingView.php`, `ListingPhoneClick.php`, `ListingWhatsappClick.php` |
| Lead migrations | `database/migrations/2026_06_11_000001_*`, `2026_06_11_000002_*` |
| Admin widgets | `app/Filament/Admin/Widgets/*` |
| Panel config | `app/Providers/Filament/AdminPanelProvider.php` |
| Payments | `app/Services/PaymobWebhookService.php`, `app/Models/Transaction.php` |
| Seller dashboard | `app/Livewire/Frontend/UserDashboard.php` |
| Phase reports | `docs/reports/phase_3b_*`, `phase_3c_*`, `view_tracking_fix_report.md` |

---

## Appendix B — Approval Gate

This document is **audit-only**. No implementation was performed.

**Awaiting approval** before any Phase 4 work begins. Recommended first approved slice:

1. Fix test-blocking migration (TD-04)
2. Seller dashboard event-table stats (TD-01)
3. `featureWithPoints` bug fix (TD-05)

---

*End of Post-Phase-3 Architecture Audit*
