# Self-Service Ads — Rollback Plan

**Date:** 2026-06-13  
**Project:** Nilex Marketplace  
**Applies to:** Self-Service Advertising Platform (Paymob seller checkout)  
**Status:** Pre-Implementation reference

---

## Purpose

This document describes how to safely disable or roll back the Self-Service Advertising feature without affecting the **Points system**, **Entitlements**, **Featured Listings**, or **existing Paymob points checkout**.

The existing **admin-managed ad campaigns** (Filament CRUD, frontend placements for admin-created campaigns) can continue operating independently when self-service is disabled.

---

## Rollback Levels

| Level | Effort | Data loss | Use when |
|-------|--------|-----------|----------|
| **L1 — Feature flag OFF** | Minutes | None | Immediate production disable |
| **L2 — Route/webhook shutdown** | Minutes | None | Flag insufficient or emergency |
| **L3 — Code revert** | Hours | None | Bug in new code paths |
| **L4 — Database rollback** | Hours | Possible for new tables | Bad migration / corrupt payment data |

**Recommended first action:** always **L1**.

---

## 1. Feature Flag Disable Procedure

### Step 1 — Disable in environment

```env
# .env
SELF_SERVICE_ADS=false
```

Or in `config/features.php`:

```php
'self_service_ads' => false,
```

Then:

```bash
php artisan config:clear
php artisan config:cache   # production only
```

### Expected behavior when OFF

| Surface | Behavior |
|---------|----------|
| `/dashboard/ads*` | Blocked (404 or redirect) |
| `POST /webhooks/paymob/ads` | Returns disabled response (404/503) |
| Seller Paymob checkout | Not initiatable |
| Admin Filament `AdCampaignResource` | Continues working (admin campaigns) |
| Existing frontend placements | Admin campaigns still served if active+approved |
| Points checkout `/payment/checkout` | **Unaffected** |
| Points webhook `/payments/callback` | **Unaffected** |

### Verification

```bash
# Seller routes should fail
curl -I https://your-domain/dashboard/ads

# Points webhook still reachable (do not disable)
curl -I -X POST https://your-domain/payments/callback

# Config cached correctly
php artisan tinker --execute="dump(config('features.self_service_ads'));"
```

---

## 2. Database Rollback Order

**Only if L1–L3 insufficient.** Roll back in reverse migration order:

```bash
# 1. Audit logs (newest dependent table)
php artisan migrate:rollback --path=database/migrations/2026_06_13_100003_create_ad_campaign_audit_logs_table.php

# 2. Payment attempts
php artisan migrate:rollback --path=database/migrations/2026_06_13_100002_create_payment_attempts_table.php

# 3. Self-service columns on ad_campaigns
php artisan migrate:rollback --path=database/migrations/2026_06_13_100001_add_self_service_fields_to_ad_campaigns_table.php
```

### ⚠️ Warnings

- Rolling back column migration **drops** `seller_id`, `payment_status`, Paymob IDs, `amount_paid`, `paid_at`
- Export payment audit data before L4 if finance reconciliation needed:

```sql
SELECT * FROM payment_attempts WHERE status = 'success';
SELECT * FROM ad_campaign_audit_logs;
SELECT id, title, seller_id, payment_status, amount_paid, paid_at FROM ad_campaigns WHERE payment_status = 'paid';
```

### Do NOT roll back (preserves admin campaigns)

```
2026_06_13_000001_create_ad_campaigns_table.php
2026_06_13_000002_create_ad_campaign_logs_table.php
```

These belong to the pre-existing admin ad system documented in `reports/ad_campaigns_implementation.md`.

---

## 3. Cache Cleanup

After disable or rollback, flush ad placement caches:

```bash
php artisan cache:forget ad_campaign_hero_top
php artisan cache:forget ad_campaign_home_feed
php artisan cache:forget ad_campaign_login_page
php artisan cache:forget ad_campaign_listing_detail
php artisan cache:forget ad_campaign_search_results

# Or flush all cache (broader impact)
php artisan cache:clear
```

### Tracking dedup keys (optional cleanup)

Pattern: `ad_imp_{campaign_id}_{ip}_{uaHash}` and `ad_clk_{campaign_id}_{ip}_{uaHash}`

These expire automatically after 60 minutes. Full flush only needed if stale counts suspected.

### Queue cleanup

```bash
# Remove pending ad jobs if queue contaminated
php artisan queue:clear

# Or restart workers after deploy
php artisan queue:restart
```

---

## 4. Route Disable Procedure

If feature flag middleware fails, disable at route level:

### Option A — Comment out in `routes/ads.php`

```php
// Route::middleware(['auth', 'otp.verified', 'self_service_ads'])->group(function () { ... });
```

