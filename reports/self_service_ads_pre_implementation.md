# Self-Service Ads — Pre-Implementation Report

**Date:** 2026-06-13  
**Project:** Nilex Marketplace  
**Scope:** Discovery findings before Self-Service Advertising Platform implementation  
**Status:** Pre-Implementation (Discovery Complete — Awaiting Build)

---

## Executive Summary

The Nilex platform already has an **admin-managed ad campaigns system** (Parts 1–3, documented in `reports/ad_campaigns_implementation.md`). The **Self-Service Advertising Platform** (seller purchases via Paymob, seller dashboard, isolated payment flow) has **not been implemented yet**.

This report captures what exists today, what is missing, risks, and the planned additive changes — without modifying Points, Entitlements, Featured Listings, or existing checkout.

---

## Existing Components

### Database & Models

| Component | Path | Notes |
|-----------|------|-------|
| `ad_campaigns` migration | `database/migrations/2026_06_13_000001_create_ad_campaigns_table.php` | placement, status, approval_status, scheduling, metrics, soft deletes |
| `ad_campaign_logs` migration | `database/migrations/2026_06_13_000002_create_ad_campaign_logs_table.php` | impression/click events (not admin audit) |
| `AdCampaign` model | `app/Models/AdCampaign.php` | SoftDeletes, Spatie Media, approve/reject helpers |
| `AdCampaignLog` model | `app/Models/AdCampaignLog.php` | Tracking log only |

**Columns present today:** `title`, `placement`, `category_id`, `target_url`, `status`, `approval_status`, `rejected_reason`, `starts_at`, `ends_at`, `views_count`, `clicks_count`, `priority`, `created_by`

**Columns NOT present:** `seller_id`, `payment_status`, `paymob_order_id`, `paymob_transaction_id`, `amount_paid`, `paid_at`

### Services & Jobs

| Component | Path | Notes |
|-----------|------|-------|
| `AdCampaignService` | `app/Services/AdCampaignService.php` | Placement cache, tracking, approve/reject (Log only) |
| `AdCampaignObserver` | `app/Observers/AdCampaignObserver.php` | Auto-expire + cache invalidation |
| `TrackAdImpressionJob` | `app/Jobs/TrackAdImpressionJob.php` | Queued impressions, 60-min dedup |
| `ExpireCampaigns` | `app/Console/Commands/ExpireCampaigns.php` | Hourly via `routes/console.php` |

### Filament Admin

| Component | Path | Notes |
|-----------|------|-------|
| `AdCampaignResource` | `app/Filament/Admin/Resources/AdCampaigns/AdCampaignResource.php` | Full CRUD — no Approve/Reject UI actions |
| `AdCampaignStats` | `app/Filament/Admin/Pages/AdCampaignStats.php` | Charts + export |
| Navigation group | `AdminPanelProvider` | `الحملات الإعلانية` |

### Frontend & Tracking

| Component | Path | Notes |
|-----------|------|-------|
| Hero Top | `resources/views/frontend/home.blade.php` | Wired |
| Home Feed | `resources/views/frontend/home.blade.php` | Wired |
| Category Page | `resources/views/frontend/category.blade.php` | Wired |
| Login Page Banner | `resources/views/auth/login.blade.php` | **Not wired** |
| Ad banner component | `resources/views/components/ad-banner.blade.php` | Reusable |
| Pricing landing | `resources/views/frontend/ads/pricing.blade.php` | Static `mailto:` — no self-service |
| Tracking routes | `routes/web.php` | `ads.impression`, `ads.click`, `ads.pricing` |

### Paymob (Points System — Separate)

| Component | Path | Notes |
|-----------|------|-------|
| `PaymobService` | `app/Services/PaymobService.php` | Auth, order, payment key |
| `PaymobWebhookService` | `app/Services/PaymobWebhookService.php` | Points `Transaction` fulfillment only |
| `PaymobController` | `app/Http/Controllers/PaymobController.php` | `POST /payments/callback` |
| `PaymentController` | `app/Http/Controllers/Frontend/PaymentController.php` | Points checkout only |
| Config | `config/services.php` | `paymob.*` credentials |

### Config Missing for Self-Service

