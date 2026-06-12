# Seller Dashboard UX & Conversion Audit Report

**Project:** Nilex Marketplace  
**Date:** 2026-06-12  
**Mode:** Read-only audit — no code, tests, routes, or configuration changes  
**Scope:** Seller dashboard UX, information architecture, analytics presentation, conversion/monetization/retention opportunities

**Primary files reviewed:**

| Category | Paths |
|----------|-------|
| Dashboard component | `app/Livewire/Frontend/UserDashboard.php` |
| Dashboard view | `resources/views/livewire/frontend/user-dashboard.blade.php` |
| Seller analytics | `app/Services/SellerListingAnalyticsService.php` |
| Lead tracking (write path) | `app/Services/ListingLeadTrackingService.php`, `app/Http/Controllers/ListingController.php` |
| Navigation | `resources/views/layouts/navigation.blade.php`, `layouts/frontend.blade.php`, `layouts/app.blade.php` |
| Monetization context | `config/pricing.php`, `lang/ar/ui.php`, `resources/views/points/history.blade.php` |
| Admin reference (safe BI boundaries) | `app/Filament/Admin/Widgets/*` |
| Tests | `tests/Feature/Dashboard/*` |
| Prior audits | `docs/reports/seller_dashboard_compliance_audit.md`, `docs/reports/business_plan_marketing_audit.md` |

---

## Executive Summary

The Nilex seller dashboard is a **functional, owner-scoped control center** that successfully delivers listing management, incoming offer handling, points-based featuring, and a dual-layer analytics surface (legacy counters + event-based verified metrics). The UI is visually cohesive, mobile-responsive, and aligned with pricing-page marketing claims.

**However, the dashboard is optimized for *displaying* data, not for *driving seller action*.** Sellers see raw counts without derived insights (conversion rate, contact rate, trends, best/worst performers). Analytics are split across two unexplained sections with overlapping metrics. The performance chart uses legacy data only, with a misleading subtitle. Monetization paths (top-up, featuring, business upgrade) exist elsewhere but are **not contextualized** inside the dashboard at moments of high intent.

### Overall assessment

| Dimension | Score | Summary |
|-----------|-------|---------|
| **Dashboard UX** | ⚠️ Good foundation, weak guidance | Clean layout; lacks explanations, empty-state coaching, and action-oriented insights |
| **Information Architecture** | ⚠️ Fragmented analytics | Two parallel stat rows + chart + per-listing mini-stats without hierarchy or narrative |
| **Analytics Presentation** | ⚠️ Raw counts only | No rates, trends, comparisons, or performance rankings |
| **Conversion Opportunities** | ❌ Under-exploited | High view/click volume not linked to featuring, optimization, or credit purchase |
| **Monetization Opportunities** | ❌ Under-exploited | Points balance visible; no low-balance nudge, ROI framing, or plan upsell |
| **Retention Opportunities** | ❌ Missing | No goals, health scores, re-engagement prompts, or achievement system |
| **Analytics Quality** | ⚠️ Dual-source confusion | Legacy vs event metrics coexist without labeling; chart disagrees with verified section |
| **Admin data isolation** | ✅ Pass | Seller queries scoped to `user_id`; no platform-wide BI leakage |

### Top 5 findings (actionable, additive only)

1. **Duplicate metrics confuse sellers** — Views and WhatsApp appear in legacy cards, verified section, per-listing rows, and chart (legacy only), with no explanation of why numbers differ.
2. **No derived KPIs** — Contact rate, conversion rate, phone/WhatsApp ratio, and performance scores are computable from existing event tables but not shown.
3. **Chart is low-value** — Bar chart of 7 paginated listings (not “last 7 by performance”), lifetime legacy columns only, no time axis, no phone clicks.
4. **Monetization moments missed** — Sellers with traffic but zero contacts, low points, or unpublished listings receive no contextual upgrade/feature/top-up prompts.
5. **Business Plan invisible on dashboard** — No credit-volume messaging, no “best value per credit” anchor, no roadmap tease for `business_dashboard` / `business_badge`.

**Verdict:** The dashboard **passes compliance and data isolation** but **under-delivers on conversion, retention, and seller comprehension**. Highest ROI improvements are additive: contextual labels, derived KPIs from existing data, trend charts, and intent-based monetization nudges — without removing any existing section.

---

## Current Dashboard Inventory

### Page structure (top → bottom)

