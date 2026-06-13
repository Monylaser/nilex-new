# Plan Type Implementation

## Overview

This change introduces `plan_type` categorization for point plans (**Individual** vs **Company**) and lays the groundwork for dashboard feature gating (e.g. Business Analytics modules).

---

## Migration

**File:** `database/migrations/2026_06_12_200001_add_plan_type_to_point_plans_table.php`

| Table         | Column      | Type   | Default        | Notes                          |
|---------------|-------------|--------|----------------|--------------------------------|
| `point_plans` | `plan_type` | string | `'individual'` | After `is_active`              |
| `users`       | `plan_type` | string | `'individual'` | After `plan_tier` (for gating) |

Run:

```bash
php artisan migrate
```

Rollback:

```bash
php artisan migrate:rollback --step=1
```

---

## Changes Made

### 1. Database

- Added `plan_type` to `point_plans` with default `'individual'`.
- Added `plan_type` to `users` so `auth()->user()->plan_type` works for feature gating.

### 2. `PointPlan` model

- Added constants: `PLAN_TYPE_INDIVIDUAL`, `PLAN_TYPE_COMPANY`.
- Added `plan_type` to `$fillable`.

### 3. `PointPlanResource` (Filament Admin)

**Added only — no existing fields, columns, or logic were removed.**

- **Form:** `Select` field `plan_type` with options:
  - `individual` → Individual
  - `company` → Company
- **Table:** `TextColumn` for `plan_type` rendered as a badge (Company = primary, Individual = gray).

### 4. Feature gating foundation

**Trait:** `app/Models/Concerns/HasPlanType.php`

Used by the `User` model. Provides:

| Method / constant              | Purpose                                      |
|--------------------------------|----------------------------------------------|
| `PLAN_TYPE_INDIVIDUAL`         | `'individual'`                               |
| `PLAN_TYPE_COMPANY`            | `'company'`                                  |
| `hasCompanyPlan()`             | Returns `true` when plan type is company     |
| `hasIndividualPlan()`          | Returns `true` when plan type is individual  |
| `isCompanyPlan()`              | Alias for `hasCompanyPlan()`                 |

**User model:** uses `HasPlanType` trait; `plan_type` added to `$fillable`.

**EntitlementService:** on plan purchase, syncs `users.plan_type` from the purchased `PointPlan` (additive logic only).

---

## Feature Gating Usage

### Blade

```blade
@if(auth()->user()->plan_type === 'company')
    {{-- Business Analytics, company-only modules --}}
@endif
```

Or with the helper:

```blade
@if(auth()->user()->isCompanyPlan())
    {{-- Company dashboard modules --}}
@endif
```

### Livewire / Controllers

```php
if (auth()->user()->hasCompanyPlan()) {
    // Show Business Analytics widget
}
```

### Constants (recommended)

```php
use App\Models\User;

if (auth()->user()->plan_type === User::PLAN_TYPE_COMPANY) {
    // ...
}
```

### Example: conditional dashboard section

```blade
@if(auth()->user()->isCompanyPlan())
    @livewire('frontend.business-dashboard')
@else
    <x-upgrade-prompt feature="business_dashboard" />
@endif
```

---

## Integrity Confirmation

**PointPlanResource.php — verified:**

| Area   | Status |
|--------|--------|
| Form fields (`name_ar`, `name_en`, `points`, `price`, `description`, `is_active`) | Unchanged |
| Table columns (`id`, `name_ar`, `points`, `price`, `is_active`, `created_at`)   | Unchanged |
| Validations, defaults, sorting                                                    | Unchanged |
| Pages configuration                                                               | Unchanged |

Only **additions** were made:

- `Select::make('plan_type')` appended to the form
- `TextColumn::make('plan_type')` inserted before `created_at` in the table

---

## Admin Workflow

1. Open **Filament Admin → خطط النقاط (Point Plans)**.
2. Create or edit a plan.
3. Set **نوع الخطة (Plan Type)** to **Individual** or **Company**.
4. Save — the badge appears in the plans list.

Existing plans receive `plan_type = 'individual'` automatically via the migration default.

---

## Next Steps (optional)

- Gate specific Livewire components / routes with `HasPlanType` helpers.
- Seed company plan types for Business-tier plans in `PlanEntitlementSeeder`.
- Expose `plan_type` on the public pricing page for Individual vs Company plan groupings.
