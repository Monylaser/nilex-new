# Seller Dashboard Compliance + Admin/Seller Feature Separation Audit

**Project:** Nilex Marketplace  
**Date:** 2026-06-12  
**Mode:** Read-only audit — no code, test, or configuration changes  
**Scope:** Seller dashboard, seller-facing analytics, marketing alignment, admin/seller separation

---

## 1. Executive Summary

This audit reviewed the seller dashboard (`UserDashboard` Livewire), seller analytics services, seller routes/navigation, pricing/marketing copy, Filament admin widgets, permissions, and existing test coverage.

### Key conclusions

| Area | Result |
|------|--------|
| Admin-only analytics exposure to sellers | **No accidental exposure found** |
| Seller can access forbidden BI (CTR, funnel, revenue, category, top listings) | **Blocked** — no seller routes or UI |
| Marketed seller dashboard features exist in production | **Yes** — all eight pricing-page capabilities are implemented |
| Admin vs seller data isolation | **Enforced** — seller queries scoped to `user_id`; admin widgets are platform-wide |
| Marketing vs implementation alignment | **Partial gaps** — pricing matrix implies tier gating; code grants all analytics to any OTP-verified seller |

### Risk summary

| Severity | Count | Theme |
|----------|-------|-------|
| HIGH | 0 | No seller path to admin-only data |
| MEDIUM | 3 | Tier-matrix vs all-access implementation; dual metric sources; chart data source mismatch |
| LOW | 4 | Navigation gaps, chart labeling, bilingual UI inconsistency, historical counter drift |

### Verdict: **PASS WITH RISKS**

Sellers cannot access admin-only analytics. All seller-marketed dashboard capabilities are real and functional. Remaining risks are **marketing ambiguity** (pricing matrix tier columns vs “all verified sellers” copy), **dual-source metric display** (legacy columns + event tables shown side-by-side), and **missing authorization/UI test coverage** — not admin data leakage.

---

## 2. Seller Features Inventory

### 2.1 Core seller surface

| Feature | Location | Route / Entry | Middleware | Seller Access | Works |
|---------|----------|---------------|------------|---------------|-------|
| Seller dashboard | `app/Livewire/Frontend/UserDashboard.php` | `GET /dashboard` (`dashboard`) | `auth`, `otp.verified` | All OTP-verified sellers | Yes |
| Dashboard view | `resources/views/livewire/frontend/user-dashboard.blade.php` | Same | Same | Same | Yes |
| Listing management (list/delete/feature) | `UserDashboard` actions | Livewire methods | Auth-scoped queries | Owner only (`user_id`) | Yes |
| Incoming offers (accept/reject) | `UserDashboard` | Livewire methods | `receiver_id = Auth::id()` | Listing owner | Yes |
| Create listing CTA | Dashboard + nav | `listings.create` | `auth`, `otp.verified` | Yes | Yes |
| Points balance display | Dashboard welcome card | Inline `$user->points` | — | Yes | Yes |
| Points history | `resources/views/points/history.blade.php` | `GET /points/history` | `auth`, `otp.verified` | Yes | Yes |
| Profile | `ProfileController` | `profile.edit` | `auth`, `otp.verified` | Yes | Yes |
| Make offer (buyer → seller) | `ListingController::makeOffer` | `POST /listings/{listing}/offer` | `auth`, `otp.verified` | Buyers only | Yes |
| Payment top-up | `PaymentController` | `payment.checkout` | `auth`, `otp.verified` | Yes | Yes |
| Lead tracking (write path) | `ListingLeadTrackingService` | Public listing show + AJAX endpoints | Public / auth for clicks | N/A (buyer actions) | Yes |

### 2.2 Seller analytics capabilities (implemented)

