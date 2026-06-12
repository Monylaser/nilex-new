# TD-06 Lazy Loading Audit — Listing `location` Relationship

**Project:** Nilex Marketplace  
**Date:** 2026-06-12  
**Debt ID:** TD-06  
**Scope:** Non-destructive eager-loading fix for `Listing::location` lazy-load violations

---

## Executive Summary

Three feature tests fail with `Illuminate\Database\LazyLoadingViolationException` when rendering listing cards that access `$listing->location`. Lazy loading is disabled in non-production via `Model::preventLazyLoading(! app()->isProduction())` in `AppServiceProvider`.

**Root cause:** `HomeController` eager-loads `category` but not `location` for homepage and search queries. Blade templates access `$listing->location->name_ar`, triggering lazy-load violations when listings have a `location_id`.

**Proposed fix:** Add `'location'` to existing `->with()` calls in `HomeController::index()` and `HomeController::search()`. No query logic, filters, sorting, or view changes required.

---

## Failing Tests (Before Fix)

| # | Test Class | Test Name | HTTP Route |
|---|------------|-----------|------------|
| 1 | `Tests\Feature\Listings\ListingWorkflowTest` | `Boost System → it boosted listing appears in thefeatured section on the homepage` | `GET /` |
| 2 | `Tests\Feature\Search\AdvancedSearchTest` | `Advanced Search → it search with no query returns all published listings` | `GET /search` (`listings.search`) |
| 3 | `Tests\Feature\Search\AdvancedSearchTest` | `Advanced Search → it min_price filter excludes listings below the threshold` | `GET /search?min_price=…` |

**Baseline:** 147 tests, 144 passing, 3 failing.

---

## Lazy Loading Configuration

```php
// app/Providers/AppServiceProvider.php (line 30)
Model::preventLazyLoading(! app()->isProduction());
```

Lazy loading is enforced in local/testing environments only.

---

## Stack Traces

### Failure 1 — Homepage (`ListingWorkflowTest`)

```
Illuminate\Database\LazyLoadingViolationException: Attempted to lazy load [location] on model [App\Models\Listing] but lazy loading is disabled.

View: resources/views/frontend/home.blade.php
Controller: App\Http\Controllers\Frontend\HomeController::index() (line 31)
Test: tests/Feature/Listings/ListingWorkflowTest.php:250 — $response->assertOk()
```

Key stack frames:

```
#17 HomeController.php(31): ResponseFactory->view('frontend.home', ...)
#18 HomeController.php index()
#73 ListingWorkflowTest.php(248): TestCase->get('/')
```

Compiled view reference: `storage/framework/views/354a129f0701635d4a4816b4c05665e6.php:362`

### Failures 2 & 3 — Search (`AdvancedSearchTest`)

```
Illuminate\Database\LazyLoadingViolationException: Attempted to lazy load [location] on model [App\Models\Listing] but lazy loading is disabled.

View: resources/views/frontend/search-results.blade.php
Controller: App\Http\Controllers\Frontend\HomeController::search()
Test: tests/Feature/Search/AdvancedSearchTest.php:101 — $response->assertOk()
```

Key stack frames:

```
#69 AdvancedSearchTest.php(99): TestCase->get(route('listings.search'))
HomeController::search() → view('frontend.search-results', ...)
```

Compiled view reference: `storage/framework/views/...php:212`

---

## Blade Relationship Usage

### `resources/views/frontend/home.blade.php`

| Line | Variable | Usage |
|------|----------|-------|
| 251–252 | `$listing` (featured section) | `@if($listing->location)` / `$listing->location->name_ar` |
| 276 | `$listing` (featured section) | `@if($listing->user?->email_verified_at)` — verified seller badge |
| 354–355 | `$listing` (latest listings grid) | `@if($listing->location)` / `$listing->location->name_ar` |
| 374 | `$listing` (latest listings grid) | `@if($listing->user?->email_verified_at)` — verified seller badge |

### `resources/views/frontend/search-results.blade.php`

| Line | Variable | Usage |
|------|----------|-------|
| 211–212 | `$listing` (search results grid) | `@if($listing->location)` / `$listing->location->name_ar` |

---

## Controller & Query Audit

### Home Page — `HomeController::index()`

**File:** `app/Http/Controllers/Frontend/HomeController.php`  
**Route:** `GET /` (homepage)

```php
$featuredListings = Listing::with('category')->active()->featured()->latest()->take(3)->get();
$latestListings   = Listing::with('category')->active()->latest()->paginate(12);
```

| Query | Eager loads | Missing |
|-------|-------------|---------|
| `$featuredListings` | `category` | **`location`** |
| `$latestListings` | `category` | **`location`** |

**View variables passed:** `categories`, `featuredListings`, `latestListings`

### Search Page — `HomeController::search()`

**File:** `app/Http/Controllers/Frontend/HomeController.php`  
**Route:** `GET /search` → `listings.search`

```php
->query(function ($q) use ($minPrice, $maxPrice, $categoryId, $provinceId) {
    $q->with('category')->where('status', Listing::STATUS_PUBLISHED);
    // ... price/category/province filters unchanged
});
$listings = $search->paginate(12)->withQueryString();
```

| Query | Eager loads | Missing |
|-------|-------------|---------|
| Scout search + Eloquent `.query()` callback | `category` | **`location`** |

**View variables passed:** `listings`, `query`, `lat`, `lng`, `radius`, `minPrice`, `maxPrice`, `categoryId`, `provinceId`

### Already Correct (Not Modified)

| Location | Eager loads |
|----------|-------------|
| `ListingController::show()` | `carBrand`, `carModel`, `province`, `location` via `$listing->load([...])` |
| `Livewire\Frontend\ListingGrid::render()` | `category`, `province`, `location`, `carBrand` via Scout `.query()` |

---

## Model Relationship

```php
// app/Models/Listing.php
public function location(): BelongsTo
{
    return $this->belongsTo(Location::class);
}
```

Foreign key: `location_id` on `listings` table.

---

## Proposed Minimal Fix

**File:** `app/Http/Controllers/Frontend/HomeController.php`

### Change 1 — `index()` (lines 27–28)

```php
// BEFORE
$featuredListings = Listing::with('category')->active()->featured()->latest()->take(3)->get();
$latestListings   = Listing::with('category')->active()->latest()->paginate(12);

// AFTER
$featuredListings = Listing::with(['category', 'location', 'user'])->active()->featured()->latest()->take(3)->get();
$latestListings   = Listing::with(['category', 'location', 'user'])->active()->latest()->paginate(12);
```

**Note:** `user` is required because `home.blade.php` checks `$listing->user?->email_verified_at` for the verified-seller badge. This violation surfaced after fixing `location` (same test, deeper render path).

### Change 2 — `search()` (line 162)

```php
// BEFORE
$q->with('category')->where('status', Listing::STATUS_PUBLISHED);

// AFTER
$q->with(['category', 'location'])->where('status', Listing::STATUS_PUBLISHED);
```

**What is NOT changed:** filters, sorting, pagination, scopes, caching, views, routes, or any existing feature.

---

## Regression Protection

Existing tests already cover the affected pages:

- `ListingWorkflowTest` — homepage with boosted/featured listings
- `AdvancedSearchTest` — search with no query and with `min_price` filter

No new tests required unless the full suite still fails after the fix.

---

## Success Criteria

- `php artisan test` → **147 passed, 0 failed**
- Only additive eager-loading changes in `HomeController.php`
- No removals, refactors, or behavior changes
