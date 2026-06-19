# Self-Service Advertising — Phase 1 & Phase 2 Execution Report

**Date:** 2026-06-13  
**Project:** Nilex Marketplace  
**Scope:** Backend Foundation + Payment Infrastructure  
**Status:** Complete — STOP before Phase 3

---

## 1. Existing Components Detected

| Component | Path | Status |
|-----------|------|--------|
| `AdCampaign` model | `app/Models/AdCampaign.php` | ✅ Found |
| `AdCampaignLog` model | `app/Models/AdCampaignLog.php` | ✅ Found |
| `AdCampaignService` | `app/Services/AdCampaignService.php` | ✅ Found |
| `AdCampaignObserver` | `app/Observers/AdCampaignObserver.php` | ✅ Found |
| `AdCampaignResource` (Filament) | `app/Filament/Admin/Resources/AdCampaigns/AdCampaignResource.php` | ✅ Found (NOT modified) |
| `ad_campaigns` migration | `database/migrations/2026_06_13_000001_create_ad_campaigns_table.php` | ✅ Found |
| `ad_campaign_logs` migration | `database/migrations/2026_06_13_000002_create_ad_campaign_logs_table.php` | ✅ Found |
| `PaymobService` | `app/Services/PaymobService.php` | ✅ Found |
| `PaymobWebhookService` (points) | `app/Services/PaymobWebhookService.php` | ✅ Found |
| `PaymobController` (points) | `app/Http/Controllers/PaymobController.php` | ✅ Found |
| `PaymentController` (points checkout) | `app/Http/Controllers/Frontend/PaymentController.php` | ✅ Found |
| `config/services.php` (Paymob creds) | `config/services.php` | ✅ Found |
| `config/features.php` | — | ❌ NOT FOUND (created) |
| `PaymentAttempt` model | — | ❌ NOT FOUND (created) |
| `AdCampaignAuditLog` model | — | ❌ NOT FOUND (created) |
| `AdCampaignPaymentService` | — | ❌ NOT FOUND (created) |
| `PaymobAdWebhookService` | — | ❌ NOT FOUND (created) |
| `PaymobWebhookController` | — | ❌ NOT FOUND (created) |

---

## 2. Components Reused

| Component | Reuse Pattern |
|-----------|---------------|
| `AdCampaign` model | Extended with payment/seller fields only |
| `AdCampaignService` | Extended `getForPlacement()` with feature-flagged display rules |
| `PaymobService` | Injected into `AdCampaignPaymentService` for auth/order/payment key |
| `config/services.php` | Paymob credentials (`api_key`, `integration_id`, `iframe_id`, `hmac_secret`) |
| HMAC field order | Mirrored from `PaymobWebhookService::computeHmac()` in new isolated service |
| Merchant order ID namespace | Points: `nilex:{id}` · Ads: `nilex-ad:{campaign_id}:{attempt_id}` |

---

## 3. Components Extended

### `app/Models/AdCampaign.php`

- Added fillable: `seller_id`, `payment_status`, `paymob_order_id`, `paymob_transaction_id`, `amount_paid`, `paid_at`
- Added casts: `paid_at`, `payment_status`, `amount_paid`
- Added relationships: `seller()`, `paymentAttempts()`, `auditLogs()`
- Added scopes: `displayable()`, `paid()`
- Added helpers: `isDisplayable()`, `is_paid` accessor
- **Preserved:** all existing scopes, methods, media config, SoftDeletes

### `app/Services/AdCampaignService.php`

- Extended `getForPlacement()` to apply `displayable()` scope when `features.self_service_ads` is enabled
- When flag is OFF, existing `active()` scope behavior is unchanged
- **Preserved:** all existing public methods unchanged

### `bootstrap/app.php`

- Registered isolated `routes/ads.php` via `withRouting(then: ...)`
- Added CSRF exemption for `webhooks/paymob/ads`

---

## 4. Components Created

| Component | Path |
|-----------|------|
| Feature flag config | `config/features.php` |
| Ad pricing config | `config/ad_pricing.php` |
| `PaymentAttempt` model | `app/Models/PaymentAttempt.php` |
| `AdCampaignAuditLog` model | `app/Models/AdCampaignAuditLog.php` |
| `AdCampaignPaymentService` | `app/Services/AdCampaignPaymentService.php` |
| `PaymobAdWebhookService` | `app/Services/PaymobAdWebhookService.php` |
| `PaymobWebhookController` | `app/Http/Controllers/PaymobWebhookController.php` |
| Ads routes file | `routes/ads.php` |

---

## 5. Files Created