| # | Section | Location (Blade) | Data / Actions | Seller-scoped |
|---|---------|------------------|----------------|---------------|
| 1 | Welcome + CTA | L5–37 | Name, points, verified badge, member since; CTA → `listings.create` | Yes |
| 2 | Status + legacy engagement stats | L39–71 | 6 cards: الكل, نشط, مراجعة, مرفوض, مشاهدات, واتساب | Yes |
| 3 | Verified Analytics | L73–96 | Event Views, Phone Clicks, WhatsApp Clicks | Yes |
| 4 | Performance chart | L98–112 | Chart.js bar: views + WhatsApp per listing | Yes |
| 5 | Incoming offers | L114–147 | Accept/reject pending offers | Yes (`receiver_id`) |
| 6 | Flash messages | L149–155 | Success/error session flashes | — |
| 7 | My listings | L157–339 | Paginated table/cards; feature, delete; per-listing stats | Yes |

### Statistics inventory

| Metric | UI label(s) | Source | Query / table | Appears in |
|--------|-------------|--------|---------------|------------|
| Total listings | الكل | Legacy | `Listing::where('user_id')->count()` | Stats row |
| Active | نشط | Legacy | `status = published` | Stats row |
| Pending | مراجعة | Legacy | `status = pending` | Stats row |
| Rejected | مرفوض | Legacy | `status = rejected` | Stats row |
| Legacy views | مشاهدات | Legacy column | `sum(views_count)` | Stats row, chart, per-listing |
| Legacy WhatsApp | واتساب | Legacy column | `sum(whatsapp_clicks)` | Stats row, chart, per-listing |
| Event views | Event Views | Event table | `listing_views` via `SellerListingAnalyticsService` | Verified section |
| Phone clicks | Phone Clicks | Event table | `listing_phone_clicks` | Verified section only |
| Event WhatsApp | WhatsApp Clicks | Event table | `listing_whatsapp_clicks` | Verified section |
| Points balance | نقطة | User model | `users.points` | Welcome card |
| Per-listing views | 👁 | Legacy column | `listing.views_count` | Listings table/cards |
| Per-listing WhatsApp | 💬 | Legacy column | `listing.whatsapp_clicks` | Listings table/cards |
| Offers count | Badge on section | Offer model | `pending` + `receiver_id` | Offers section |

### Backend services

| Service | Methods | Used by dashboard? |
|---------|---------|-------------------|
| `SellerListingAnalyticsService` | `totalViewsForUser`, `totalPhoneClicksForUser`, `totalWhatsappClicksForUser`, `getDashboardStats` | Yes — 3 event totals only |
| `ListingLeadTrackingService` | `recordView`, `recordPhoneClick`, `recordWhatsappClick` | No (write path on listing show / AJAX) |

**Gap:** `SellerListingAnalyticsService` exposes only platform-wide totals for the seller. No per-listing event aggregation, date filtering, trends, or derived rates.

### Dashboard actions (Livewire)

| Action | Method | Monetization tie-in |
|--------|--------|---------------------|
| Accept offer | `acceptOffer` | Indirect — closes lead loop |
| Reject offer | `rejectOffer` | — |
| Delete listing | `deleteListing` | — |
| Feature listing | `featureListing` | **Direct** — deducts points (`featureWithPoints(3)` → 30 pts at 10/day) |

### Navigation context

| Entry point | Links to dashboard | Dashboard links out |
|-------------|-------------------|---------------------|
| Logo / nav / footer | Yes | `listings.create`, listing show URLs |
| Points badge (nav) | — → `points.history` | **Not linked from dashboard body** |
| Pricing page | Marketed features | **No link from dashboard** |
| Payment checkout | Post-purchase → dashboard | **No in-dashboard top-up CTA** |

### Test coverage (existing)

| Test file | Covers |
|-----------|--------|
| `UserDashboardStatsTest.php` | Legacy totals, status breakdown, `featureListing` |
| `SellerListingAnalyticsServiceTest.php` | Event aggregation, cross-seller isolation |
| `SellerDashboardAnalyticsUiTest.php` | Verified section rendering, legacy coexistence |

**Not tested:** Chart data contract, derived KPIs, monetization nudges, IDOR on actions, conversion UX flows.

---

## UX Findings

### Strengths

| Finding | Evidence |
|---------|----------|
| Cohesive visual system | Consistent `rounded-2xl`, Nilex green palette, RTL support |
| Mobile-first listings | Dedicated card layout `< md`; table `md+` |
| Clear primary CTA | “أضف إعلان جديد” prominent in welcome card |
| Offer workflow inline | Accept/reject without leaving dashboard |
| Empty state for listings | Encourages first listing with CTA |
| Verification badge | Builds trust when `is_phone_verified` |

### Issues

