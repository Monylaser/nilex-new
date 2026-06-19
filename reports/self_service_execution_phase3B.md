# Self-Service Advertising — Phase 3B Execution Report

**Date:** 2026-06-13  
**Project:** Nilex Marketplace  
**Scope:** Filament Admin Enhancements  
**Status:** Complete — STOP before Phase 4

---

## 1. Resource Changes

### `AdCampaignResource` — Extended (NOT regenerated)

**Preserved:**
- Navigation icon, group, labels, sort order
- Form schema (sections, fields, media upload, scheduling)
- Existing table columns (image, title, placement, status, priority, views, clicks, CTR, starts_at, ends_at)
- Filters (status, placement, starts_at range)
- Pages (`ListAdCampaigns`, `CreateAdCampaign`, `EditAdCampaign`)
- Bulk delete toolbar action
- Default sort (`priority` desc)

**Added — Table columns (appended after placement):**

| Column | Type | Notes |
|--------|------|-------|
| `seller.name` | TextColumn | Searchable, placeholder for legacy admin campaigns |
| `payment_status` | BadgeColumn | paid=green, pending=yellow, failed=red, refunded=gray |
| `approval_status` | BadgeColumn | approved=green, pending=yellow, rejected=red |
| `amount_paid` | TextColumn | EGP money format, sortable |

**Note:** `starts_at` and `ends_at` were already present in the table; not duplicated.

**Added — Query optimization:**
- `getEloquentQuery()` eager-loads `seller` relationship

**Added — Helper option maps:**
- `paymentStatusOptions()` — Arabic labels
- `approvalStatusOptions()` — Arabic labels

---

## 2. Actions Added

### Approve (`approve`)

| Rule | Value |
|------|-------|
| Visible when | `payment_status = paid` AND `approval_status != approved` |
| Authorization | `AdCampaignPolicy::approve` → `Update:AdCampaign` (Shield) |
| Confirmation | Required modal |

**Logic (via `AdCampaignService::approveCampaign`):**
1. `approval_status = approved`
2. `rejected_reason = null`
3. `status = active`
4. `starts_at = now()`
5. `ends_at = now()->addDays(duration_days)` (fallback: 7 days if null)
6. Audit log created
7. Placement cache cleared
8. Seller notification dispatched (queued, if `seller_id` set)

### Reject (`reject`)

| Rule | Value |
|------|-------|
| Visible when | `approval_status != rejected` |
| Authorization | `AdCampaignPolicy::reject` → `Update:AdCampaign` (Shield) |
| Modal field | `rejected_reason` (required Textarea) |

**Logic (via `AdCampaignService::rejectCampaign`):**
1. `approval_status = rejected`
2. `rejected_reason` stored from modal
3. Audit log created
4. Placement cache cleared
5. Seller notification dispatched (queued, if `seller_id` set)

**Preserved record actions:** `EditAction`, `DeleteAction`

---

## 3. Policy Review

### `AdCampaignPolicy` — Extended (NOT replaced)

**Preserved seller methods:**
- `view()` — seller ownership OR `View:AdCampaign` (admin)
- `retryPayment()` — seller owns campaign + `payment_status = failed`

**Added Filament Shield methods (matches `ListingPolicy` / `CategoryPolicy` pattern):**
- `viewAny`, `view`, `create`, `update`, `delete`, `deleteAny`
- `restore`, `forceDelete`, `restoreAny`, `forceDeleteAny`
- `replicate`, `reorder`

**Added admin moderation methods:**
- `approve()` → requires `Update:AdCampaign`
- `reject()` → requires `Update:AdCampaign`

**Authorization in Filament actions:**
- `->authorize('approve')` and `->authorize('reject')` — never bypassed

**Note:** Shield permissions for `AdCampaign` should be generated via `php artisan shield:generate` when resource is stable. `super_admin` bypasses via Filament Shield gate.

---

## 4. Audit Logging

**Model:** `AdCampaignAuditLog` (created in Phase 1, used in Phase 3B)

**Stored on approve/reject:**

| Field | Source |
|-------|--------|
| `admin_id` | Authenticated Filament admin (`Auth::id()`) |
| `campaign_id` | Target campaign |
| `action` | `approve` or `reject` |
| `old_values` | JSON snapshot before change |
| `new_values` | JSON snapshot after change |
| `created_at` | Auto timestamp |

**Snapshot fields:** `approval_status`, `rejected_reason`, `status`, `starts_at`, `ends_at`, `payment_status`, `amount_paid`

**Location:** `AdCampaignService::approveCampaign()` / `rejectCampaign()` inside DB transaction

**Separate from:** `ad_campaign_logs` (impression/click tracking — unchanged)

---

## 5. Notifications

| Class | Trigger | Channels | Recipient |
|-------|---------|----------|-----------|
| `AdCampaignApprovedNotification` | Approve action | database, mail | Campaign seller |
| `AdCampaignRejectedNotification` | Reject action | database, mail | Campaign seller |

Both implement `ShouldQueue`. Seller is notified only when `seller_id` is set (self-service campaigns).

---

## 6. Cache Invalidation

- `AdCampaignService::clearCampaignCache()` called after approve/reject
- `AdCampaignObserver::saved()` also clears placement cache on model update (existing behavior)

---

## 7. Files Modified

```
app/Filament/Admin/Resources/AdCampaigns/AdCampaignResource.php
app/Services/AdCampaignService.php
app/Policies/AdCampaignPolicy.php
```

---

## 8. Files Created

```
app/Notifications/AdCampaignApprovedNotification.php
app/Notifications/AdCampaignRejectedNotification.php
reports/self_service_execution_phase3B.md
```

---

## 9. Files Intentionally NOT Modified

```
app/Filament/Admin/Resources/AdCampaigns/Pages/ListAdCampaigns.php
app/Filament/Admin/Resources/AdCampaigns/Pages/CreateAdCampaign.php
app/Filament/Admin/Resources/AdCampaigns/Pages/EditAdCampaign.php
app/Filament/Admin/Pages/AdCampaignStats.php
app/Models/AdCampaign.php
app/Observers/AdCampaignObserver.php
Seller dashboard / public banners / frontend placements
```

---

## 10. Manual Testing Checklist

### Table
- [ ] Seller column shows seller name for self-service campaigns
- [ ] Payment status badge colors: paid=green, pending=yellow, failed=red
- [ ] Approval status badge colors: approved=green, pending=yellow, rejected=red
- [ ] Amount paid displays in EGP

### Approve Action
- [ ] Hidden when `payment_status != paid`
- [ ] Hidden when already approved
- [ ] Sets `starts_at`, `ends_at` from `duration_days`
- [ ] Row appears in `ad_campaign_audit_logs`
- [ ] Seller receives notification (queue worker running)

### Reject Action
- [ ] Hidden when already rejected
- [ ] Modal requires `rejected_reason`
- [ ] Reason stored on campaign
- [ ] Audit log row created
- [ ] Seller receives notification

### Authorization
- [ ] Non-admin cannot access Filament actions
- [ ] Actions respect `Update:AdCampaign` permission

### Legacy Campaigns
- [ ] Admin-created campaigns (null `payment_status`) still editable via form
- [ ] Approve action hidden for unpaid legacy campaigns (expected)

---

## NOT Implemented (Out of Scope — Phase 3B)

- Seller dashboard changes
- Public banners / frontend placements
- Filament form changes for self-service fields
- Shield permission generation (`shield:generate`)
- List page tabs (pending approval queue)

---

**Awaiting explicit user approval before continuing to Phase 4.**