```
config/features.php
config/ad_pricing.php
database/migrations/2026_06_13_100001_add_self_service_fields_to_ad_campaigns_table.php
database/migrations/2026_06_13_100002_create_payment_attempts_table.php
database/migrations/2026_06_13_100003_create_ad_campaign_audit_logs_table.php
app/Models/PaymentAttempt.php
app/Models/AdCampaignAuditLog.php
app/Services/AdCampaignPaymentService.php
app/Services/PaymobAdWebhookService.php
app/Http/Controllers/PaymobWebhookController.php
routes/ads.php
reports/self_service_execution_phase1.md
```

---

## 6. Files Modified

```
app/Models/AdCampaign.php
app/Services/AdCampaignService.php
bootstrap/app.php
```

**Safety check:** None of the modified files belong to Points, Entitlements, Featured Listings, Checkout, or Subscription systems.

---

## 7. Migrations Generated

### MIGRATION_PLAN

#### Extend `ad_campaigns` — `2026_06_13_100001_add_self_service_fields_to_ad_campaigns_table.php`

| Column | Action | Notes |
|--------|--------|-------|
| `placement` | **SKIP** | Already exists |
| `approval_status` | **SKIP** | Already exists |
| `rejected_reason` | **SKIP** | Already exists |
| `deleted_at` | **SKIP** | Already exists (SoftDeletes) |
| `seller_id` | **ADD** | FK → `users`, nullable |
| `payment_status` | **ADD** | enum: pending/paid/failed/refunded, nullable |
| `paymob_order_id` | **ADD** | string, nullable |
| `paymob_transaction_id` | **ADD** | string, nullable, unique |
| `amount_paid` | **ADD** | decimal(10,2), nullable |
| `paid_at` | **ADD** | timestamp, nullable |

#### Create `payment_attempts` — `2026_06_13_100002_create_payment_attempts_table.php`

| Column | Type |
|--------|------|
| `id` | bigint PK |
| `campaign_id` | FK → `ad_campaigns` |
| `amount` | decimal(10,2) |
| `paymob_order_id` | string, nullable |
| `paymob_transaction_id` | string, nullable, unique |
| `status` | enum: pending/success/failed |
| `payload` | json, nullable |
| `created_at`, `updated_at` | timestamps |

#### Create `ad_campaign_audit_logs` — `2026_06_13_100003_create_ad_campaign_audit_logs_table.php`

| Column | Type |
|--------|------|
| `id` | bigint PK |
| `campaign_id` | FK → `ad_campaigns` |
| `admin_id` | FK → `users`, nullable |
| `action` | string |
| `old_values` | json, nullable |
| `new_values` | json, nullable |
| `created_at`, `updated_at` | timestamps |

**Note:** Migrations generated only. `php artisan migrate` was NOT executed per instructions.

---

## 8. Security Review

| Control | Implementation | Location |
|---------|----------------|----------|
| Feature flag gate | Returns 404 when disabled | `PaymobAdWebhookService::handle()` |
| HMAC validation | SHA-512, 22-field concatenation | `PaymobAdWebhookService::verifyHmac()` |
| Amount verification | `amount_cents` vs `payment_attempts.amount` | `PaymobAdWebhookService::handle()` |
| Campaign ownership | Merchant order ID parse + seller_id check | `verifyCampaignOwnership()` |
| Transaction status | Rejects `success=false` or `pending=true` | `PaymobAdWebhookService::handle()` |
| Replay protection | Unique `paymob_transaction_id` indexes | Migrations + `isTransactionAlreadyProcessed()` |
| Idempotency | Status check + duplicate transaction lookup | `fulfill()` + pre-check |
| Row locking | `lockForUpdate()` on attempt + campaign | `fulfill()` |
| Database transactions | Wrapped in `DB::transaction()` | `fulfill()`, `initiatePayment()` |
| Rejected webhook logging | All rejections logged with IP + reason | Throughout `PaymobAdWebhookService` |
| CSRF exemption | Only `/webhooks/paymob/ads` | `bootstrap/app.php` |
| Payload never trusted | All state derived from DB + HMAC-validated fields | Webhook service design |

---

## 9. HMAC Review

- **Algorithm:** `hash_hmac('sha512', ...)` — matches existing points webhook
- **Secret source:** `config('services.paymob.hmac_secret')` with env fallback
- **Field order:** Identical 22-field sequence from `PaymobWebhookService::computeHmac()`
- **Boolean normalization:** `boolStr()` → `'true'` / `'false'`
- **Comparison:** `hash_equals()` for timing-safe comparison
- **HMAC location:** Query string `?hmac=...` (Paymob standard)
- **Isolation:** HMAC logic duplicated in `PaymobAdWebhookService` — existing `PaymobWebhookService` NOT modified

