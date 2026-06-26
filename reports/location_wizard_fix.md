# Listing Wizard — Location Step Fix (free-text → Location hierarchy)

**Status:** ✅ COMPLETED
**Date:** 2026-06-19
**Role:** Senior Laravel 13 Frontend + Backend Integration Engineer

## 1. Problem

The Listing Wizard's Location step used a **free-text** field and submitted the wrong key:

```js
formData.location          // free text, e.g. "القاهرة - مدينة نصر"
fd.append('location', ...)  // wrong key — backend never reads it
```

Meanwhile the backend already:
- Validates `location_id => nullable|exists:locations,id`
- Persists `$listing->location_id`
- Uses the `Listing` → `Location` relationship
- Has a full `Location` hierarchy (Governorate → City)

So the free-text value was silently discarded and `location_id` was always `null`.

## 2. Existing `Location` model structure (reused, not modified)

`app/Models/Location.php`:

- **Hierarchy fields:** `parent_id`, `level`, `sort_order`, `is_active`, `name_ar`, `name_en`.
- **Constants:** `LEVEL_GOVERNORATE = 0`, `LEVEL_CITY = 1`.
- **Relationships:**
  - `parent()` — `belongsTo(Location, 'parent_id')`
  - `children()` — `hasMany(Location, 'parent_id')->orderBy('sort_order')`
- **Scopes:** `active()`, `governorates()` (`level=0`), `cities()` (`level=1`).

Hierarchy used: **Governorate** (`level=0`, `parent_id=null`) → **City** (`level=1`, `parent_id=governorate.id`). The selected **city** id is stored in `listings.location_id`. This mirrors the admin `ListingForm` (`province_id` → `location_id`) exactly.

## 3. Files modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Frontend/HomeController.php` | `create()` now passes active governorates eager-loaded with active child cities. Added `use App\Models\Location;`. |
| `resources/views/frontend/listings/create.blade.php` | Replaced free-text input with Governorate→City dependent dropdowns; updated Alpine state, derived `cities`, change handler, and submit payload. |

**Not modified:** `Listing` model, `HomeController@store` validation, `HomeController@search`, the `locations` table/migrations.

## 4. Backend change (`HomeController@create`)

```php
$governorates = Location::governorates()
    ->active()
    ->with(['children' => function ($query) {
        $query->active()->orderBy('sort_order');
    }])
    ->get(['id', 'name_ar', 'parent_id', 'level', 'sort_order']);

return view('frontend.listings.create', compact('categories', 'governorates'));
```

- Uses existing `governorates()` + `active()` scopes and the `children()` relationship.
- Eager-loads only active cities, ordered by `sort_order` (no N+1).

## 5. Frontend changes (`create.blade.php`)

### 5.1 Data bootstrap
```js
const NILEX_LOCATIONS = @json($governorates);
```

### 5.2 Alpine state
```js
governorates: NILEX_LOCATIONS,

formData: {
    // ...
    phone: '',
    governorate_id: '',   // UI-only selector (not submitted)
    location_id: '',      // submitted city id
},
```
The old `location: ''` free-text field was **removed**.

### 5.3 Dependent dropdown logic
```js
// Child cities of the currently selected governorate.
get cities() {
    const gov = this.governorates.find(g => g.id == this.formData.governorate_id);
    return (gov && Array.isArray(gov.children)) ? gov.children : [];
},

// Reset chosen city when governorate changes.
onGovernorateChange() {
    this.formData.location_id = '';
    delete this.errors.location_id;
},
```

### 5.4 Markup (RTL Arabic preserved)
- **Governorate** `<select>` bound to `formData.governorate_id`, `@change="onGovernorateChange()"`.
- **City** `<select>` bound to `formData.location_id`, **disabled until** a governorate is chosen, options driven by `cities`.

### 5.5 Submit payload
```js
fd.append('location_id', this.formData.location_id);
```
The old `fd.append('location', ...)` was removed. Empty selection submits `''`, which Laravel's `ConvertEmptyStringsToNull` turns into `null` — satisfying `nullable|exists:locations,id`.

### 5.6 Server error routing
`mapServerErrors()` now routes `location_id` (and `phone`) validation errors to **step 4**.

## 6. Requirements checklist

| # | Requirement | Status |
|---|-------------|--------|
| 1 | Remove free-text location input | ✅ |
| 2 | Use existing Location model data | ✅ |
| 3 | Dependent dropdowns Governorate → City | ✅ |
| 4 | Governorate change loads only its child cities | ✅ |
| 5 | Store selected city id in `formData.location_id` | ✅ |
| 6 | Submit `fd.append('location_id', ...)` | ✅ |
| 7 | `HomeController` validation unchanged (`nullable|exists:locations,id`) | ✅ |
| 8 | No new database columns | ✅ |
| 9 | `Listing` model not modified | ✅ |
| 10 | Search logic not modified | ✅ |
| 11 | Reuse existing hierarchy/relationships (`governorates()`, `active()`, `children()`) | ✅ |
| 12 | RTL Arabic UI maintained | ✅ |
| 13 | All existing wizard functionality preserved (steps, validation, images, custom fields, localStorage, AJAX) | ✅ |

## 7. Notes

- `governorate_id` is a **UI-only** field for cascading; only `location_id` is submitted.
- Stale `location` keys in any user's `localStorage` are harmless (ignored by the template).
- No migrations required.

**Done.** Stopping here as instructed.