| Capability | UI Section | Service / Query | Seller-Scoped |
|------------|--------------|-----------------|---------------|
| Legacy total views | Stats card “مشاهدات” | `Listing::where('user_id')->sum('views_count')` | Yes |
| Legacy WhatsApp clicks | Stats card “واتساب” | `sum('whatsapp_clicks')` | Yes |
| Event views | “Verified Analytics → Event Views” | `SellerListingAnalyticsService::totalViewsForUser()` → `listing_views` via `whereHas listing.user_id` | Yes |
| Phone clicks | “Phone Clicks” | `totalPhoneClicksForUser()` → `listing_phone_clicks` | Yes |
| Event WhatsApp clicks | “WhatsApp Clicks” | `totalWhatsappClicksForUser()` → `listing_whatsapp_clicks` | Yes |
| Listing status breakdown | الكل / نشط / مراجعة / مرفوض | Direct `Listing` counts by `status` | Yes |
| Performance chart | “أداء الإعلانات” | Chart.js; `views_count` + `whatsapp_clicks` from paginated listings | Yes (owner listings only) |
| Per-listing mini stats | Listings table/cards | `$listing->views_count`, `$listing->whatsapp_clicks` | Yes |

### 2.3 Seller features marketed on pricing page (`/pricing`)

| Marketed feature (`lang/en/ui.php`) | Implemented | Notes |
|-------------------------------------|-------------|-------|
| Total Listing Views | Yes | Legacy counter card |
| Event Views Analytics | Yes | Event table via `SellerListingAnalyticsService` |
| Phone Click Tracking | Yes | Event table |
| WhatsApp Click Tracking | Yes | Legacy + event cards |
| Listing Status Statistics | Yes | 4 status cards |
| Listing Performance Overview | Yes | Bar chart (legacy data) |
| Points History | Yes | Route exists; linked from `layouts/navigation.blade.php` points badge, not dashboard body |
| Offer Management | Yes | Incoming offers section + accept/reject |

Pricing copy (`ui.pricing.dashboard_subtitle`) states: *“Every verified seller account includes these features today — no plan tier required.”* This matches code behavior but **contradicts** tier columns in `config/pricing.php` for event analytics and charts (see §9).

---

## 3. Dashboard Statistics Verification

| Display Name | Data Source | Service / Query | Table(s) | Access Scope | Accuracy Risk |
|--------------|-------------|-----------------|----------|--------------|---------------|
| **الكل** (Total listings) | `UserDashboard::render()` | `Listing::where('user_id', $id)->count()` | `listings` | Owner only | Low |
| **نشط** (Active) | Same | `where('status', STATUS_PUBLISHED)` | `listings` | Owner only | Low |
| **مراجعة** (Pending) | Same | `where('status', STATUS_PENDING)` | `listings` | Owner only | Low |
| **مرفوض** (Rejected) | Same | `where('status', STATUS_REJECTED)` | `listings` | Owner only | Low |
| **مشاهدات** (Views) | Legacy aggregate | `sum('views_count')` | `listings.views_count` | Owner only | **Medium** — may exceed event count due to pre-fix inflation; deduped event path gates increments |
| **واتساب** (WhatsApp) | Legacy aggregate | `sum('whatsapp_clicks')` | `listings.whatsapp_clicks` | Owner only | **Medium** — synced on new clicks (`ListingController::trackWhatsappClick` dual-writes); historical drift possible |
| **Event Views** | Event aggregate | `SellerListingAnalyticsService::totalViewsForUser()` | `listing_views` JOIN `listings` | Owner listings only | Low — deduped (24h user/IP) |
| **Phone Clicks** | Event aggregate | `totalPhoneClicksForUser()` | `listing_phone_clicks` | Owner listings only | Low — deduped (1h user) |
| **WhatsApp Clicks** (event) | Event aggregate | `totalWhatsappClicksForUser()` | `listing_whatsapp_clicks` | Owner listings only | Low — deduped (1h user) |
| **Points balance** | User model | `$user->points` | `users.points` | Self only | Low |
| **Per-listing views/clicks** | Listing row | Column read | `listings` | Owner rows in paginated query | Medium — legacy columns only |

### Dual-source note

Sellers simultaneously see **legacy counters** (top stats row) and **event-based totals** (“Verified Analytics” section). These measure differently:

- Legacy: lifetime column sums, gated increments on deduped writes.
- Event: raw row counts in event tables (also deduped at write time).

Numbers **will often differ** between the two rows for the same metric. This is not admin leakage but may confuse sellers (MEDIUM presentation risk).

---