---

## 10. Idempotency Review

| Layer | Mechanism |
|-------|-----------|
| Pre-fulfillment | `isTransactionAlreadyProcessed()` checks `payment_attempts.paymob_transaction_id` (status=success) and `ad_campaigns.paymob_transaction_id` (payment_status=paid) |
| During fulfillment | `lockForUpdate()` on both `PaymentAttempt` and `AdCampaign` rows |
| Attempt status | Returns `already_processed` if attempt status is already `success` |
| Campaign status | Returns `already_processed` if campaign `payment_status` is already `paid` |
| Database constraint | Unique index on `paymob_transaction_id` in both tables |
| Response | Duplicate callbacks receive HTTP 200 with `{"status":"already_processed"}` |

---

## 11. Database Transaction Review

| Operation | Transaction Scope |
|-----------|-------------------|
| Payment initiation | `AdCampaignPaymentService::initiatePayment()` — creates attempt, Paymob order, updates campaign atomically |
| Webhook fulfillment | `PaymobAdWebhookService::fulfill()` — locks rows, updates attempt + campaign atomically |
| Rollback | Any exception in fulfillment returns 500; no partial state committed |

---

## 12. Feature Flag Review

| Location | Behavior when `self_service_ads = false` (default) |
|----------|-----------------------------------------------------|
| `config/features.php` | `self_service_ads => env('SELF_SERVICE_ADS', false)` |
| `PaymobAdWebhookService` | Returns 404, logs attempt |
| `AdCampaignPaymentService` | `ensureEnabled()` throws RuntimeException |
| `AdCampaignService::getForPlacement()` | Uses existing `active()` scope (unchanged behavior) |

| Behavior when `self_service_ads = true` |
|---------------------------------------|
| Webhook processes ad payments |
| `getForPlacement()` applies `displayable()` payment gate |
| Payment service allows checkout initiation |

**Env variable:** `SELF_SERVICE_ADS=true` to enable.

---

## 13. Rollback Notes

1. **Disable feature:** Set `SELF_SERVICE_ADS=false` or remove env var (default false)
2. **Reverse migrations (in order):**
   ```bash
   php artisan migrate:rollback --step=3
   ```
3. **Remove route registration:** Revert `bootstrap/app.php` `then:` block and CSRF exception
4. **Delete new files:** See Section 5 (Files Created)
5. **Revert extensions:** Restore `AdCampaign.php` and `AdCampaignService.php` from git

**Zero impact on points flow:** Points webhook routes (`/payments/callback`, `/payment/webhook`) and `PaymobWebhookService` were not touched.

---

## 14. Isolation Confirmation

| System | Modified? |
|--------|-----------|
| Points System | ❌ NOT modified |
| Point Plans | ❌ NOT modified |
| User Entitlements | ❌ NOT modified |
| Featured Listings | ❌ NOT modified |
| Listing Promotions | ❌ NOT modified |
| Existing Checkout (`PaymentController`) | ❌ NOT modified |
| Existing Subscription Logic | ❌ NOT modified |
| `PaymobWebhookService` (points) | ❌ NOT modified |
| `PaymobService` | ❌ NOT modified |
| `AdCampaignResource` (Filament) | ❌ NOT modified |

---

## Phase 1 Acceptance Criteria

| Criterion | Status |
|-----------|--------|
| `config/features.php` | ✅ |
| `config/ad_pricing.php` | ✅ |
| Migration files generated | ✅ |
| `PaymentAttempt` model | ✅ |
| `AdCampaignAuditLog` model | ✅ |
| `AdCampaign` safely extended | ✅ |
| `AdCampaignPaymentService` | ✅ |
| `PaymobAdWebhookService` | ✅ |
| `PaymobWebhookController` | ✅ |
| Webhook route `/webhooks/paymob/ads` | ✅ Verified via `php artisan route:list` |
| HMAC validation | ✅ |
| Idempotency | ✅ |
| Row locking | ✅ |
| Database transactions | ✅ |
| Feature flag integration | ✅ |

---

## NOT Implemented (Phase 3+)

Per instructions, the following were intentionally excluded:

- Seller UI / Dashboard pages
- Filament Resources, Forms, Tables
- Frontend Banners / Placement rendering
- Public Advertising UI
- Notifications (approval, payment received)
- Middleware (`EnsureSelfServiceAdsEnabled`)
- Policies (`AdCampaignPolicy`)
- Livewire seller components

**Awaiting explicit user approval before continuing to Phase 3.**