| ID | Finding | Severity | Detail |
|----|---------|----------|--------|
| UX-1 | **Bilingual inconsistency** | Medium | Arabic-primary UI; Verified Analytics section uses English labels (“Event Views”, “Phone Clicks”, “Verified Analytics”) |
| UX-2 | **No metric tooltips or glossary** | Medium | Sellers see 9+ numbers with no “what does this mean?” or “how is this counted?” |
| UX-3 | **Analytics hierarchy unclear** | High | Two stat rows look equally authoritative; sellers cannot tell which to trust |
| UX-4 | **Chart subtitle misleading** | Medium | “آخر 7 إعلانات” implies recency ranking; data is first 7 items on current pagination page of `latest()` |
| UX-5 | **No edit listing path** | Low | Only view (public URL), feature, delete — no inline edit |
| UX-6 | **Rejected listing dead-end** | Medium | Shows “مرفوض” with tooltip on reason (desktop only); no “fix and resubmit” guidance |
| UX-7 | **Points context weak** | Medium | Balance shown in welcome; no cost preview (“تمييز = 30 نقطة لـ 3 أيام”), no link to history or pricing |
| UX-8 | **featureListing opaque** | Low | Star icon + confirm dialog; does not show point cost or duration before confirm |
| UX-9 | **No date range control** | Medium | All stats are lifetime; sellers cannot answer “how am I doing this week?” |
| UX-10 | **Incoming offers buried** | Low | Below chart; high-intent leads may be missed if seller has many listings |
| UX-11 | **No loading / refresh affordance** | Low | Livewire full-page render; chart uses `wire:ignore` — may stale on pagination |

### Information architecture map

```
┌─────────────────────────────────────────────────────────────┐
│  Welcome (identity + points + CTA)                          │
├─────────────────────────────────────────────────────────────┤
│  Row A: Inventory status (4) + Legacy engagement (2)        │  ← Operational
├─────────────────────────────────────────────────────────────┤
│  Row B: Verified event metrics (3)                          │  ← Analytics (unexplained)
├─────────────────────────────────────────────────────────────┤
│  Chart: Per-listing legacy views/WhatsApp (7 bars)          │  ← Analytics (different source)
├─────────────────────────────────────────────────────────────┤
│  Offers (conditional)                                       │  ← Transactions
├─────────────────────────────────────────────────────────────┤
│  Listings table (management + mini legacy stats)            │  ← Operations
└─────────────────────────────────────────────────────────────┘
```

**Problem:** Three analytics surfaces (Row A partial, Row B, Chart, per-listing) with no narrative thread from “traffic” → “contacts” → “action”.

---

## Analytics Findings

### Metric duplication matrix

| Concept | Legacy | Event-based | Per-listing | Chart | Duplicated? |
|---------|--------|-------------|-------------|-------|-------------|
| Views | ✅ مشاهدات | ✅ Event Views | ✅ 👁 | ✅ views_count | **Yes — 4 places** |
| WhatsApp clicks | ✅ واتساب | ✅ WhatsApp Clicks | ✅ 💬 | ✅ whatsapp_clicks | **Yes — 4 places** |
| Phone clicks | ❌ | ✅ Phone Clicks | ❌ | ❌ | No duplication; **under-exposed** |
| Listing status | ✅ 4 cards | — | ✅ per row | — | Appropriate |
| Offers | — | — | — | — | Single section ✅ |
| Points | ✅ welcome | — | — | — | Single ✅ |

### Why legacy vs event numbers differ

| Factor | Legacy (`views_count`, `whatsapp_clicks`) | Event tables |
|--------|------------------------------------------|--------------|
| Storage | Denormalized columns on `listings` | Row per event in `listing_views`, etc. |
| Increment gate | Only when `ListingLeadTrackingService` returns `true` | Same gate at write time |
| Dedup | 24h views (user/IP); 1h clicks (user) | Same rules |
| Historical drift | Pre-TD-01 inflation possible on views | Events only from migration forward |
| Phone | **No legacy column** | `listing_phone_clicks` only |

Sellers comparing “مشاهدات 500” vs “Event Views 120” will assume a bug unless labeled.

### Confusing metrics for sellers

| Metric | Why confusing | Recommendation (additive) |
|--------|---------------|---------------------------|
| مشاهدات vs Event Views | Same word “views”, different counts | Add subtitle: “إجمالي العداد” vs “مشاهدات موثّقة (بدون تكرار)” |
| واتساب vs WhatsApp Clicks | Duplicate labels, different numbers | Same labeling pattern |
| Phone Clicks | Sellers may not know “reveal phone” is tracked | Tooltip: “عندما يضغط المشتري لإظهار رقمك” |
| Verified Analytics (English) | Implies premium/certification unclear | Arabic label + badge explaining deduplication |
| Chart vs cards | Chart uses legacy; verified section uses events | Add chart data source note OR additive event-based chart |

