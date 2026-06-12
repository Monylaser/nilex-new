# TD-04 Implementation Report — Option A

**Project:** Nilex Marketplace  
**Date:** 2026-06-11  
**Debt ID:** TD-04  
**Implementation:** Option A — `Schema::hasIndex()` cross-database introspection  
**Reference:** `docs/reports/td_04_sqlite_migration_audit.md`

---

## Executive Summary

Option A has been implemented. MySQL-specific `SHOW INDEX` queries in `2026_06_11_000002_add_lead_funnel_analytics_indexes.php` were replaced with Laravel `Schema::hasIndex()` introspection. The unused `DB` facade import was removed.

**Result:** Test suite unblocked. **142 of 145 tests pass** (up from 2). The remaining **3 failures** are unrelated to TD-04 — all are `LazyLoadingViolationException` for `Listing::location` in frontend views. These were masked by the migration bootstrap failure and are documented below for follow-up; they were **not** fixed in this change.

Migration verification on SQLite (file DB) and MySQL confirms indexes are created, idempotent re-runs succeed, and rollback removes indexes cleanly.

---

## Implementation Changes

**File modified:** `database/migrations/2026_06_11_000002_add_lead_funnel_analytics_indexes.php`

| Before | After |
|--------|-------|
| `DB::select('SHOW INDEX FROM ...')` for standalone `created_at` check | `Schema::hasIndex($table, ['created_at'])` |
| `DB::select('SHOW INDEX FROM ...')` for named index check in `down()` | `Schema::hasIndex($table, $indexName)` |
| `use Illuminate\Support\Facades\DB` | Removed (unused) |

**Behavior preserved:**

- `up()` skips index creation when a standalone `created_at` index already exists (composite `(listing_id, created_at)` indexes do not match `['created_at']` alone).
- `down()` skips drop when the named index is absent.
- `Schema::hasTable()` guards remain unchanged.
- Production MySQL deployments with existing indexes remain safe (idempotent re-run verified).

---

## Test Suite Results

**Command:** `php artisan test`

### Before fix

| Metric | Value |
|--------|-------|
| Total tests | 145 |
| Passed | 2 |
| Failed | 143 |
| Assertions | 4 |
| Duration | ~80s |

**First failing test:** `Tests\Unit\Auth\OtpServiceTest` → `it verifies a valid otp and clears replay state`

**Exception:** `QueryException` — `SQLSTATE[HY000]: General error: 1 near "SHOW": syntax error`  
**SQL:** `SHOW INDEX FROM `listing_views` WHERE Column_name = created_at AND Seq_in_index = 1`  
**Connection:** `sqlite`, `:memory:`

All 143 failures shared the identical migration bootstrap root cause.

### After fix

| Metric | Value |
|--------|-------|
| Total tests | 145 |
| Passed | 142 |
| Failed | 3 |
| Assertions | 328 |
| Duration | ~125s |

**Delta:** +140 tests unblocked (+98.6% of previously failing suite).

### New first failing test (post-fix)

| Field | Value |
|-------|-------|
| Class | `Tests\Feature\Listings\ListingWorkflowTest` |
| Method | `Boost System` → `it boosted listing appears in the featured…` |
| Exception | `Illuminate\Database\LazyLoadingViolationException` |
| Message | Attempted to lazy load `[location]` on model `[App\Models\Listing]` but lazy loading is disabled |
| View | `resources/views/frontend/home.blade.php` (via `HomeController@index`) |
| Assertion | Expected 200, received 500 |

---

## Remaining Failures — Grouped by Root Cause

All 3 remaining failures share a **single root cause** unrelated to TD-04.

### Root cause: Lazy loading violation — `Listing::location`

| # | Test | View / Route |
|---|------|--------------|
| 1 | `Tests\Feature\Listings\ListingWorkflowTest` → `it boosted listing appears in the featured…` | `frontend/home.blade.php` — `HomeController@index` |
| 2 | `Tests\Feature\Search\AdvancedSearchTest` → `it search with no query returns all publish…` | `frontend/search-results.blade.php` — search route |
| 3 | `Tests\Feature\Search\AdvancedSearchTest` → `it min_price filter excludes listings below…` | `frontend/search-results.blade.php` — search route |

