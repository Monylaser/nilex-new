# Pricing Page Feature Audit — Analytics & Dashboard Capabilities

**Project:** Nilex Marketplace  
**Date:** 2026-06-12  
**Scope:** Read-only audit of all user-facing analytics, dashboard, and reporting features  
**Purpose:** Validate real product capabilities before building Pricing Page UI or Plans Feature Matrix  
**Rule:** Do not advertise, display, or sell any feature that is not actually available to the plan owner today.

---

## Executive Summary

Nilex monetization is **points-based** (`PointPlan` packages on `/pricing`), not subscription-tier analytics. All sellers who pass `auth` + `otp.verified` middleware receive the **same** dashboard analytics — there is no plan-gated analytics tier in code today.

The platform runs a **dual analytics architecture**:

| Layer | Data source | Primary consumers |
|-------|-------------|-------------------|
| Legacy counters | `listings.views_count`, `listings.whatsapp_clicks` | Seller dashboard (primary UI), public listing cards |
| Event tables | `listing_views`, `listing_phone_clicks`, `listing_whatsapp_clicks` | Admin BI widgets + seller "Verified Analytics" aggregate totals |

**Admin BI** (lead funnel, CTR, top listings, category performance, revenue charts) is fully built but gated to **`super_admin` only** via `canView()` on every Filament widget. Regular sellers cannot access it.

**Seller dashboard** exposes basic listing stats, legacy engagement totals, aggregate event-based totals, and one legacy bar chart. Advanced analytics (CTR, funnel, date filters, per-listing events, exports) exist only in admin or not at all.

**Verdict for pricing page:** The current pricing page correctly focuses on **points and listing boosts** with no analytics claims. Any future feature matrix must **exclude or mark "Coming Soon"** for all admin-only and unimplemented analytics capabilities.

---

## Access Model

| Surface | Route | Gate | Who |
|---------|-------|------|-----|
| Seller dashboard | `GET /dashboard` | `auth`, `otp.verified` | All verified sellers |
| Points history | `GET /points/history` | `auth`, `otp.verified` | All verified sellers |
| Pricing page | `GET /pricing` | Public | Everyone |
| Admin panel | `GET /admin` | Filament auth + `User::canAccessPanel()` | `super_admin`, `admin`, `moderator` |
| Admin BI widgets | `/admin` dashboard | `canView()` → `super_admin` only | **Super admin only** |

**Tracking routes (data capture, not analytics UI):**

| Route | Auth | Behavior |
|-------|------|----------|
| `GET /listings/{listing}` | Public | Records view event + increments `views_count` on deduped event |
| `POST /listings/{listing}/reveal-phone` | **Required** | Records phone click event (no legacy column) |
| `POST /listings/{listing}/whatsapp-click` | Public route; user optional | Records WhatsApp event + increments `whatsapp_clicks` on deduped event |

---

## Feature Availability Table

Status codes used below:

| Code | Meaning |
|------|---------|
| **SELLER** | Available to regular logged-in sellers today |
| **ADMIN** | Admin-only (`super_admin` for BI widgets) |
| **PARTIAL** | Implemented incompletely or mixed legacy/event data |
| **BACKEND** | Data/services exist; no user-facing UI for sellers |
| **NONE** | Not implemented |

### 1. Seller Analytics

| # | Feature | Status | Seller access | Evidence |
|---|---------|--------|---------------|----------|
| 1.1 | Seller dashboard hub | **SELLER** | Yes — `/dashboard` | `app/Livewire/Frontend/UserDashboard.php`, `routes/web.php` |
| 1.2 | Listing status breakdown (total / active / pending / rejected) | **SELLER** | Yes | `UserDashboard.php` L92–96; `user-dashboard.blade.php` L40–60 |
| 1.3 | Legacy aggregate views (`views_count` sum) | **SELLER** | Yes — labeled "مشاهدات" | `UserDashboard.php` L97 |
| 1.4 | Legacy aggregate WhatsApp clicks (`whatsapp_clicks` sum) | **SELLER** | Yes — labeled "واتساب" | `UserDashboard.php` L98 |
| 1.5 | Verified Analytics — event aggregate totals | **SELLER** | Yes — lifetime totals only | `SellerListingAnalyticsService`, Blade L73–96 |
| 1.6 | Per-listing metrics in listing table | **PARTIAL** | Yes — legacy views + WhatsApp only | Blade L196–198; no phone, no event counts |
| 1.7 | Per-listing event analytics page | **BACKEND** | No UI | Models + service exist; no route/view |
| 1.8 | Seller CTR / conversion rates | **NONE** | No | CTR only in admin widgets |
| 1.9 | Seller lead funnel visualization | **NONE** | No | `LeadFunnelWidget` is admin-only |
| 1.10 | Date-range filtered seller stats | **NONE** | No | Admin widgets have today/7d/30d; seller stats are lifetime |
| 1.11 | Plan-gated / tiered analytics | **NONE** | N/A | No subscription analytics tiers in codebase |