| File | Status |
|------|--------|
| `config/features.php` | **Not Found** |
| `config/ad_pricing.php` | **Not Found** |
| `payment_attempts` table | **Not Found** |
| `ad_campaign_audit_logs` table | **Not Found** |
| `PaymobWebhookController` (ads) | **Not Found** |
| `/webhooks/paymob/ads` route | **Not Found** |
| Seller `/dashboard/ads*` routes | **Not Found** |
| `AdCampaignPolicy` | **Not Found** |

---

## Systems Verified Isolated (Must Not Be Modified)

| System | Key Files | Verified |
|--------|-----------|----------|
| Points | `PointService`, `PointPlan`, `Transaction` | ✅ Present, separate |
| Entitlements | `EntitlementService`, `HasPlanType` | ✅ Present, separate |
| Featured Listings | `Listing::featureWithPoints()`, Filament feature actions | ✅ Present, separate |
| Existing Checkout | `PaymentController`, `PaymobWebhookService` | ✅ Points-only path |
| Subscriptions | `AssignPlanEntitlementsListener` | ✅ Present, separate |

---

## Risks

| # | Risk | Severity | Mitigation |
|---|------|----------|------------|
| 1 | Admin campaigns use `status = active` without payment; self-service needs `payment_status = paid` | **High** | Backward-compatible display scope: admin campaigns bypass payment check |
| 2 | Paymob `merchant_order_id` pattern `nilex:{transaction_id}` collides if reused | **High** | Use distinct prefix e.g. `nilex-ad:{campaign_id}:{attempt_id}` |
| 3 | Modifying `PaymobWebhookService` breaks points checkout | **Critical** | Parallel `PaymobAdWebhookService` only — never edit points webhook |
| 4 | `created_by` vs `seller_id` naming mismatch | **Medium** | Add `seller_id`; keep `created_by` for admin-created records |
| 5 | `placement` enum lacks `login_page` | **Medium** | New migration ALTER enum |
| 6 | Approve/Reject logs to `Log::info` only | **Medium** | New `ad_campaign_audit_logs` table |
| 7 | No `AdCampaignPolicy` | **Medium** | Create policy before seller dashboard |
| 8 | Click tracking is synchronous (spec wants queue) | **Low** | Add `TrackAdClickJob` |
| 9 | Ad migrations untracked in git | **Medium** | Commit + verify migrate status per environment |
| 10 | No feature flag — all ad routes active | **Medium** | `config/features.php` gates all new + existing self-service paths |

---

## Planned Changes (Additive Only)

### Phase 1 — Foundation
- Create `config/features.php` → `self_service_ads => false`
- Create `config/ad_pricing.php` → placement + duration pricing
- Migrations: extend `ad_campaigns`, create `payment_attempts`, create `ad_campaign_audit_logs`

### Phase 2 — Payment (Isolated)
- `AdCampaignPaymentService` + `PaymobAdWebhookService`
- `PaymobWebhookController` → `POST /webhooks/paymob/ads`
- HMAC, idempotency, row locking — separate from points

### Phase 3 — Seller Dashboard (RTL)
- `/dashboard/ads`, `/dashboard/ads/create`, `/dashboard/ads/{campaign}`
- Livewire + Alpine.js price calculator, draft, retry payment

### Phase 4 — Admin Enhancements
- Extend `AdCampaignResource`: Seller, Payment Status, Approve/Reject + reason modal
- Audit log writes to `ad_campaign_audit_logs`

### Phase 5 — Frontend Placements
- Wire `login_page` banner on login view
- Display rule: `payment_status = paid` + `approval_status = approved` + date window + not deleted

### Phase 6 — Performance & Tracking
- All retrieval via `AdCampaignService` (cache + eager load)
- Queue click tracking + cache invalidation jobs
- Feature flag on routes, services, views, admin actions

---

## Related Documentation

| Report | Purpose |
|--------|---------|
| `reports/ad_campaigns_part1.md` | Admin campaigns backend |
| `reports/ad_campaigns_part2.md` | Filament admin CRUD |
| `reports/ad_campaigns_implementation.md` | Full admin campaigns summary |
| `reports/self_service_advertising.md` | Architecture & implementation plan (this initiative) |
| `reports/self_service_ads_rollback_plan.md` | Rollback procedures |

---

**Next step:** Explicit approval to begin implementation starting with feature flag + migrations.