## 4. Dashboard Charts Verification

| Chart | Location | Data Source | Query / Aggregation | Time Range | Ownership Filter | Accuracy |
|-------|----------|-------------|---------------------|------------|------------------|----------|
| **أداء الإعلانات** (Listing performance bar chart) | `user-dashboard.blade.php` L99–111, Chart.js L365–404 | Legacy listing columns | `collect($listings->items())->take(7)` → `views_count`, `whatsapp_clicks`; labels truncated titles | Current paginated page (default latest 10, take 7) | `Listing::where('user_id', $user->id)` in component | **Medium** |

### Chart findings

1. **Misleading subtitle:** Label says “آخر 7 إعلانات” (last 7 listings) but data is the **first 7 items on the current pagination page** of `latest()` listings — not performance-ranked or globally last 7.
2. **Legacy-only data:** Chart does **not** use event tables or phone clicks; only `views_count` and `whatsapp_clicks`.
3. **No date filter:** Lifetime totals per listing, not time-windowed like admin widgets.
4. **Marketing alignment:** Pricing advertises “Analytics Charts” for Pro Seller+ in the feature matrix, but the chart renders for **all** OTP-verified sellers (no plan check).

---

## 5. Admin vs Seller Feature Separation Table

| Feature | Location | Permission / Gate | Visible To Seller? | Route Accessible By Seller? | Linked In Seller UI? | Referenced In Seller Marketing? | Risk |
|---------|----------|-----------------|-------------------|----------------------------|----------------------|--------------------------------|------|
| CTR Analytics (Top/Category widgets) | `TopListingsWidget`, `CategoryPerformanceWidget` | `canView()` → `super_admin` only | No | No (`/admin` → 403) | No | Matrix only — **Admin Only** badge | None |
| Conversion Analytics | `ConversionMetricsWidget` | `super_admin` | No | No | No | Not advertised as available | None |
| Lead Funnel Analytics | `LeadFunnelWidget` | `super_admin` | No | No | No | Matrix — **Coming Soon** | None |
| Revenue Analytics | `MonetizationOverviewWidget`, `DailyRevenueWidget`, `MonthlyRevenueWidget`, `WeeklyRevenueChart` | `super_admin` | No | No | No | Matrix — **Admin Only** | None |
| Revenue Dashboard / Alerts | `RevenueAlertWidget` | `super_admin` | No | No | No | No | None |
| Business Intelligence / Platform Stats | `StatsOverviewWidget` | `super_admin` | No | No | No | No | None |
| Category Performance Analytics | `CategoryPerformanceWidget` | `super_admin` | No | No | No | Matrix — **Admin Only** | None |
| Top Listings Analytics | `TopListingsWidget` | `super_admin` | No | No | No | Matrix — **Admin Only** | None |
| Advanced Reporting | Not implemented | — | No | No | No | Matrix — **Coming Soon** (`monthly_reports`) | None |
| Advanced Seller Insights / CTR | Not implemented | — | No | No | No | Matrix — **Coming Soon** (`basic_ctr`, `advanced_ctr`) | None |
| Executive Reporting | Not implemented | — | No | No | No | No | None |
| Listings chart (platform) | `ListingsChart` | `super_admin` | No | No | No | No | None |
| Categories chart | `CategoriesChartWidget` | `super_admin` | No | No | No | No | None |
| Governorates chart | `GovernoratesChartWidget` | `super_admin` | No | No | No | No | None |
| Best-selling plans chart | `BestSellingPlansChart` | `super_admin` | No | No | No | No | None |
| Filament resources (users, moderation, audit logs, etc.) | `app/Filament/Admin/Resources/*` | Filament Shield + `canAccessPanel()` | No | No (403) | Admin nav link only for `super_admin`/`admin` roles | No | None |
| Seller event analytics | `SellerListingAnalyticsService` + dashboard | None (OTP-verified seller) | Yes | Yes | Yes | Yes — verified capabilities section | None |
| Seller legacy analytics | `UserDashboard` sums | None | Yes | Yes | Yes | Yes | None |

### Panel access control

```php
// app/Models/User.php
public function canAccessPanel(Panel $panel): bool
{
    return $this->hasRole('super_admin')
        || $this->hasRole('admin')
        || $this->hasRole('moderator');
}
```