### 2. Dashboard Statistics

| # | Feature | Status | Seller access | Evidence |
|---|---------|--------|---------------|----------|
| 2.1 | Seller stat cards (6-card grid) | **SELLER** | Yes | `user-dashboard.blade.php` L40–71 |
| 2.2 | Admin platform overview (users, ads, moderation, bans) | **ADMIN** | No | `StatsOverviewWidget.php` |
| 2.3 | Admin monetization KPIs (revenue, transactions) | **ADMIN** | No | `MonetizationOverviewWidget`, `DailyRevenueWidget`, `MonthlyRevenueWidget`, `RevenueAlertWidget` |
| 2.4 | Admin user→paying conversion stats | **ADMIN** | No | `ConversionMetricsWidget.php` |
| 2.5 | Points transaction history | **SELLER** | Yes — ledger, not listing analytics | Route `points.history`; `resources/views/points/history.blade.php` |
| 2.6 | Incoming offers management | **SELLER** | Yes — offer workflow, not funnel analytics | `UserDashboard.php` L82–87, L114+ |

### 3. Event-Based Analytics

| # | Feature | Status | Seller access | Evidence |
|---|---------|--------|---------------|----------|
| 3.1 | Event table schema | **BACKEND** | N/A | `2026_06_11_000001_create_listing_lead_tracking_tables.php` |
| 3.2 | Event write service (dedup rules) | **BACKEND** | Active on user actions | `ListingLeadTrackingService.php` — 24h view dedup, 1h click dedup |
| 3.3 | Seller read service | **PARTIAL** | Aggregate totals only | `SellerListingAnalyticsService.php` |
| 3.4 | Admin platform-wide event reads (date-filtered) | **ADMIN** | No | `LeadFunnelWidget`, `TopListingsWidget`, `CategoryPerformanceWidget` |
| 3.5 | Event models & listing relationships | **BACKEND** | N/A | `ListingView`, `ListingPhoneClick`, `ListingWhatsappClick`; `Listing.php` relations |

### 4. Phone Click Tracking

| # | Feature | Status | Seller access | Evidence |
|---|---------|--------|---------------|----------|
| 4.1 | Capture phone reveal clicks | **PARTIAL** | Events recorded; auth required | `ListingController::revealPhone()`, route `listings.reveal-phone` |
| 4.2 | Guest phone tracking | **PARTIAL** | No — 401 for guests | `revealPhone()` L38–40 |
| 4.3 | Seller visibility — aggregate phone clicks | **SELLER** | Yes — Verified Analytics total only | `$stats['phone_clicks']` |
| 4.4 | Seller visibility — per-listing phone clicks | **NONE** | No | Not in listing table or chart |
| 4.5 | Admin phone click analytics | **ADMIN** | No | All lead-funnel widgets |

### 5. WhatsApp Click Tracking

| # | Feature | Status | Seller access | Evidence |
|---|---------|--------|---------------|----------|
| 5.1 | Capture WhatsApp button clicks | **SELLER** (capture) | Events + legacy increment on deduped click | `ListingController::trackWhatsappClick()` L58–64 |
| 5.2 | Seller legacy WhatsApp total | **SELLER** | Yes | `$stats['clicks']` |
| 5.3 | Seller event WhatsApp total | **SELLER** | Yes | `$stats['whatsapp_clicks_events']` |
| 5.4 | Admin WhatsApp analytics | **ADMIN** | No | Lead-funnel widgets |

### 6. Listing Performance Metrics

| # | Feature | Status | Seller access | Evidence |
|---|---------|--------|---------------|----------|
| 6.1 | Seller bar chart "أداء الإعلانات" | **PARTIAL** | Yes — last 7 paginated listings, legacy data only | Blade L98–112, Chart.js L344–410 |
| 6.2 | Admin top listings table (views, phone, WhatsApp, offers, CTR) | **ADMIN** | No | `TopListingsWidget.php` |
| 6.3 | Admin category performance table | **ADMIN** | No | `CategoryPerformanceWidget.php` |
| 6.4 | Public listing view count on cards/detail | **SELLER** (display) | Public sees `views_count` | `show.blade.php`, `home.blade.php`, `search-results.blade.php` |
| 6.5 | Admin listing CRUD performance columns | **NONE** | No | `ListingTable.php` has no engagement columns |

