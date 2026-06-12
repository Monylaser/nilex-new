# TD-04 — SQLite Migration Compatibility Audit

**Project:** Nilex Marketplace  
**Date:** 2026-06-11  
**Debt ID:** TD-04  
**Scope:** `database/migrations/2026_06_11_000002_add_lead_funnel_analytics_indexes.php`  
**Mode:** Read-only audit — no migrations, tests, or application code were modified  
**Reference:** `docs/reports/post_phase3_architecture_audit.md` (TD-04)

---

## Executive Summary

The Pest/PHPUnit suite is **fully blocked** during database bootstrap: **143 of 145 tests fail** with the same `QueryException` before any test assertions run. The sole root cause is migration `2026_06_11_000002_add_lead_funnel_analytics_indexes.php`, which executes MySQL-specific `SHOW INDEX` introspection against the **SQLite `:memory:`** database configured in `phpunit.xml`.

Production deployments on MySQL are unaffected today because `SHOW INDEX` is valid MySQL syntax. However, the migration is **not portable** to SQLite (tests) or PostgreSQL (potential future hosting). All other migrations in the repository either use Laravel Schema Builder exclusively or guard raw SQL with `getDriverName()` checks.

**Audit verdict:** Single-file fix required. **Recommended path (Option A):** replace raw `SHOW INDEX` queries with Laravel 13 `Schema::hasIndex()` / `Schema::getConnection()->getSchemaBuilder()->getIndexes()`, preserving idempotent index creation across MySQL, SQLite, and PostgreSQL.

---

## Root Cause

### Failure chain

1. Feature and most Unit tests use `RefreshDatabase` (or Pest `uses(RefreshDatabase::class)`).
2. `RefreshDatabase` runs `migrate:fresh` against the test connection on each test class setup.
3. Migrations run in timestamp order. `2026_06_11_000001_create_listing_lead_tracking_tables.php` completes successfully on SQLite.
4. `2026_06_11_000002_add_lead_funnel_analytics_indexes.php` calls `createdAtIndexExists('listing_views')` in `up()`.
5. `createdAtIndexExists()` executes `DB::select('SHOW INDEX FROM ...')`.
6. SQLite rejects `SHOW` as invalid syntax → `QueryException` → migration aborts → all dependent tests fail.

### Why the migration uses `SHOW INDEX`

The migration was written to be **idempotent on production MySQL**: skip creating a standalone `created_at` index if one already exists (e.g. partial prior deploy, manual DBA index, or re-run). The companion migration `2026_06_11_000001` already creates composite indexes `(listing_id, created_at)` and `(user_id, created_at)` but **not** standalone `created_at` indexes. Migration `000002` adds standalone `created_at` indexes for date-range analytics queries (e.g. `LeadFunnelWidget` filtering `WHERE created_at >= ?` without `listing_id` in the predicate).

The idempotency check specifically looks for an index where `created_at` is the **first** column (`Seq_in_index = 1`), so existing composite indexes do not satisfy the check — correct intent, wrong implementation for cross-DB support.

### Exact exception (first failure)

```
Tests\Unit\Auth\OtpServiceTest > it verifies a valid otp and clears replay state
QueryException

SQLSTATE[HY000]: General error: 1 near "SHOW": syntax error
(Connection: sqlite, Database: :memory:,
 SQL: SHOW INDEX FROM `listing_views` WHERE Column_name = created_at AND Seq_in_index = 1)
```

### Stack trace (abbreviated, verbose run)

```
Illuminate\Database\Connection.php:421
  → database/migrations/2026_06_11_000002_add_lead_funnel_analytics_indexes.php:56  (createdAtIndexExists)
  → database/migrations/2026_06_11_000002_add_lead_funnel_analytics_indexes.php:27  (up)
  → Migrator.php (run migration)
  → MigrateCommand / FreshCommand (migrate:fresh)
  → RefreshDatabase.php:119
  → TestCase.php:75
```

All **143** failed tests report the identical `SHOW INDEX` / `syntax error` message. No secondary migration failures were observed.

---

## Migration File Analysis

**File:** `database/migrations/2026_06_11_000002_add_lead_funnel_analytics_indexes.php`

### Tables and indexes targeted

