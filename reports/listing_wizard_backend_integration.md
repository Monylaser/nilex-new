# Listing Wizard Backend Integration — COMPLETED

**Status:** ✅ COMPLETED
**Date:** 2026-06-19
**Engineer role:** Senior Backend Laravel Engineer
**Source of truth:** `reports/listing_wizard_backend_blocked.md` (real project structure, not original assumptions).

Backend integration for the Multi-Step Listing Wizard is complete. Only the genuinely missing
column was added; pre-existing columns and logic were left untouched.

---

## 1. Files modified / created

| File | Action | Notes |
|------|--------|-------|
| `database/migrations/2026_06_19_194500_add_price_type_to_listings_table.php` | **Created** | Adds only `price_type` (guarded). |
| `app/Http/Controllers/Frontend/HomeController.php` | **Modified** | `store()` only + 1 import line. |

**No other files were modified.** The `Listing` model (`app/Models/Listing.php`) was **not**
changed — it uses `protected $guarded = [];`, so the new columns are already mass-assignable and
no `$fillable` array was introduced.

---

## 2. Migration created

File: `database/migrations/2026_06_19_194500_add_price_type_to_listings_table.php`

- Adds **only** `price_type` (string, nullable, after `price`) — the single genuinely missing field.
- Guarded with `Schema::hasColumn()` so it is safe even if re-run or partially applied.
- Adds an index on `price_type`.
- `down()` safely drops the index first, then the column (also guarded).
- **Did NOT create** `condition` (exists: `2026_04_12_173122_...`), `phone`
  (exists: `2026_04_12_201710_...`), or any `location` string column.

```php
public function up(): void
{
    Schema::table('listings', function (Blueprint $table) {
        if (! Schema::hasColumn('listings', 'price_type')) {
            $table->string('price_type')->nullable()->after('price');
            $table->index('price_type');
        }
    });
}

public function down(): void
{
    Schema::table('listings', function (Blueprint $table) {
        if (Schema::hasColumn('listings', 'price_type')) {
            $table->dropIndex(['price_type']);
            $table->dropColumn('price_type');
        }
    });
}
```

---

## 3. Controller changes (`HomeController@store`)

### 3.1 New import
Added `use Illuminate\Support\Facades\DB;` (for the transaction wrapper). No other imports changed.

### 3.2 Validation changes
Appended to the **existing** validation array (nothing removed):

```php
'condition'   => 'required|string|max:50',
'price_type'  => 'required|string|max:50',
'phone'       => 'required|string|max:20',
'location_id' => 'nullable|exists:locations,id',
```

- `location_id` reuses the existing FK + `location()` relationship — no new location text column.
- Native Laravel validation behavior is preserved: AJAX/JSON requests still automatically receive
  a **422 JSON** response on validation failure (no custom validation handling was added).

### 3.3 Data sanitization
```php
$phone = preg_replace('/\s+/', ' ', trim($validated['phone']));
```
Trims and collapses duplicate spaces in `phone` before persistence. No existing fields altered.

### 3.4 Persistence (existing pattern preserved)
Kept the existing `new Listing()` + manual assignment + `save()` pattern (no `Listing::create()`,
no `$request->all()`, no arbitrary mass assignment). Only the validated wizard fields were added
to the explicit assignment block:

```php
$listing->condition   = $validated['condition'];
$listing->price_type  = $validated['price_type'];
$listing->phone       = $phone;
$listing->location_id = $validated['location_id'] ?? null;
```

### 3.5 Transaction safety
The full store process — Listing creation, image uploads (Spatie), custom fields persistence, and
point credit — is wrapped in `DB::transaction(...)`, which returns the created `$listing`. If any
step throws, the entire operation rolls back.

### 3.6 AJAX support
```php
if ($request->expectsJson()) {
    return response()->json([
        'success'  => true,
        'redirect' => route('dashboard'),
    ]);
}

return redirect()->route('dashboard')->with('success', '...');
```
Legacy (non-AJAX) form behavior — the redirect to `dashboard` with the success flash message — is
preserved exactly.

---

## 4. Confirmations

- ✅ **Image upload logic NOT altered.** The Spatie Media Library block is byte-for-byte identical:
  ```php
  if ($request->hasFile('images')) {
      foreach ($request->file('images') as $image) {
          $listing->addMedia($image)->toMediaCollection('images');
      }
  }
  ```
  (It now simply executes inside the transaction closure; the logic itself is unchanged.)

- ✅ **`custom_fields_values` logic NOT altered.** Both the dynamic custom-field validation loop
  (driven by `$category->custom_fields_schema`) and the persistence line
  `$listing->custom_fields_values = $request->input('custom_fields_values', []);` are unchanged.

- ✅ **No `condition`/`phone`/`location` columns created.** Only `price_type` was added.

- ✅ **No `$fillable` introduced.** `Listing` still uses `protected $guarded = [];`.

- ✅ **No unrelated methods or files modified.** Only `store()` + one import line in `HomeController`.

- ✅ **No linter errors** in the modified/created files.

---

## 5. Suggested verification (not run)

```bash
php artisan migrate
```

This applies only the new `price_type` column; existing columns are protected by the
`Schema::hasColumn()` guard.

---

**Done.** Stopping here as instructed — no further features were implemented.