**Analysis:** Laravel strict mode (`Model::preventLazyLoading`) is enabled in tests. Controllers query listings without eager-loading `location`, but Blade templates access `$listing->location`. These tests likely never reached view rendering before TD-04 blocked migration bootstrap.

**Recommended follow-up (out of TD-04 scope):** Eager-load `location` in `HomeController`, search controller/query builder, or adjust views to avoid implicit lazy loads.

---

## Migration Verification Results

### SQLite (file database, single-process script)

Script: `scripts/verify_td04_sqlite.php`  
Artifact: `storage/app/td04_sqlite_migration_verification.json`

| Check | Result |
|-------|--------|
| `migrate:fresh` | Success |
| `listing_views_created_at_index` (by name / by column) | `true` / `true` |
| `listing_phone_clicks_created_at_index` | `true` / `true` |
| `listing_whatsapp_clicks_created_at_index` | `true` / `true` |
| `offers_created_at_index` | `true` / `true` |
| Idempotent `up()` re-run | Success |
| `down()` rollback | Success |
| Indexes after `down()` | All `false` (correctly removed) |

### SQLite (`:memory:` via `php artisan migrate:fresh`)

| Check | Result |
|-------|--------|
| Migration `2026_06_11_000002` | DONE (~4–270ms depending on seed data) |
| Full test bootstrap (`RefreshDatabase`) | Success — 142 tests pass |

### MySQL (production `.env` connection)

Script: `scripts/verify_td04_migration.php`  
Artifact: `storage/app/td04_migration_verification.json`

| Check | Result |
|-------|--------|
| Driver | `mysql` |
| All four indexes (by name / by column) | `true` / `true` |
| Idempotent `up()` re-run | Success |

### Cross-database portability

| Driver | Migration `up()` | Idempotent re-run | `down()` rollback | `hasIndex` introspection |
|--------|------------------|-------------------|-------------------|--------------------------|
| SQLite | Pass | Pass | Pass | Pass |
| MySQL | Pass | Pass | Not re-tested separately* | Pass |
| MariaDB | Expected pass† | Expected pass† | Expected pass† | Laravel grammar support |
| PostgreSQL | Expected pass† | Expected pass† | Expected pass† | Laravel grammar support |

\* MySQL rollback not isolated in this verification run; SQLite rollback verified equivalent `down()` logic.  
† Not executed in this environment; Laravel `Schema::hasIndex()` uses driver-specific grammars for all listed drivers.

---

## Risk Assessment

| Risk | Before | After | Notes |
|------|--------|-------|-------|
| CI/test suite blocked by migration | Critical | **Resolved** | 140 tests unblocked |
| SQLite test bootstrap failure | Critical | **Resolved** | No `SHOW INDEX` usage |
| PostgreSQL portability | High | **Low** | Schema introspection is cross-DB |
| Production MySQL deploy failure | Low | Low | Idempotent re-run verified on MySQL |
| Duplicate index on re-run | Low | Low | `hasIndex(['created_at'])` preserves skip logic |
| Non-standard index name on `created_at` | Low | Low | Same as audit: unlikely in this codebase |
| Pre-existing lazy-load test failures | Hidden | **Visible** | 3 tests; separate debt item |

**Overall TD-04 risk:** Low. Implementation matches audit Option A recommendation with verified behavior on SQLite and MySQL.

---

## Deliverables Checklist

| Deliverable | Status |
|-------------|--------|
| Migration refactored (`2026_06_11_000002`) | Done |
| `SHOW INDEX` removed | Done |
| `DB` import removed | Done |
| `php artisan test` executed | Done |
| Implementation report (this document) | Done |
| DOCX export | `docs/reports/td_04_implementation_report.docx` |

---

## Appendix — Code Diff Summary

```php
// createdAtIndexExists()
return Schema::hasIndex($table, ['created_at']);

// indexExists()
return Schema::hasIndex($table, $indexName);
```

Removed: `use Illuminate\Support\Facades\DB;`

---

*End of TD-04 Implementation Report*