| Table | Intended index name | Column |
|-------|---------------------|--------|
| `listing_views` | `listing_views_created_at_index` | `created_at` |
| `listing_phone_clicks` | `listing_phone_clicks_created_at_index` | `created_at` |
| `listing_whatsapp_clicks` | `listing_whatsapp_clicks_created_at_index` | `created_at` |
| `offers` | `offers_created_at_index` | `created_at` |

### MySQL-specific statements

| Location | Statement | Purpose |
|----------|-----------|---------|
| `createdAtIndexExists()` L56–58 | `SHOW INDEX FROM \`{table}\` WHERE Column_name = ? AND Seq_in_index = 1` | Skip `up()` if standalone `created_at` index exists |
| `indexExists()` L66–68 | `SHOW INDEX FROM \`{table}\` WHERE Key_name = ?` | Skip `down()` if named index missing |
| Both queries | Backtick `` ` `` table identifier quoting | MySQL/MariaDB convention |

`SHOW INDEX` (synonym `SHOW KEYS`) is **MySQL/MariaDB only**. It is not valid in SQLite or PostgreSQL.

### SQLite-incompatible statements

| Statement | SQLite issue |
|-----------|--------------|
| Entire `createdAtIndexExists()` query | SQLite has no `SHOW INDEX`; parser error at `SHOW` |
| Entire `indexExists()` query | Same — affects `down()` on SQLite if ever rolled back in tests |

**Note:** The Schema Builder calls in `up()` / `down()` (`$table->index(...)`, `$table->dropIndex(...)`) are cross-compatible. Only the introspection helpers break SQLite.

### PostgreSQL compatibility issues

| Issue | Detail |
|-------|--------|
| `SHOW INDEX FROM` | Invalid on PostgreSQL; use `pg_indexes`, `information_schema`, or Laravel introspection |
| Backtick identifiers | PostgreSQL uses double quotes, not backticks |
| `Column_name`, `Key_name`, `Seq_in_index` | MySQL `SHOW INDEX` result column names; not portable |

If this migration were run against PostgreSQL without change, it would fail at the same point as SQLite.

### Laravel Schema Builder alternatives (Laravel 13)

Available on `Schema::getConnection()->getSchemaBuilder()`:

| Method | Use case |
|--------|----------|
| `hasIndex($table, $indexName)` | Replace `indexExists()` — check by explicit name in `down()` |
| `hasIndex($table, ['created_at'])` | Replace `createdAtIndexExists()` — matches index whose **columns array equals** `['created_at']` (excludes composite `(listing_id, created_at)`) |
| `getIndexes($table)` | Full introspection if custom logic needed |
| `getIndexListing($table)` | List index names only |

**Recommended replacement pattern:**

```php
// up() — skip if standalone created_at index exists
Schema::hasIndex($table, ['created_at'])

// down() — skip drop if named index missing
Schema::hasIndex($table, $indexName)
```

No raw SQL required. Works on SQLite, MySQL, MariaDB, and PostgreSQL via driver-specific grammars (`compileIndexes`).

### Portable parts (already correct)

- `Schema::hasTable($table)` guards — cross-DB
- `$table->index('created_at', $indexName)` — cross-DB
- `$table->dropIndex($indexName)` — cross-DB

---

## Test Environment Verification

### `phpunit.xml` database configuration

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="DB_URL" value=""/>
```

PHPUnit environment variables **override** `.env` during test runs. Tests use an ephemeral in-memory SQLite database, not production MySQL.

### `config/database.php`

- Default connection fallback: `env('DB_CONNECTION', 'sqlite')`
- SQLite connection supports `:memory:` with `foreign_key_constraints` enabled
- MySQL/MariaDB/PostgreSQL connections defined but unused during tests

### `.env.testing`

**Not present.** Testing DB settings come entirely from `phpunit.xml`.

### `tests/Pest.php` / `tests/TestCase.php`

- Feature tests bind to `Tests\TestCase` (Laravel application bootstrap)
- `TestCase` does not globally apply `RefreshDatabase`; each Feature/Unit file opts in individually
- **2 passing tests** do not use `RefreshDatabase`: `Tests\Unit\Auth\OtpCodeTest`, `Tests\Unit\ExampleTest`
- **143 failing tests** all use `RefreshDatabase` and therefore execute full migrations

### Migration failure scope