### 7. CTR Metrics

| # | Feature | Status | Seller access | Evidence |
|---|---------|--------|---------------|----------|
| 7.1 | View → Phone CTR | **ADMIN** | No | `LeadFunnelWidget.php` |
| 7.2 | Phone → WhatsApp CTR | **ADMIN** | No | Same |
| 7.3 | WhatsApp → Offer CTR | **ADMIN** | No | Same |
| 7.4 | Per-listing CTR | **ADMIN** | No | `TopListingsWidget.php` |
| 7.5 | Per-category CTR | **ADMIN** | No | `CategoryPerformanceWidget.php` |
| 7.6 | Registration → payment conversion | **ADMIN** | No | `ConversionMetricsWidget.php` |
| 7.7 | Seller CTR | **NONE** | No | No seller service/view |

### 8. Lead Funnel Analytics

| # | Feature | Status | Seller access | Evidence |
|---|---------|--------|---------------|----------|
| 8.1 | Platform lead funnel (Views → Phone → WhatsApp → Offers) | **ADMIN** | No | `LeadFunnelWidget.php` |
| 8.2 | Funnel date filters (today / 7d / 30d) | **ADMIN** | No | `date-range-filter.blade.php` |
| 8.3 | Offers in funnel context | **ADMIN** | No | Sellers manage offers but see no funnel |
| 8.4 | Seller funnel visualization | **NONE** | No | — |

### 9. Charts and Graphs

| # | Feature | Status | Seller access | Evidence |
|---|---------|--------|---------------|----------|
| 9.1 | Seller listing performance bar chart (legacy) | **PARTIAL** | Yes | Chart.js in `user-dashboard.blade.php` |
| 9.2 | Weekly revenue line chart | **ADMIN** | No | `WeeklyRevenueChart.php` |
| 9.3 | Listings publish rate (7 days) | **ADMIN** | No | `ListingsChart.php` |
| 9.4 | Top 5 categories by listing count | **ADMIN** | No | `CategoriesChartWidget.php` |
| 9.5 | Top 5 governorates by listing count | **ADMIN** | No | `GovernoratesChartWidget.php` |
| 9.6 | Best-selling point plans chart | **ADMIN** | No | `BestSellingPlansChart.php` |
| 9.7 | Revenue alert sparkline | **ADMIN** | No | `RevenueAlertWidget.php` |

All Filament charts registered in `AdminPanelProvider.php` L60–76; all gated by `super_admin`.

### 10. Business Dashboard Features

| # | Feature | Status | Seller access | Evidence |
|---|---------|--------|---------------|----------|
| 10.1 | Filament admin dashboard | **ADMIN** | No | `AdminPanelProvider.php` |
| 10.2 | Monetization BI suite | **ADMIN** | No | Revenue widgets + charts |
| 10.3 | Platform health stats | **ADMIN** | No | `StatsOverviewWidget` |
| 10.4 | Lead/engagement BI suite | **ADMIN** | No | Funnel + top listings + category performance |
| 10.5 | Geographic/category listing distribution charts | **ADMIN** | No | Governorates + categories charts (listing counts, not engagement) |
| 10.6 | Seller business dashboard | **SELLER** | Yes | `/dashboard` — listings, offers, points, basic stats, one chart |
| 10.7 | Listing boost / feature with points | **SELLER** | Yes | `UserDashboard::featureListing()`, `Listing::featureWithPoints()` |

### 11. Reporting Features

| # | Feature | Status | Seller access | Evidence |
|---|---------|--------|---------------|----------|
| 11.1 | CSV/PDF analytics export | **NONE** | No | No export actions in widgets or resources |
| 11.2 | Dedicated analytics report pages | **NONE** | No | No Filament Pages for analytics reports |
| 11.3 | Admin audit log | **ADMIN** | No | `AuditLogResource.php` — operational, not listing BI |
| 11.4 | Admin activity log (Spatie) | **ADMIN** | No | `ActivityLogResource.php` |
| 11.5 | Internal dev reports | **NONE** (docs) | N/A | `docs/reports/*.md` |
| 11.6 | Category page view counter | **ADMIN** | No | `CategoryController` increments `categories.views_count`; separate from listing analytics |

---

## Summary Counts

| Status | Count | Pricing implication |
|--------|-------|---------------------|
| **SELLER** (fully available) | 14 | May advertise if relevant to points/value prop |
| **PARTIAL** (incomplete seller experience) | 8 | Advertise with accurate scope only; do not oversell |
| **ADMIN** | 22 | **Exclude** from seller pricing matrix |
| **BACKEND** | 5 | **Exclude** or **Coming Soon** |
| **NONE** | 12 | **Exclude** or **Coming Soon** |