### Metrics sellers likely misunderstand

1. **Views** — May count repeat visits (legacy) vs unique sessions (events) — neither is explained.
2. **WhatsApp clicks** — Click on button ≠ completed conversation; no disclaimer.
3. **Phone clicks** — Requires login to reveal; guests not counted — not disclosed.
4. **Chart “performance”** — Not ranked by performance; just latest listings on page 1.
5. **No conversion rate** — Sellers cannot tell if 1000 views is good or bad without contact context.

### Chart section audit

| Aspect | Current state | Usefulness | Gap |
|--------|---------------|------------|-----|
| Type | Grouped bar chart | Low–Medium | Compares only 7 listings |
| Metrics | Views + WhatsApp (legacy) | Partial | Missing phone clicks |
| Time dimension | None (lifetime per listing) | Low | No weekly/monthly trend |
| Selection logic | `take(7)` from paginated `latest()` | **Misleading** | Should be top-by-metric or date-filtered |
| Interactivity | Static | Low | No drill-down, no hover insights |
| Data source | Legacy columns | Inconsistent | Adjacent verified section uses events |
| Mobile | `h-56` responsive | OK | Label truncation (`mb_substr 12`) loses identity |

### Missing KPIs (computable from existing seller-scoped data)

| KPI | Formula (seller-scoped) | Backend needed? |
|-----|-------------------------|-----------------|
| **Contact Rate** | `(phone_clicks + whatsapp_clicks) / views_events × 100` | Yes — service method |
| **Conversion Rate** (listing lead) | Same as contact rate per listing or aggregate | Yes |
| **Phone vs WhatsApp Ratio** | `phone / (phone + whatsapp)` | Yes |
| **Total Contacts** | `phone_clicks + whatsapp_clicks_events` | Yes — simple sum |
| **Best Performing Listing** | Max contact rate or max contacts with min views threshold | Yes — query |
| **Worst Performing Listing** | Min contact rate among published with views > N | Yes |
| **Top Category** (seller's own) | Group seller listings by `category_id`, sum events | Yes |
| **Weekly trend** | `COUNT(*)` from event tables `WHERE created_at >= now()-7d` | Yes — date filter |
| **Monthly growth** | Compare current 30d vs previous 30d | Yes |
| **Listing Performance Score** | Weighted composite (views, contacts, offers, featured) | Yes — formula design |
| **Engagement Score** | Normalized contact rate vs seller's own average | Yes |

### Analytics quality scorecard

| Criterion | Rating | Notes |
|-----------|--------|-------|
| Accuracy (owner scope) | ✅ High | Queries correctly scoped |
| Consistency (single source) | ❌ Low | Dual legacy + event |
| Completeness | ⚠️ Medium | Phone only in one section |
| Actionability | ❌ Low | Counts without recommendations |
| Timeliness | ❌ Low | No time windows |
| Comparability | ❌ Low | No benchmarks or self-history |
| Trust / transparency | ⚠️ Medium | “Verified” branding without methodology |

---

## Monetization Opportunities

All recommendations are **additive** — new cards, banners, or sections alongside existing UI.

| ID | Opportunity | Trigger condition | Additive UI idea | Difficulty | Business Impact | Engineering Effort | New backend? |
|----|-------------|-------------------|------------------|------------|-----------------|-------------------|--------------|
| M1 | **Low points warning** | `points < FEATURE_COST_PER_DAY * 3` (30) | Amber banner in welcome: “رصيدك ينفد — اشحن نقاطك” → `pricing` | Low | High | 2–4 hrs | No |
| M2 | **Post-traffic feature nudge** | `views_events > 50` && `!is_featured` on top listing | Card: “إعلانك يحصل على مشاهدات — ميّزه لزيادة الظهور” + feature CTA | Low | High | 4–8 hrs | Yes — identify listing |
| M3 | **Zero contacts despite views** | `views_events >= 20` && contacts = 0 | Tip card: photo/price/description checklist + link to listing | Medium | Medium | 8–16 hrs | Yes |
| M4 | **Credit purchase CTA strip** | Always (below stats) | “اشحن رصيدك” + best plan value (Business = lowest EGP/credit) | Low | Medium | 4–6 hrs | No (static from pricing) |
| M5 | **Feature ROI framing** | Near feature button | “٣٠ نقطة = ٣ أيام تمييز — متوسط زيادة الظهور” (marketing copy, no false stats) | Low | Medium | 2–4 hrs | No |
| M6 | **Business Plan upsell** | `listings.total >= 5` OR `views_events >= 200` | Banner: volume discount + roadmap (`business_dashboard`, `business_badge`) | Medium | High | 8–12 hrs | Optional — segment rules |
| M7 | **Offer → feature loop** | After accepting offer | Toast: “عزّز إعلاناتك الأخرى بالتمييز” | Low | Low | 2–4 hrs | No |
| M8 | **Points history link** | Always | Additive link in welcome card next to points balance | Low | Low | 1–2 hrs | No |
| M9 | **Home promotion teaser** | Published + not featured | Mention `home_promotion` from pricing matrix (if implemented) | Medium | Medium | 4–8 hrs | Depends on feature |
| M10 | **Search priority education** | Growth+ features in matrix | “أولوية البحث متاحة عند شحن باقة Growth+” — only if tier gating enforced | Medium | Medium | 4–8 hrs | No |

### Upgrade trigger moments (current vs recommended)

| Moment | Currently | Recommended additive trigger |
|--------|-----------|------------------------------|
| High views, no contacts | Silent | Optimization tips + feature CTA (M2, M3) |
| Low points before feature | Error flash on failure | Proactive M1 banner |
| Multiple active listings | Same UI for 1 or 50 | Business volume messaging (M6) |
| Successful payment return | Lands on dashboard | “استخدم نقاطك لتمييز إعلانك” success coaching |
| First listing published | Stats show zeros | Onboarding checklist card |
| Pending/rejected status | Count only | Action cards: “انتظر المراجعة” / “عدّل وأعد الإرسال” |

### Business Plan upsell (dashboard-specific)

Per `business_plan_marketing_audit.md`, Business Plan differentiation is weak (only `priority_support` + `business_badge` in matrix, both unimplemented). **Safe additive dashboard messaging:**

- Credit volume value: “١٥٠٠ نقطة = أفضل سعر للنقطة” (factual from plan fixtures)
- Inventory seller hook: “تدير أكثر من ٥ إعلانات؟ باقة الأعمال توفر رصيداً أكبر”
- Roadmap tease (compliant): “لوحة تحليلات الأعمال — قريباً” with link to `#seller-dashboard` on pricing
- **Do not** promise CTR, funnel, category BI, or admin top-listings on seller dashboard

---

## Conversion Opportunities

| ID | Opportunity | Description | Difficulty | Business Impact | Engineering Effort | New backend? |
|----|-------------|-------------|------------|-----------------|-------------------|--------------|
| C1 | **Contact Rate KPI card** | Single headline metric: “نسبة التواصل” with formula tooltip | Low | High | 4–8 hrs | Yes |
| C2 | **Total Contacts card** | Sum phone + WhatsApp events — clearer than separate cards | Low | Medium | 2–4 hrs | Yes |
| C3 | **Best / worst listing callouts** | Two compact cards above chart | Medium | High | 8–16 hrs | Yes |
| C4 | **Event-based chart** | Add second chart OR switch dataset to event tables + phone series | Medium | High | 16–24 hrs | Yes |
| C5 | **7-day trend sparkline** | Mini line chart for views + contacts | Medium | High | 16–24 hrs | Yes |
| C6 | **Offers → response rate** | “رديت على X من Y عرض” if offer history tracked | Medium | Medium | 8–12 hrs | Yes |
| C7 | **Listing health badges** | “صور ناقصة”, “وصف قصير”, “سعر غير محدد” on rows | Medium | High | 16–24 hrs | Yes — rules engine |
| C8 | **Click-to-feature from chart** | Select bar → highlight feature button on that listing | Medium | Medium | 8–12 hrs | No (front-end) |
| C9 | **WhatsApp vs Phone insight** | “عملاؤك يفضّون الواتساب بنسبة ٧٠٪” | Low | Medium | 4–8 hrs | Yes |
| C10 | **Verified badge on high performers** | Gamified “إعلان نجم” when contact rate > threshold | Low | Medium | 4–8 hrs | Yes |

### Funnel view (seller mental model — additive)

```
مشاهدات → كشف الهاتف / واتساب → عروض سعر → قبول
```

Currently only raw stage counts exist. **Additive funnel strip** (not admin LeadFunnelWidget) could show seller's own stage totals as a horizontal progress visualization.

---

## Retention Opportunities

| ID | Opportunity | Description | Difficulty | Business Impact | Engineering Effort | New backend? |
|----|-------------|-------------|------------|-----------------|-------------------|--------------|
| R1 | **Weekly performance summary** | “هذا الأسبوع: +١٢ مشاهدة، +٣ تواصل” vs last week | Medium | High | 16–24 hrs | Yes |
| R2 | **Seller goals** | “هدف: ١٠ تواصلات هذا الشهر” with progress bar | Medium | High | 24–40 hrs | Yes — goals table |
| R3 | **Achievement badges** | “أول ١٠٠ مشاهدة”, “أول عرض سعر”, “بائع موثّق” | Medium | Medium | 24–40 hrs | Yes |
| R4 | **Listing health score** | 0–100 per listing based on completeness + engagement | Medium | High | 16–24 hrs | Yes |
| R5 | **Re-engagement prompt** | No logins 14d + active listings → email/in-dashboard prompt | High | High | 40+ hrs | Yes — jobs |
| R6 | **Stale listing alert** | Published > 60d, views declining → “حدّث الصور أو انشر من جديد” | Medium | Medium | 12–20 hrs | Yes |
| R7 | **Rejected listing recovery** | Dedicated card with rejection reason + edit CTA | Low | Medium | 4–8 hrs | No |
| R8 | **Onboarding checklist** | First 7 days: verify phone, post listing, share link, feature | Medium | High | 16–24 hrs | Yes — progress state |
| R9 | **Performance recommendations** | Rule-based tips from metrics | Medium | High | 16–24 hrs | Yes |
| R10 | **Monthly email digest** | Roadmap item `monthly_reports` — seller-scoped summary | High | Medium | 40+ hrs | Yes |

### Listing health indicators (additive per row)

| Signal | Data available today | Display idea |
|--------|---------------------|--------------|
| No images | `getFirstMediaUrl` empty | Red “أضف صوراً” |
| Low views (7d) | Event table query | Amber “قليل الظهور” |
| High views, zero contacts | Derived | “حسّن الوصف أو السعر” |
| Not featured | `is_featured` | “ميّز الإعلان” |
| Featured expiring | `featured_until` | “ينتهي التمييز خلال X أيام” |
| Rejected | `status`, `rejection_reason` | “عدّل وأعد الإرسال” |

---

## Quick Wins

Additive changes with **Low difficulty**, **minimal backend**, deployable in days.

| # | Improvement | What to add | Difficulty | Impact | Effort | Backend? |
|---|-------------|-------------|------------|--------|--------|----------|
| QW1 | Arabic labels for Verified Analytics | Translate section title + card labels | Low | Medium | 1–2 hrs | No |
| QW2 | Metric subtitles | “إجمالي العداد” / “أحداث موثّقة بدون تكرار” under duplicate pairs | Low | High | 2–4 hrs | No |
| QW3 | Phone click tooltip | One-line explanation under Phone Clicks card | Low | Medium | 1 hr | No |
| QW4 | Points history + pricing links | In welcome card next to balance | Low | Medium | 1–2 hrs | No |
| QW5 | Fix chart subtitle copy | “أحدث ٧ إعلانات في القائمة” or rank by views | Low | Medium | 1 hr (copy) / 8 hrs (logic) | Optional |
| QW6 | Total Contacts summary card | `phone + whatsapp` events in verified section | Low | High | 2–4 hrs | Yes (trivial) |
| QW7 | Contact Rate card | One derived percentage | Low | High | 4–6 hrs | Yes |
| QW8 | Low points banner | Conditional on `points < 30` | Low | High | 2–4 hrs | No |
| QW9 | Feature cost in confirm | Show “٣٠ نقطة لـ ٣ أيام” in `wire:confirm` or adjacent text | Low | Medium | 1–2 hrs | No |
| QW10 | Rejected listing guidance | Static tip when `rejected > 0` | Low | Medium | 2–4 hrs | No |

---

## High Impact Improvements

| # | Improvement | Rationale | Difficulty | Impact | Effort | Backend? |
|---|-------------|-----------|------------|--------|--------|----------|
| HI1 | **Unified analytics narrative** | Keep both sections but add explainer + Total Contacts + Contact Rate | Medium | High | 16–24 hrs | Yes |
| HI2 | **7/30-day trend chart** | Answers “am I growing?” — high retention value | Medium | High | 24–40 hrs | Yes |
| HI3 | **Best/worst listing insights** | Actionable performance without admin top-listings BI | Medium | High | 16–24 hrs | Yes |
| HI4 | **Contextual feature nudge** | Converts existing traffic to points spend | Low–Med | High | 8–16 hrs | Yes |
| HI5 | **Listing health indicators** | Drives listing quality → more contacts | Medium | High | 16–24 hrs | Yes |
| HI6 | **Event-based per-listing stats in table** | Add phone + event views columns alongside legacy | Medium | High | 16–24 hrs | Yes |
| HI7 | **Business Plan volume banner** | Connects dashboard scale to credit economics | Medium | High | 8–12 hrs | Optional |
| HI8 | **Seller-scoped funnel strip** | Stage visualization using existing event + offer counts | Medium | High | 16–24 hrs | Yes |

---

## Low Risk Improvements

Changes that **do not remove** existing sections, **do not expose** admin BI, and **do not modify** permissions.

| # | Improvement | Risk profile | Notes |
|---|-------------|--------------|-------|
| LR1 | Additive KPI cards below verified section | ✅ Safe | New row; legacy cards untouched |
| LR2 | Arabic translations for English labels | ✅ Safe | Copy only |
| LR3 | Tooltips and methodology notes | ✅ Safe | Reduces confusion without changing data |
| LR4 | Links to `points.history` and `pricing` | ✅ Safe | Uses existing routes |
| LR5 | Second chart (event-based) alongside current | ✅ Safe | Additive; legacy chart remains |
| LR6 | Conditional coaching banners | ✅ Safe | Rule-based on seller's own data |
| LR7 | Listing health badges on rows | ✅ Safe | Derived from own listing fields |
| LR8 | Weekly delta text (“+١٢٪ عن الأسبوع الماضي”) | ✅ Safe | Seller-scoped time queries |
| LR9 | Onboarding checklist for new sellers | ✅ Safe | Hides when complete |
| LR10 | “Coming soon” teasers for `basic_ctr`, `monthly_reports` | ✅ Safe | Compliant with pricing matrix badges |

### Explicitly avoid (per audit rules)

- Removing legacy stat cards or verified section
- Exposing admin widgets (CTR platform-wide, category performance, revenue, top listings across platform)
- Showing competitor benchmarks or other sellers' data
- Enforcing plan-tier gating without product decision (would be subtractive visibility for some users)

---

## Estimated Business Impact

### Impact model (qualitative)

| Initiative cluster | Primary metric affected | Expected direction | Confidence |
|--------------------|-------------------------|-------------------|------------|
| QW2 + QW6 + QW7 (clarity + contact rate) | Seller comprehension, trust | ↑ dashboard engagement | High |
| QW8 + M2 + M4 (monetization nudges) | Points purchase, feature adoption | ↑ ARPU, ↑ credit burn | Medium–High |
| HI2 + HI3 (trends + best/worst) | Return visits, listing optimization | ↑ retention, ↑ contact rate | Medium |
| HI5 + R4 (listing health) | Listing quality | ↑ buyer contacts | Medium |
| M6 + HI7 (Business Plan) | Business tier checkout | ↑ high-value transactions | Medium (messaging only today) |
| R2 + R3 (goals + achievements) | DAU/WAU sellers | ↑ long-term retention | Medium (longer build) |

### Prioritized roadmap (additive only)

| Phase | Items | Calendar | Cumulative effort |
|-------|-------|----------|-------------------|
| **Phase A — Clarity** | QW1–QW4, QW6–QW7, QW9 | 1–2 days | ~20 hrs |
| **Phase B — Convert** | QW8, M1–M2, M4–M5, C1–C3 | 3–5 days | ~40 hrs |
| **Phase C — Insight** | HI1, HI3, HI6, C4–C5, LR8 | 1–2 weeks | ~80 hrs |
| **Phase D — Retain** | HI5, R1, R4, R7–R8 | 2–3 weeks | ~60 hrs |
| **Phase E — Scale** | M6, HI7, R2–R3, R10 | 4+ weeks | ~100+ hrs |

### KPIs to measure post-implementation

| Metric | Baseline (today) | Target |
|--------|------------------|--------|
| Feature adoption rate | Unknown — not tracked on dashboard | Track `featureListing` clicks / eligible listings |
| Pricing page visits from dashboard | 0 direct links | Add UTM; target measurable referrals |
| Points purchase conversion | Post-payment only | Low-points banner → checkout rate |
| Dashboard return frequency | Not measured | Weekly active sellers |
| Contact rate (platform) | Not shown to sellers | Seller-level improvement after health tips |
| Support tickets “analytics wrong” | Qualitative | Reduce via labeling (QW2) |

---

## Recommendation Register

Full register for product/engineering tracking. **None require removing existing code.**

| ID | Recommendation | Category | Difficulty | Business Impact | Effort | New backend? |
|----|----------------|----------|------------|-----------------|--------|--------------|
| QW1 | Arabic labels for Verified Analytics | UX | Low | Medium | 1–2 hrs | No |
| QW2 | Dual-source metric subtitles | Analytics | Low | High | 2–4 hrs | No |
| QW3 | Phone click tooltip | Analytics | Low | Medium | 1 hr | No |
| QW4 | Points history + pricing links | Monetization | Low | Medium | 1–2 hrs | No |
| QW5 | Chart subtitle accuracy | Analytics | Low | Medium | 1–8 hrs | Optional |
| QW6 | Total Contacts card | Conversion | Low | High | 2–4 hrs | Yes |
| QW7 | Contact Rate card | Conversion | Low | High | 4–6 hrs | Yes |
| QW8 | Low points banner | Monetization | Low | High | 2–4 hrs | No |
| QW9 | Feature cost disclosure | UX | Low | Medium | 1–2 hrs | No |
| QW10 | Rejected listing guidance | Retention | Low | Medium | 2–4 hrs | No |
| M1 | Low points warning | Monetization | Low | High | 2–4 hrs | No |
| M2 | High-traffic feature nudge | Monetization | Low | High | 4–8 hrs | Yes |
| M3 | Zero-contact optimization tips | Conversion | Medium | Medium | 8–16 hrs | Yes |
| M4 | Credit purchase strip | Monetization | Low | Medium | 4–6 hrs | No |
| M5 | Feature ROI copy | Monetization | Low | Medium | 2–4 hrs | No |
| M6 | Business Plan volume banner | Monetization | Medium | High | 8–12 hrs | Optional |
| C1 | Contact Rate KPI | Conversion | Low | High | 4–8 hrs | Yes |
| C3 | Best/worst listing callouts | Conversion | Medium | High | 8–16 hrs | Yes |
| C4 | Event-based chart (additive) | Analytics | Medium | High | 16–24 hrs | Yes |
| C5 | 7-day trend sparkline | Analytics | Medium | High | 16–24 hrs | Yes |
| C7 | Listing health badges | Retention | Medium | High | 16–24 hrs | Yes |
| C9 | Phone vs WhatsApp ratio insight | Analytics | Low | Medium | 4–8 hrs | Yes |
| HI1 | Unified analytics narrative | Analytics | Medium | High | 16–24 hrs | Yes |
| HI2 | 7/30-day trend chart | Retention | Medium | High | 24–40 hrs | Yes |
| HI5 | Listing health score | Retention | Medium | High | 16–24 hrs | Yes |
| HI6 | Event stats in listings table | Analytics | Medium | High | 16–24 hrs | Yes |
| HI7 | Business Plan dashboard banner | Monetization | Medium | High | 8–12 hrs | Optional |
| R1 | Weekly performance summary | Retention | Medium | High | 16–24 hrs | Yes |
| R2 | Seller goals | Retention | Medium | High | 24–40 hrs | Yes |
| R3 | Achievement badges | Retention | Medium | Medium | 24–40 hrs | Yes |
| R7 | Rejected listing recovery card | Retention | Low | Medium | 4–8 hrs | No |
| R8 | Onboarding checklist | Retention | Medium | High | 16–24 hrs | Yes |

---

## Appendix A — Safe seller BI vs admin BI boundary

| Admin-only (do NOT expose) | Safe seller-scoped alternative |
|----------------------------|-------------------------------|
| Platform CTR / conversion (`ConversionMetricsWidget`) | Seller's own contact rate |
| Lead funnel platform-wide (`LeadFunnelWidget`) | Seller's own views → contacts → offers strip |
| Revenue analytics | Seller's own points spend history (already on `points.history`) |
| Category performance platform-wide | Seller's own listings grouped by category |
| Top listings platform-wide | Seller's own best/worst listing |
| Cross-seller benchmarks | Seller's own week-over-week delta |

---

## Appendix B — Data layer readiness

| Event table | Indexed | `created_at` for trends | Per-listing aggregation |
|-------------|---------|-------------------------|-------------------------|
| `listing_views` | ✅ `listing_id`, `created_at` | ✅ | Ready |
| `listing_phone_clicks` | ✅ | ✅ | Ready |
| `listing_whatsapp_clicks` | ✅ | ✅ | Ready |

`SellerListingAnalyticsService` requires **extension only** (new methods) — no migration needed for Phase A–C recommendations.

---

## Appendix C — Compliance cross-reference

Findings from `seller_dashboard_compliance_audit.md` relevant to this UX audit:

| Compliance finding | UX implication |
|--------------------|----------------|
| M1 Tier matrix vs all-access | Dashboard cannot use tier-gated upsell for event analytics until product decides gating |
| M2 Dual metric sources | **Primary UX issue** — address via QW2, HI1 (labeling, not removal) |
| M3 Chart legacy-only | **Primary analytics issue** — address via C4 (additive event chart) |
| L1 Points history not in dashboard body | Address via QW4 |

---

*End of Seller Dashboard UX & Conversion Audit Report.*