| Category | Count | Result on SQLite `:memory:` |
|----------|-------|------------------------------|
| Total migrations | 55 | — |
| Migrations with raw SQL | 4 files | 3 pass (driver-guarded) |
| **Blocking migration** | **1** | **`2026_06_11_000002`** |

Other raw-SQL migrations (verified safe):

| Migration | Raw SQL | Guard |
|-----------|---------|-------|
| `2026_04_10_035138_create_locations_table.php` | `SET FOREIGN_KEY_CHECKS` | `getDriverName() === 'mysql'` |
| `2026_04_10_110517_create_listings_table.php` | `SET FOREIGN_KEY_CHECKS` | `getDriverName() === 'mysql'` |
| `2026_06_04_200001_add_fraud_detection_to_listings_table.php` | `ALTER TABLE ... MODIFY/ALTER COLUMN` | Branches for mysql, pgsql, sqlite |

**Conclusion:** Failures are caused **only** by `2026_06_11_000002`, not by additional migrations.

---

## Test Suite Results

**Command:** `php artisan test`  
**Date:** 2026-06-11  
**Duration:** 79.18s

| Metric | Value |
|--------|-------|
| **Total tests** | **145** |
| **Passed** | **2** |
| **Failed** | **143** |
| **Assertions executed** | 4 (only on passing tests) |
| **Failure type** | 143 × `QueryException` (migration bootstrap) |

### Passing tests

| Test | Why it passes |
|------|---------------|
| `Tests\Unit\Auth\OtpCodeTest` | Pure value-object test; no DB |
| `Tests\Unit\ExampleTest` | No `RefreshDatabase` |

### First failing test (chronological in output)

| Field | Value |
|-------|-------|
| Class | `Tests\Unit\Auth\OtpServiceTest` |
| Method | `it verifies a valid otp and clears replay state` |
| Exception | `Illuminate\Database\QueryException` |
| SQL | `SHOW INDEX FROM \`listing_views\` WHERE Column_name = created_at AND Seq_in_index = 1` |
| Connection | `sqlite`, `:memory:` |

---

## Affected Files

| File | Role | Change needed |
|------|------|---------------|
| `database/migrations/2026_06_11_000002_add_lead_funnel_analytics_indexes.php` | **Primary** — contains `SHOW INDEX` | Replace introspection with Schema Builder |
| `database/migrations/2026_06_11_000001_create_listing_lead_tracking_tables.php` | Creates event tables + composite indexes | No change (reference only) |
| `phpunit.xml` | Test DB config | No change |
| `tests/**/*` (143 files/methods) | Consumers of `RefreshDatabase` | No change — fixed by migration |
| `docs/reports/post_phase3_architecture_audit.md` | Identified TD-04 | Reference |

**Downstream consumers (indirect):** CI pipelines, pre-merge quality gates, Phase 4 test additions for lead tracking/widgets — all blocked until TD-04 is resolved.

---

## Risk Assessment

| Risk | Likelihood | Impact | Notes |
|------|------------|--------|-------|
| CI/test suite remains red | **Current** | **Critical** | 98.6% of tests fail at migration |
| Regressions ship undetected | **Current** | **High** | No automated safety net |
| Production MySQL deploy fails | Low | Medium | `SHOW INDEX` works on MySQL today |
| PostgreSQL migration failure | Medium (if adopted) | High | Same `SHOW INDEX` error |
| Duplicate index on MySQL re-run | Low | Low | Idempotency intent is correct; implementation must preserve it via `hasIndex` |
| Removing idempotency checks | Medium | Medium | Could fail on DBs where index partially exists |
| Index redundancy confusion | Low | Low | Composite + standalone `created_at` indexes coexist by design |

---

## Recommended Fix Options

### Option A — Replace `SHOW INDEX` with `Schema::hasIndex()` (Recommended)

**Approach:** Refactor `createdAtIndexExists()` and `indexExists()` to use Laravel schema introspection. Remove `DB` facade import if unused.

**Pros:**

- Minimal diff (~15 lines)
- Preserves idempotent behavior on all drivers
- Aligns with existing patterns in `2026_06_04_200001_add_fraud_detection_to_listings_table.php`
- Unblocks entire test suite immediately

**Cons:**

- Slight behavior difference if MySQL has a non-standard index name on `created_at` only — unlikely in this codebase