- Verified sellers without these roles receive **403** on `/admin` (confirmed in `tests/Feature/NilexAuthPointsTest.php`).
- All BI widgets add a second layer: `canView()` requires **`super_admin`** specifically.
- Admin navigation link (`layouts/navigation.blade.php`) is shown only to `super_admin` or `admin` — not to plain sellers.

---

## 6. Navigation Audit

### Seller-accessible routes (authenticated + OTP-verified)

| Route Name | Path | Purpose |
|------------|------|---------|
| `dashboard` | `/dashboard` | Seller dashboard |
| `listings.create` | `/listings/create` | New listing form |
| `listings.store` | `POST /listings/store` | Save listing |
| `points.history` | `/points/history` | Points ledger |
| `profile.edit` | `/profile` | Profile settings |
| `profile.update` | `PATCH /profile` | Update profile |
| `profile.destroy` | `DELETE /profile` | Delete account |
| `payment.checkout` | `POST /payment/checkout` | Buy points |
| `payment.callback` | `/payment/callback` | Payment return |
| `messages.store` | `POST /messages` | Messaging |
| `listings.offer` | `POST /listings/{listing}/offer` | Submit price offer |

### Public / buyer routes (relevant to seller analytics writes)

| Route Name | Path | Purpose |
|------------|------|---------|
| `listings.show` | `/listings/{listing}` | View listing + `recordView()` |
| `listings.reveal-phone` | `POST /listings/{listing}/reveal-phone` | Phone reveal + `recordPhoneClick()` |
| `listings.whatsapp-click` | `POST /listings/{listing}/whatsapp-click` | WhatsApp click tracking |

### Navigation menus reviewed

| Menu | File | Seller Links | Admin Analytics Links |
|------|------|--------------|----------------------|
| Breeze app nav | `layouts/navigation.blade.php` | Dashboard (logo), points badge → `points.history`, profile | `/admin` only if `super_admin` or `admin` |
| Frontend public nav | `layouts/frontend.blade.php` | `dashboard`, `listings.create` | None |
| App footer | `layouts/app.blade.php` | `dashboard` | None |
| Public footer | `components/footer.blade.php` | `dashboard`, `pricing` | None |
| Dashboard body | `user-dashboard.blade.php` | `listings.create`, listing show links | **None** |

### Navigation gaps (LOW)

- **Points history** is marketed on `/pricing` but has **no direct link** on the dashboard page itself — only via the points badge in `layouts/navigation.blade.php`.
- **No hidden links** to `/admin` or admin widget endpoints found in seller views.

---

## 7. Permissions Audit

### Layers evaluated

| Layer | Mechanism | Seller (no admin role) | Verified seller | Company / business account |
|-------|-----------|------------------------|-----------------|---------------------------|
| Filament panel | `User::canAccessPanel()` | 403 | 403 (unless role assigned) | 403 (no special bypass) |
| Admin widgets | `Widget::canView()` → `super_admin` | Hidden / inaccessible | Hidden | Hidden |
| Filament Shield | Role permissions on resources | N/A — panel blocked | N/A | N/A |
| Seller dashboard | `auth` + `otp.verified` | Blocked until OTP | Allowed | Allowed (same as any seller) |
| Listing actions | `where('user_id', Auth::id())` / `receiver_id` | Owner-scoped | Owner-scoped | Owner-scoped |
| Policies (`ListingPolicy`, etc.) | Shield permission strings | Apply to Filament admin context | — | — |

### Policies

`app/Policies/ListingPolicy.php` and sibling policies gate **Filament admin resources**, not the frontend Livewire dashboard. Seller dashboard relies on **query scoping** in `UserDashboard` methods rather than policy calls.

### Gaps

- No test asserts that `admin` or `moderator` roles cannot see `super_admin`-only widgets (role separation within admin is untested).
- No test asserts cross-seller IDOR on `acceptOffer`, `rejectOffer`, `deleteListing`, or `featureListing`.

---

## 8. Forbidden Analytics Audit