---

## Current Pricing Page Assessment

**File:** `resources/views/frontend/pricing.blade.php`  
**Controller:** `HomeController::pricing()` → loads `PointPlan::active()`

### What the pricing page sells today (verified accurate)

| Claim / section | Verified? | Notes |
|-----------------|-----------|-------|
| Point plan packages (points + EGP price) | ✅ Yes | Dynamic from `point_plans` table |
| Earn points (account, phone verify, referral, listing, daily login) | ✅ Yes | Matches `PointService` gamification rules |
| Spend points on listing boosts (1/3/7/14 days) | ✅ Yes | Matches `Listing::featureCost()` tiers |
| First featured ad free | ⚠️ Verify business rule | Trust strip claim — confirm against `PointService` / promo logic before retaining |
| Points valid 90 days | ⚠️ Verify business rule | Trust strip claim — confirm against points expiry logic |
| Direct payment for companies | ⚠️ Verify availability | Trust strip claim — confirm payment flow exists |

### What the pricing page does NOT claim (correct)

The current pricing page contains **zero analytics marketing claims**. No mention of dashboards, CTR, funnels, reports, or advanced charts. This is the correct baseline.

---

## Pricing Matrix Recommendations

Before adding a Plans Feature Matrix or analytics-related value propositions, apply this decision tree:

```
Is the feature accessible to a regular seller at /dashboard today?
├── NO → Is it partially built (backend or admin-only)?
│   ├── YES → Mark "Coming Soon" OR exclude entirely
│   └── NO  → Exclude entirely
└── YES → Is it fully functional (not PARTIAL)?
    ├── YES → May include in matrix
    └── NO  → Describe accurately with scope limits OR exclude
```

**Safer default:** When in doubt, **exclude** rather than "Coming Soon" — avoids setting buyer expectations for unfinished admin tooling.

### ✅ Safe to advertise (seller-verified today)

These reflect real capabilities all verified sellers receive (no plan gate):

| Feature | How to describe accurately |
|---------|---------------------------|
| Seller dashboard | Manage listings, view status, respond to offers |
| Basic listing stats | Total views and WhatsApp clicks (legacy counters) |
| Verified engagement totals | Aggregate event-based views, phone clicks, WhatsApp clicks |
| Listing performance chart | Bar chart comparing views vs WhatsApp for recent listings |
| Per-listing view & WhatsApp counts | Shown in listing table on dashboard |
| Listing boost with points | Feature listings for increased visibility (1–14 day tiers) |
| Points balance & transaction history | Track point purchases and spending |

### ⚠️ Advertise with scope limits only (PARTIAL)

| Feature | Limitation to disclose |
|---------|------------------------|
| Verified Analytics | Lifetime aggregate totals only — no date filters, no per-listing breakdown |
| Phone click tracking | Aggregate total only; requires buyer login to reveal phone; not shown per listing |
| Performance chart | Last 7 listings only; legacy counters; excludes phone clicks and event data |
| Legacy vs event totals | Two stat blocks may show different numbers on historical listings |

### 🚫 Exclude from pricing matrix (admin-only)

Do not sell or imply these are included in any seller plan:

- Lead funnel analytics (Views → Phone → WhatsApp → Offers)
- CTR / conversion rate metrics
- Top listings performance ranking
- Category performance analytics
- Date-range analytics filters (today / 7d / 30d)
- Revenue / monetization dashboards
- Platform health / moderation stats
- User→paying conversion metrics
- Geographic / category distribution charts
- Best-selling plans chart
- Weekly revenue charts
- Admin audit / activity logs

### 🚫 Exclude or "Coming Soon" (not implemented for sellers)

| Feature | Recommendation |
|---------|----------------|
| Per-listing event analytics page | **Exclude** (no UI exists) |
| Seller lead funnel | **Exclude** |
| Seller CTR metrics | **Exclude** |
| Date-range filtered seller stats | **Exclude** |
| CSV/PDF report export | **Exclude** |
| Dedicated analytics report pages | **Exclude** |
| Plan-tier analytics (Basic vs Pro) | **Exclude** (no tier system exists) |

If product roadmap intends to ship seller analytics upgrades, use a separate **"Coming Soon"** section with no plan tie-in — never bundle unbuilt features into a purchasable tier.

---

## Proposed Feature Matrix (If Added to Pricing Page)

This matrix reflects **today's reality only**. Empty cells mean the feature is not available to that audience.