### Option B — Remove route file load from `bootstrap/app.php` / `web.php`

```php
// require __DIR__.'/ads.php';
```

### Webhook shutdown

```php
// Route::post('/webhooks/paymob/ads', ...);
```

Also remove CSRF exception if route removed:

```php
// bootstrap/app.php — validateCsrfTokens except:
// 'webhooks/paymob/ads',
```

Then:

```bash
php artisan route:clear
php artisan route:cache   # production
```

### Paymob dashboard

Disable or remove the ads webhook URL in Paymob merchant dashboard to stop inbound callbacks.

---

## 5. Webhook Shutdown Procedure

1. Set `SELF_SERVICE_ADS=false` and clear config cache
2. Remove webhook URL from Paymob portal: `https://{domain}/webhooks/paymob/ads`
3. Monitor logs for stray callbacks:

```bash
tail -f storage/logs/laravel.log | grep -i "paymob.*ads"
```

4. Confirm points webhook still active: `https://{domain}/payments/callback`

### In-flight payments

If disabling mid-checkout:
- Seller may complete Paymob payment but webhook returns disabled
- Reconcile manually from Paymob dashboard + `payment_attempts` table
- Re-enable flag temporarily to process backlog, or refund via Paymob

---

## 6. Recovery Checklist

After rollback or hotfix deploy:

- [ ] `config('features.self_service_ads')` returns expected value
- [ ] Seller `/dashboard/ads` blocked when OFF
- [ ] Admin Filament campaigns CRUD works
- [ ] Frontend banners show admin campaigns (if any active+approved)
- [ ] Points purchase end-to-end test passes
- [ ] Points webhook test callback passes
- [ ] Featured listing with points works
- [ ] Entitlements unchanged for test user
- [ ] Cache cleared for ad placements
- [ ] Queue workers restarted
- [ ] Paymob ads webhook URL updated in portal
- [ ] No errors in `storage/logs/laravel.log` for 15 minutes
- [ ] Finance team notified if paid campaigns orphaned

---

## 7. Code Revert (L3)

If deploying from git:

```bash
# Revert specific commit(s) for self-service feature
git log --oneline -- reports/ app/Services/AdCampaignPaymentService.php routes/ads.php

git revert <commit-sha>   # preferred over reset
```

Files safe to revert (self-service only):

```
config/features.php
config/ad_pricing.php
app/Services/AdCampaignPaymentService.php
app/Services/PaymobAdWebhookService.php
app/Http/Controllers/PaymobWebhookController.php
app/Http/Middleware/EnsureSelfServiceAdsEnabled.php
app/Models/PaymentAttempt.php
app/Models/AdCampaignAuditLog.php
app/Jobs/TrackAdClickJob.php
app/Jobs/InvalidateAdCampaignCacheJob.php
app/Livewire/Frontend/SellerAd*.php
routes/ads.php
database/migrations/2026_06_13_100*.php
```

Files to **partially revert** (keep admin campaign extensions if desired):

```
app/Models/AdCampaign.php
app/Services/AdCampaignService.php
AdCampaignResource.php
```

**Never revert:**

```
app/Services/PaymobWebhookService.php
app/Http/Controllers/Frontend/PaymentController.php
app/Services/PointService.php
```

---

## 8. Environment Variables Reference

| Variable | Purpose | Rollback action |
|----------|---------|-----------------|
| `SELF_SERVICE_ADS` | Master feature flag | Set `false` |
| `PAYMOB_API_KEY` | Shared Paymob auth | Keep (points need it) |
| `PAYMOB_HMAC_SECRET` | Shared HMAC | Keep (points need it) |
| `PAYMOB_INTEGRATION_ID` | Points integration | Keep unchanged |
| `PAYMOB_ADS_INTEGRATION_ID` | Ads-only integration (planned) | Remove from Paymob portal |

Using a **separate Paymob integration ID** for ads allows disabling ads checkout without touching points integration.

---

## 9. Contact & Escalation

| Issue | Action |
|-------|--------|
| Paid campaign not displaying | Check approval_status + payment_status + dates |
| Webhook storm after disable | Remove URL from Paymob + block route |
| Points checkout broken | **Stop rollback** — verify PaymobWebhookService untouched |
| Data reconciliation | Export `payment_attempts` + Paymob merchant dashboard |

---

## Related Documents

- `reports/self_service_ads_pre_implementation.md` — Discovery & risks
- `reports/self_service_advertising.md` — Full architecture
- `reports/ad_campaigns_implementation.md` — Admin campaigns (separate system)

---

**Last updated:** 2026-06-13 — Pre-implementation reference document.