| Forbidden Item | Exists? | Admin Only? | Hidden from sellers? | Accidentally Exposed? | Mentioned in Seller UI? |
|----------------|---------|-------------|----------------------|----------------------|-------------------------|
| CTR Analytics | Yes (admin widgets compute CTR) | Yes (`super_admin`) | Yes | No | No — pricing matrix shows **Coming Soon** / **Admin Only** |
| Conversion Analytics | Yes (`ConversionMetricsWidget`) | Yes | Yes | No | No |
| Lead Funnel Analytics | Yes (`LeadFunnelWidget`) | Yes | Yes | No | No — matrix **Coming Soon** |
| Revenue Analytics | Yes (monetization + revenue widgets) | Yes | Yes | No | No — matrix **Admin Only** |
| Revenue Dashboard | Yes (revenue widget suite) | Yes | Yes | No | No |
| Business Intelligence Dashboard | Partial (`StatsOverviewWidget`, charts) | Yes | Yes | No | Matrix **Coming Soon** for `business_dashboard` |
| Category Performance Analytics | Yes (`CategoryPerformanceWidget`) | Yes | Yes | No | Matrix **Admin Only** |
| Top Listings Analytics | Yes (`TopListingsWidget`) | Yes | Yes | No | Matrix **Admin Only** |
| Advanced Reporting | No dedicated seller or export system | — | — | No | Matrix **Coming Soon** |
| Advanced Seller Insights | No | — | — | No | Matrix **Coming Soon** |
| Executive Reporting | No | — | — | No | No |

### Seller dashboard wording check

The seller dashboard uses **“Verified Analytics”** and **“Event-Based Analytics”** — these refer to the seller-scoped `SellerListingAnalyticsService`, not admin BI. No forbidden terms (CTR, funnel, revenue, category performance, top listings) appear in `user-dashboard.blade.php`.

---

## 9. Risk Assessment

### HIGH — None

No evidence that authenticated sellers can read platform-wide admin aggregates, revenue data, category BI, top-listings rankings, or lead-funnel metrics.

### MEDIUM

| ID | Finding | Evidence | Impact |
|----|---------|----------|--------|
| M1 | **Pricing matrix tier columns vs all-access implementation** | `config/pricing.php` marks `event_views`, `phone_clicks`, `whatsapp_clicks` as Growth+ and `analytics_charts` as Pro Seller+; `UserDashboard` shows all metrics/charts to every OTP-verified seller with no plan check | Sellers on Starter plan receive features matrix marks as tier-exclusive; matrix_subtitle mitigates but matrix checkmarks still imply gating |
| M2 | **Dual metric sources shown together** | Legacy row + “Verified Analytics” event row for views/WhatsApp | Sellers may think analytics are broken when numbers differ |
| M3 | **Chart uses legacy columns only** | Chart.js reads `views_count`/`whatsapp_clicks`; event tables ignored | “Verified Analytics” branding adjacent to chart implies consistency; chart may disagree with event totals |

### LOW

| ID | Finding | Evidence |
|----|---------|----------|
| L1 | Points history not linked from dashboard body | Marketed on pricing; only in nav points badge |
| L2 | Chart subtitle “آخر 7 إعلانات” misleading | Takes 7 from paginated `latest()` page |
| L3 | English labels in Arabic-primary dashboard | “Verified Analytics”, “Event Views”, etc. |
| L4 | Historical legacy counter drift | Pre-fix view inflation; pre-dual-write WhatsApp zeros |

---

## 10. Missing Test Coverage

Existing tests (good coverage):

| Test File | Covers |
|-----------|--------|
| `tests/Feature/Dashboard/UserDashboardStatsTest.php` | Legacy stats, status breakdown, `featureListing` |
| `tests/Feature/Dashboard/SellerListingAnalyticsServiceTest.php` | Event aggregation, cross-seller isolation |
| `tests/Feature/Dashboard/SellerDashboardAnalyticsUiTest.php` | Event UI rendering, legacy coexistence |
| `tests/Feature/NilexAuthPointsTest.php` | Seller blocked from `/admin` |
| `tests/Feature/Pricing/PricingPageTest.php` | Marketing does not advertise forbidden analytics |
| `tests/Feature/Pricing/PricingMarketingComplianceTest.php` | Matrix badges for restricted features |
| `tests/Feature/Pricing/PricingMatrixComplianceTest.php` | Config matrix states |
| `tests/Feature/Listings/ListingWorkflowTest.php` | Dual-write for views/WhatsApp clicks |