| Feature | Free seller | Points buyer | Admin |
|---------|:-----------:|:------------:|:-----:|
| Seller dashboard | ✅ | ✅ | — |
| Listing status stats | ✅ | ✅ | — |
| Legacy views / WhatsApp totals | ✅ | ✅ | — |
| Verified Analytics (aggregate) | ✅ | ✅ | — |
| Per-listing views / WhatsApp | ✅ | ✅ | — |
| Performance bar chart (basic) | ✅ | ✅ | — |
| Listing boost (points) | ✅ | ✅ | — |
| Points history | ✅ | ✅ | — |
| Per-listing phone clicks | — | — | — |
| Date-range filters | — | — | ✅ |
| Lead funnel | — | — | ✅ |
| CTR metrics | — | — | ✅ |
| Top listings / category performance | — | — | ✅ |
| Revenue / monetization BI | — | — | ✅ |
| Analytics export (CSV/PDF) | — | — | — |

> **Note:** There is no "Points buyer vs Free seller" analytics difference today. Points unlock **listing boosts**, not analytics tiers. Any matrix column implying premium analytics for higher plans would be **false advertising** unless a tier system is built first.

---

## Known Data Integrity Caveats (Do Not Overclaim)

Documented in `docs/reports/td_01_seller_admin_analytics_alignment_audit.md`:

1. **Dual sources** — Seller dashboard shows both legacy columns and event-table totals. Numbers may diverge on pre-fix historical data.
2. **Views** — Legacy and event counts align for new traffic post view-tracking fix; historical inflation may remain in legacy columns.
3. **WhatsApp** — Dual-write is active (`trackWhatsappClick()` increments `whatsapp_clicks` on deduped events). Pre-fix legacy data may still lag event totals.
4. **Phone** — No legacy column; only event-table aggregate visible to sellers.
5. **Admin vs seller semantics** — Admin applies date filters and deduplicated event counts; seller sees lifetime totals from both legacy sums and event sums — not directly comparable.

Pricing copy must not claim "real-time" or "accurate to the event" unless referring specifically to the Verified Analytics block — and even then, without date filtering.

---

## Test Coverage (Confirms Intended Behavior)

| Test file | What it validates |
|-----------|-------------------|
| `tests/Feature/Dashboard/SellerListingAnalyticsServiceTest.php` | Event aggregation service scoping |
| `tests/Feature/Dashboard/SellerDashboardAnalyticsUiTest.php` | Verified Analytics UI presence |
| `tests/Feature/Dashboard/UserDashboardStatsTest.php` | Legacy stat cards |
| `tests/Feature/Listings/ListingWorkflowTest.php` | View / phone / WhatsApp tracking + dedup |

---

## Audit Conclusion

| Question | Answer |
|----------|--------|
| Can we add analytics features to the pricing matrix today? | **Only the 7 seller-verified items** in the "Safe to advertise" section |
| Are there premium analytics tiers to sell? | **No** — points buy visibility boosts, not analytics |
| Is admin BI sellable to sellers? | **No** — `super_admin` only, no seller exposure path |
| Is the current pricing page compliant? | **Yes** — no false analytics claims |
| Blocker before pricing UI work? | **None** — this audit is complete; proceed using the recommendations above |

---

## Related Documentation

- `docs/reports/td_01_seller_admin_analytics_alignment_audit.md` — Dual-source divergence analysis
- `docs/reports/td_01_phase1_implementation_report.md` — Event tracking Phase 1
- `docs/reports/td_01_phase2_implementation_report.md` — SellerListingAnalyticsService
- `docs/reports/td_01_phase2b_seller_analytics_ui_report.md` — Verified Analytics UI
- `docs/reports/post_phase3_architecture_audit.md` — Post-Phase 3 architecture
- `docs/reports/phase_3b_top_listings_widget_report.md` — Admin top listings
- `docs/reports/phase_3c_category_performance_widget_report.md` — Admin category performance

---

## Post-Implementation Note (2026-06-12)

Pricing page UI + feature matrix implemented per `docs/reports/pricing_page_ui_feature_matrix_report.md`.

The comparison matrix is **UI/marketing positioning only** (`config/pricing.php`). Backend plan-tier enforcement was **not** built. The analytics marketing section on the pricing page lists **seller-verified features only** (views, phone, WhatsApp, dashboard stats, performance chart) — not CTR or lead funnel.

**Action required before matrix checkmarks become contractual:** implement plan-tier entitlements aligned with this audit.

---

*Audit performed read-only. No application code, migrations, or pricing page changes were made as part of this report.*