**Risk:** Low  
**Effort:** ~30 minutes including test verification

---

### Option B — Consolidate indexes into `000001`; simplify or remove `000002`

**Approach:** Add standalone `$table->index('created_at', $name)` to `2026_06_11_000001` for new installs. For existing production DBs, either:

- Keep `000002` as a no-op Schema-only migration without introspection (try/catch duplicate index), or
- Squash migrations (only viable pre-production)

**Pros:**

- Single migration defines full schema for new environments
- Eliminates introspection entirely on greenfield

**Cons:**

- Does not help already-migrated production DBs without careful rollout
- Squashing breaks migration history for deployed environments
- Higher coordination cost

**Risk:** Medium (deployment history)  
**Effort:** 1–2 hours + deploy planning

---

### Option C — Run tests against MySQL instead of SQLite

**Approach:** Change `phpunit.xml` or CI to use MySQL/MariaDB service container; keep `SHOW INDEX` migration as-is.

**Pros:**

- No migration code change
- Tests run against production-like engine

**Cons:**

- Slower tests; CI service dependency
- Does not fix PostgreSQL portability
- Masks cross-DB issues for developers using SQLite locally
- Contradicts Laravel default testing convention and current `phpunit.xml`

**Risk:** Medium (false confidence on SQLite; ops overhead)  
**Effort:** 2–4 hours (CI + local dev docs)

---

## PASS / FAIL Table

| Audit area | Status | Evidence |
|------------|--------|----------|
| Migration file inspected | **PASS** | Full 73-line review completed |
| MySQL-specific statements identified | **PASS** | 2 × `SHOW INDEX`, backtick quoting |
| SQLite incompatibilities identified | **PASS** | Both introspection queries |
| PostgreSQL issues identified | **PASS** | Same `SHOW INDEX` failure mode |
| Schema Builder alternatives documented | **PASS** | `hasIndex`, `getIndexes` |
| `phpunit.xml` SQLite `:memory:` verified | **PASS** | Lines 26–28 |
| Test DB isolation from production | **PASS** | PHPUnit env overrides |
| Test suite executed | **PASS** | 145 tests, 79s |
| Single root-cause migration confirmed | **PASS** | 143/143 failures identical |
| Other migrations ruled out | **PASS** | 3 raw-SQL migrations driver-guarded |
| Production MySQL impact today | **PASS** | Migration runs if reached |
| Cross-database portability | **FAIL** | SQLite + PostgreSQL broken |
| Automated test suite health | **FAIL** | 2/145 pass |
| TD-04 fix implemented | **N/A** | Audit-only per scope |

---

## Implementation Plan

**Prerequisite:** Approval of Option A (recommended).

| Step | Action | Owner | Verification |
|------|--------|-------|--------------|
| 1 | Replace `createdAtIndexExists()` with `Schema::hasIndex($table, ['created_at'])` | Dev | Code review |
| 2 | Replace `indexExists()` with `Schema::hasIndex($table, $indexName)` | Dev | Code review |
| 3 | Remove unused `DB` import | Dev | Static analysis |
| 4 | Run `php artisan test` locally | Dev | Expect 145/145 pass (or document pre-existing unrelated failures) |
| 5 | Run `php artisan migrate` on MySQL staging | Dev/Ops | Indexes created or skipped idempotently |
| 6 | Optional: `php artisan migrate:fresh --seed` on SQLite | Dev | Full bootstrap clean |
| 7 | Update TD-04 status in architecture audit | Dev | Doc only |

**Estimated timeline:** Same day (Option A).  
**Rollback:** Revert single migration file; no data migration involved.

---

## Appendix A — Related Migration Context

`2026_06_11_000001_create_listing_lead_tracking_tables.php` creates:

- `listing_views`, `listing_phone_clicks`, `listing_whatsapp_clicks`
- Composite indexes: `(listing_id, created_at)`, `(user_id, created_at)` per table
- **Does not** create standalone `created_at` indexes

`2026_06_11_000002` adds standalone `created_at` indexes on four tables including `offers` (which has `timestamps()` from `2026_05_15_091908_create_offers_table.php`).

---

## Appendix B — Approval Gate

This document is **audit-only**. No implementation was performed.

**Awaiting approval** before applying Option A, B, or C.

---

*End of TD-04 SQLite Migration Compatibility Audit*