### Not covered (report only — do not implement)

| Gap | Recommended future test |
|-----|-------------------------|
| Seller HTTP access to `/admin` widgets/API | Assert 403 for seller on `/admin` and widget Livewire endpoints |
| IDOR on `deleteListing`, `acceptOffer`, `rejectOffer`, `featureListing` | Acting as user B calling user A’s listing/offer IDs → 404 |
| `points.history` route | Auth + OTP gate; only own `pointTransactions` |
| Dashboard route gates | Unauthenticated → redirect; unverified OTP → redirect to `otp.notice` |
| Chart data contract | Assert chart dataset uses owner listings only |
| Tier gating (if product requires it) | Starter seller should/should not see event section per business rule |
| Admin role `moderator` panel access vs `super_admin` widget visibility | Panel 200 but BI widgets hidden |
| Consistency test: legacy sum vs event sum after controlled clicks | End-to-end engagement simulation |
| Navigation: seller UI never renders `/admin` link without role | Blade assertion for plain seller session |

---

## 11. PASS / FAIL Verdict

### **PASS WITH RISKS**

**Rationale:**

- **Rule 1 (see it → have access):** All seller-visible dashboard features are backed by working routes, services, and owner-scoped queries.
- **Rule 2 (click it → works):** Dashboard actions scope by `user_id` / `receiver_id`; seller routes exist behind `auth` + `otp.verified`.
- **Rule 3 (statistics → verified source):** Every stat traced to `listings` columns or event tables via `SellerListingAnalyticsService`; accuracy caveats documented above.
- **Rule 4 (marketing → exists or labeled):** Pricing page marks unavailable features as **Coming Soon** or **Admin Only**; seller dashboard section lists only verified capabilities. Residual ambiguity is tier-matrix vs all-access code (M1), not false advertising of admin BI.

**Would become FAIL if:**

- Sellers could reach `/admin` BI or platform-wide aggregates (not observed).
- Marketed seller features were missing entirely (not observed).
- Forbidden analytics appeared in seller dashboard without labeling (not observed).

**Recommended follow-ups (out of audit scope):**

1. Align pricing matrix tier checkmarks with implementation **or** enforce plan-based gating in `UserDashboard`.
2. Consolidate or clearly label legacy vs event metrics to reduce dual-source confusion.
3. Feed chart from event tables (or add phone-click series) for consistency with “Verified Analytics”.
4. Add IDOR and route-gate tests listed in §10.

---

## Appendix A — Files Reviewed

| Category | Paths |
|----------|-------|
| Seller dashboard | `app/Livewire/Frontend/UserDashboard.php`, `resources/views/livewire/frontend/user-dashboard.blade.php` |
| Seller analytics | `app/Services/SellerListingAnalyticsService.php`, `app/Services/ListingLeadTrackingService.php` |
| Tracking write path | `app/Http/Controllers/ListingController.php` |
| Routes | `routes/web.php` |
| Navigation | `resources/views/layouts/navigation.blade.php`, `layouts/frontend.blade.php`, `layouts/app.blade.php`, `components/footer.blade.php` |
| Points | `resources/views/points/history.blade.php` |
| Marketing | `config/pricing.php`, `resources/views/frontend/pricing.blade.php`, `resources/views/frontend/pricing/_feature-matrix.blade.php`, `lang/en/ui.php`, `lang/ar/ui.php` |
| Admin panel | `app/Providers/Filament/AdminPanelProvider.php`, `app/Filament/Admin/Widgets/*` |
| Auth / permissions | `app/Models/User.php`, `config/filament-shield.php`, `app/Policies/*` |
| Tests | `tests/Feature/Dashboard/*`, `tests/Feature/Pricing/*`, `tests/Feature/NilexAuthPointsTest.php`, `tests/Feature/Listings/ListingWorkflowTest.php` |

---

*End of audit report.*
