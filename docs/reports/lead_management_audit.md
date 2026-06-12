# Lead Management Audit — Phase 0

**Project:** Nilex Marketplace  
**Date:** 2026-06-12  
**Task:** P1 Seller Lead Management — pre-implementation audit  
**Status:** COMPLETE — ready for implementation

---

## Executive Summary

Nilex already captures three high-intent buyer actions on listings: **phone reveal**, **WhatsApp click**, and **offer submission**. These are stored in dedicated event tables (`listing_phone_clicks`, `listing_whatsapp_clicks`, `offers`) plus aggregate counters on `listings`. The seller dashboard today exposes **counts only** via `SellerListingAnalyticsService`; it does not surface individual interested buyers.

Phase 1 lead management should **denormalize existing events** into a new `seller_leads` layer without altering tracking logic, payment, PointPlan, or analytics services.

---

## 1. Existing Lead Data Inventory

### 1.1 `listing_views`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `listing_id` | FK → listings | cascade delete |
| `user_id` | nullable FK → users | null for guests |
| `ip_address` | string(45) nullable | guest dedup key |
| `created_at` | timestamp | no `updated_at` |

**Population:** `ListingLeadTrackingService::recordView()` on listing page load. Dedup: 24h per user or IP.

**Lead relevance:** **Not a Phase 1 lead source.** Views indicate browsing interest, not contact intent. Including views would inflate “leads” and expose guest IP data to sellers (privacy risk).

**Seller exposure today:** Count only via `SellerListingAnalyticsService::totalViewsForUser()`.

---

### 1.2 `listing_phone_clicks`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `listing_id` | FK → listings | |
| `user_id` | nullable FK → users | |
| `created_at` | timestamp | |

**Population:** `ListingLeadTrackingService::recordPhoneClick()` via `POST /listings/{listing}/reveal-phone`. Endpoint requires authentication (`401` for guests). Dedup: 1h per authenticated user per listing.

**Lead relevance:** **Phase 1 lead source — Phone Reveal.** Each row is a verified buyer who requested the seller’s phone number.

**Buyer identity:** Always authenticated when created through the public API; `user_id` should be populated in normal operation.

---

### 1.3 `listing_whatsapp_clicks`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `listing_id` | FK → listings | |
| `user_id` | nullable FK → users | guests allowed |
| `created_at` | timestamp | |

**Population:** `ListingLeadTrackingService::recordWhatsappClick()` via `POST /listings/{listing}/whatsapp-click`. Also increments `listings.whatsapp_clicks` counter on first click. Dedup: 1h per authenticated user (guests not deduped at user level).

**Lead relevance:** **Phase 1 lead source — WhatsApp Click.** Each row is a buyer who initiated WhatsApp contact intent.

**Buyer identity:** May be anonymous (`user_id` null) for guest clicks.

---

### 1.4 `offers`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `listing_id` | FK → listings | |
| `sender_id` | FK → users | buyer |
| `receiver_id` | FK → users | seller (listing owner) |
| `amount` | decimal(12,2) | |
| `message` | text nullable | |
| `status` | enum | `pending`, `accepted`, `rejected`, `canceled` |
| `created_at`, `updated_at` | timestamps | |

**Population:** `ListingController::makeOffer()` — authenticated buyers only; one pending offer per buyer per listing.

**Lead relevance:** **Phase 1 lead source — Offer Submission.** Richest lead data: buyer identity, amount, message.

**Seller exposure today:** Pending offers shown in `UserDashboard` Livewire component (`incomingOffers`); accept/reject actions exist. Lead management complements this with unified pipeline + CRM statuses.

---

### 1.5 `SellerListingAnalyticsService`

Read-only aggregation over event tables scoped to seller-owned listings:

| Method | Returns |
|--------|---------|
| `totalViewsForUser()` | count of `listing_views` |
| `totalPhoneClicksForUser()` | count of `listing_phone_clicks` |
| `totalWhatsappClicksForUser()` | count of `listing_whatsapp_clicks` |
| `getDashboardStats()` | array with `views_events`, `phone_clicks`, `whatsapp_clicks_events` |

**Constraints for implementation:** Do **not** modify this service. Lead management is a parallel read/write layer.

---

## 2. User & Ownership Relationships

```
User (seller)
  └── hasMany Listing (user_id)
        ├── hasMany ListingView
        ├── hasMany ListingPhoneClick
        ├── hasMany ListingWhatsappClick
        └── hasMany Offer

User (buyer)
  └── sender on Offer
  └── user_id on click/view events (nullable for guests)

Offer
  ├── sender_id → buyer
  ├── receiver_id → seller (denormalized for query speed)
  └── listing_id → listing.user_id must match receiver_id
```

**Ownership rule for seller lead access:**

> A seller may only see leads where `seller_id = auth()->id()`, derived from `listing.user_id` or `offer.receiver_id`.

**Cross-seller isolation:** Events on another seller’s listings must never appear. Enforced via policy + query scopes.

---

## 3. What Can Be Safely Exposed to Sellers

