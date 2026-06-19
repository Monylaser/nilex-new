# Listing Wizard Backend Integration — BLOCKED

**Status:** ❌ BLOCKED — execution halted during mandatory pre-checks.
**Date:** 2026-06-19
**Engineer role:** Senior Backend Laravel Engineer
**Task:** Extend backend for the Multi-Step Listing Wizard (add `condition`, `price_type`, `phone`, `location`).

The task instructions require a STOP if any of the target columns already exist or if any
stated assumption is incorrect. **Multiple assumptions are incorrect.** No migration, model,
or controller changes were made. Code was only read, never modified.

---

## 1. Pre-check results

| Check | Expected by task | Actual finding | Result |
|-------|------------------|----------------|--------|
| `listings` table exists | yes | yes — created in `2026_04_10_110517_create_listings_table.php` | ✅ OK |
| Column `condition` is new | does not exist | **ALREADY EXISTS** | ⛔ BLOCK |
| Column `phone` is new | does not exist | **ALREADY EXISTS** | ⛔ BLOCK |
| Column `price_type` is new | does not exist | does not exist | ✅ would be OK |
| Column `location` (string) is new | does not exist | does not exist as a string, but **`location_id` FK + `location()` relationship exist** | ⚠️ CONFLICT |
| `app/Models/Listing.php` exists | yes | yes | ✅ OK |
| `HomeController::store()` exists | yes | yes — `App\Http\Controllers\Frontend\HomeController::store()` | ✅ OK |
| Listing model uses `$fillable` | yes (append to it) | **NO `$fillable` — uses `protected $guarded = [];`** | ⛔ BLOCK |

---

## 2. Detailed findings

### 2.1 `condition` already exists
File: `database/migrations/2026_04_12_173122_add_condition_to_listings_table.php`

```php
public function up(): void
{
    Schema::table('listings', function (Blueprint $table) {
        $table->string('condition')->nullable()->after('price')->default('new');
    });
}
```

Creating `add_wizard_fields_to_listings_table` with a `condition` column would attempt to add
a duplicate column and **fail the migration** (`Column already exists`).

### 2.2 `phone` already exists
File: `database/migrations/2026_04_12_201710_add_phone_to_listings_table.php`

```php
public function up(): void
{
    Schema::table('listings', function (Blueprint $table) {
        $table->string('phone')->nullable()->after('price');
    });
}
```

Same duplicate-column failure risk as above.

### 2.3 `price_type` — does not exist
No occurrence found anywhere in `database/migrations/` or `app/`. This column could safely be
added in isolation, but the task forbids a partial migration when other listed columns already
exist.

### 2.4 `location` — naming conflict with existing relationship
The `listings` table has a `location_id` foreign key (added in
`2026_04_13_224722_add_missing_fields_to_listings_table.php`), and `Listing` defines:

```php
public function location(): BelongsTo
{
    return $this->belongsTo(Location::class, 'location_id');
}
```

Adding a plain string column named `location` would shadow/collide with the existing
`location()` Eloquent relationship accessor, causing ambiguous attribute/relationship access
(e.g. `$listing->location` would no longer reliably return the related `Location` model). This
is a real correctness risk and needs a product decision (different column name, or store on
the related `Location` instead).

### 2.5 Listing model has no `$fillable` array
File: `app/Models/Listing.php` (line 32):

```php
protected $guarded = [];
```

The model is fully mass-assignable via `$guarded = []`; there is **no `$fillable` array** to
append to. Step 2 of the task ("Append ONLY the following fields to the existing `$fillable`
array … DO NOT overwrite the existing `$fillable` array") is not applicable as written.
Note: because `$guarded = []`, the new columns would already be mass-assignable once they
exist — no model change is strictly required for assignment.

### 2.6 Current `store()` persistence logic (for reference, unchanged)
File: `app/Http/Controllers/Frontend/HomeController.php`, lines 65–114.

- **Custom fields:** validated dynamically from `$category->custom_fields_schema`, then saved
  via `$listing->custom_fields_values = $request->input('custom_fields_values', [])`.
- **Image uploads:** Spatie Media Library — `$listing->addMedia($image)->toMediaCollection('images')`
  (note: the create-table migration also has a legacy `images` JSON column, but uploads use the
  media library, not that column).
- **Persistence style:** the controller builds the model field-by-field (`new Listing()` + manual
  assignment + `save()`), **not** `Listing::create()`. The task's Step 3 assumes a
  `Listing::create()` payload, which does not match the current implementation.
- **No `DB::transaction()`** currently wraps the store flow, and there is no `expectsJson()`
  branch — it always `redirect()->route('dashboard')`.

---

## 3. Why this is a hard STOP

Per task rules:

> "If any of the following columns already exist: condition, price_type, phone, location →
> DO NOT create duplicate migrations. Report findings and stop. DO NOT CONTINUE."

`condition` and `phone` both already exist ⇒ STOP condition met.

Additionally, two stated assumptions are false (`$fillable` array; `Listing::create()` payload),
and `location` has a structural conflict. Proceeding blindly would either break the migration
(duplicate columns), break model/relationship access (`location`), or apply edits that do not
match the real code (Step 2 / Step 3 assumptions).

---

## 4. Recommended path forward (needs your decision)

A safe, non-duplicating plan once confirmed:

1. **Migration** — create `add_wizard_fields_to_listings_table` that adds **only the genuinely
   missing column(s)**, guarded with `Schema::hasColumn()`:
   - `price_type` (string, nullable) — missing, safe to add.
   - For `location`: choose one — (a) skip it and reuse `location_id`, or (b) add a distinctly
     named column such as `location_text` / `location_label` to avoid shadowing the relationship.
   - Do **not** re-add `condition` or `phone`.
   - Add indexes only for columns that actually get created.
2. **Model** — no `$fillable` change needed (model uses `$guarded = []`). If you specifically
   want an explicit allow-list, that is a larger change and should be scoped separately.
3. **Controller `store()`** — add validation + explicit, mapped persistence for the new fields,
   keeping the existing `new Listing()` + `save()` pattern (not `Listing::create()`), plus the
   requested `DB::transaction()` wrapper and `expectsJson()` branch — preserving the current
   redirect for legacy forms.

Please confirm:

- **Q1:** How should `location` be handled — reuse existing `location_id`, or add a new
  separately-named string column?
- **Q2:** Confirm we add `price_type` only (and `location` per Q1), and explicitly skip
  `condition`/`phone` since they already exist.
- **Q3:** OK to keep the existing `new Listing(); …; save()` pattern instead of
  `Listing::create()` (to honor "do not refactor existing logic")?

No files were modified. Awaiting confirmation before proceeding.