| Data | Phone Reveal | WhatsApp Click | Offer | Expose? |
|------|-------------|----------------|-------|---------|
| Lead timestamp | ✅ | ✅ | ✅ | **Yes** |
| Listing title / link | ✅ | ✅ | ✅ | **Yes** |
| Lead source type | ✅ | ✅ | ✅ | **Yes** |
| Buyer display name | ✅ (if auth) | ⚠️ if auth | ✅ | **Yes** (when `user_id`/`sender_id` present) |
| Buyer phone / email | ❌ | ❌ | ❌ | **No** — use in-app contact / existing offer flow |
| Offer amount / message | — | — | ✅ | **Yes** (already shown in dashboard) |
| IP address | — | — | — | **Never** |
| View counts | aggregate only | — | — | **Counts only** (existing analytics) |
| CRM status (new/contacted/…) | new field | new field | new field | **Yes** (seller-managed) |

**Anonymous WhatsApp leads:** Show as “Anonymous visitor” with listing + timestamp + source. Do not fabricate buyer profiles.

---

## 4. GDPR / Privacy Implications

### 4.1 Legal basis

- **Phone reveal & offers:** Contractual / legitimate interest — authenticated users take explicit actions toward a transaction.
- **WhatsApp clicks (guests):** Legitimate interest for seller response; minimal data (timestamp + listing). Document in privacy policy that contact-intent actions may be shared with listing owners.

### 4.2 Data minimization (Phase 1)

- Do **not** copy `ip_address` from `listing_views` into seller-facing lead records.
- Do **not** expose buyer email, phone, IP, device fingerprint, or OTP fields to sellers.
- Store only: `seller_id`, `listing_id`, optional `buyer_id`, source reference, CRM status, activity log.

### 4.3 Buyer rights

Privacy policy (`privacy-policy` legal page) already states users may request access, correction, or deletion via `privacy@nilex.com`. Lead records referencing a buyer should be deletable/anonymizable when a user account is deleted (`buyer_id` → `nullOnDelete` or cascade policy TBD — recommend `nullOnDelete` to preserve seller pipeline history without PII).

### 4.4 Consent

Cookie consent banner exists (`cookie-consent.blade.php`). Lead tracking is action-based (not cookie analytics). No additional consent gate required for Phase 1, but CRM exports/notifications (future) should revisit consent.

---

## 5. Proposed Architecture (Additive)

### 5.1 New tables

**`seller_leads`**

| Column | Purpose |
|--------|---------|
| `seller_id` | FK users — listing owner |
| `listing_id` | FK listings |
| `buyer_id` | nullable FK users — interested party |
| `source_type` | `phone_reveal`, `whatsapp_click`, `offer` |
| `source_id` | ID in source table |
| `status` | `new`, `contacted`, `qualified`, `closed` |
| `created_at`, `updated_at` | |

Unique index: `(source_type, source_id)` — one lead per tracked event, no synthetic duplicates.

**`seller_lead_activities`** (timeline / future CRM)

| Column | Purpose |
|--------|---------|
| `seller_lead_id` | FK |
| `type` | `created`, `status_changed`, … |
| `metadata` | JSON (old/new status, etc.) |
| `created_at` | |

### 5.2 Population strategy

Use **model observers** on `ListingPhoneClick`, `ListingWhatsappClick`, and `Offer` `created` events → call new `SellerLeadService`. Does **not** modify `ListingLeadTrackingService`.

Optional **`leads:backfill`** artisan command to import historical events (additive, idempotent via unique constraint).

### 5.3 UI

| Route | Component |
|-------|-----------|
| `GET /dashboard/leads` | Lead list with filters (today / 7d / 30d / all) |
| `GET /dashboard/leads/{lead}` | Detail: listing, source, timeline, status update |

Middleware: `auth`, `otp.verified` (consistent with existing dashboard).

### 5.4 Future-ready (not Phase 1)

| Capability | Extension point |
|------------|-----------------|
| CRM integration | `seller_leads.external_crm_id`, webhook dispatch on status change |
| Lead scoring | `score` column + `SellerLeadScoringService` |
| Notifications | Laravel notifications on `SellerLeadCreated` event |
| Exports | CSV export command reading `seller_leads` scoped to seller |

---

## 6. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Analytics regression | Observers only; no changes to tracking service or counters |
| Duplicate leads | Unique `(source_type, source_id)` |
| Cross-seller data leak | Policy + `where('seller_id', auth()->id())` |
| PII over-exposure | Whitelist display fields; no IP/email/phone |
| Breaking dashboard | Additive routes/components; no UserDashboard logic removal |
| Existing tests fail | No modifications to tested services; new isolated tests |

---

## 7. Audit Conclusions

1. **Sufficient raw data exists** for Phase 1 leads from phone clicks, WhatsApp clicks, and offers.
2. **Views should remain analytics-only** — not promoted to leads in Phase 1.
3. **`seller_leads` denormalization** is the recommended additive pattern — decouples CRM workflow from immutable event logs.
4. **Observers + `SellerLeadService`** satisfy the “do not modify analytics tracking logic” constraint.
5. **Privacy-safe seller view:** listing, source, timestamp, optional buyer first name, offer details, CRM status — never IP or direct contact PII.

---

## 8. Implementation Checklist (Post-Audit)

- [ ] Migration: `seller_leads`, `seller_lead_activities`
- [ ] Models, policy, service, observers
- [ ] Livewire: index + detail
- [ ] Routes under authenticated group
- [ ] Tests: creation, visibility, ownership, access
- [ ] Implementation report with test results

---

*Audit completed. No application code changed during Phase 0.*
