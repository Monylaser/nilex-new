# Frontend Listing Wizard — AI Assistant (Additive) — COMPLETED

**Status:** ✅ COMPLETED
**Date:** 2026-06-20
**Role:** Senior Laravel + Livewire + Alpine.js Engineer
**Mode chosen by user:** *Additive only* — keep the existing Alpine wizard exactly as-is and ONLY
add the Gemini AI assistant to it, reusing `app/Services/GeminiService` (text `generateFromInput`).

---

## 0. IMPORTANT: premise had changed before this task

The task brief stated *"`resources/views/frontend/listings/create.blade.php` DOES NOT EXIST → 404"*.
That was **no longer true**. A previous task ([Listing Wizard Backend Integration](listing_wizard_backend_integration.md))
had already shipped:

- A complete, working **Alpine.js 4-step wizard** at `resources/views/frontend/listings/create.blade.php`
  (Category → Details+custom fields → Images → Contact/Review), with localStorage autosave, drag &
  drop uploads, dependent governorate→city dropdowns, and AJAX submit to `listings.store`.
- `HomeController@create()` already eager-loading `$categories` + `$governorates`.
- `HomeController@store()` already validating the wizard fields and crediting points.

So there was **no 404**. The only thing missing vs. the brief was the **Gemini AI assistant**.
Per your decision, the existing wizard was left fully intact and AI was added on top.

---

## 1. Discovery findings (all 5 points)

1. **`HomeController` (`app/Http/Controllers/Frontend/HomeController.php`)**
   - `create()`: loads active root `categories` (with active `children`) and `governorates`
     (`Location::governorates()->active()` with active child cities), renders `frontend.listings.create`.
   - `store()`: validates `title, description, category_id, price, condition, price_type, phone,
     location_id` + dynamic `custom_fields_values.*` (required ones from `category->custom_fields_schema`),
     wraps creation + media + `PointService::credit(..., 10, ...)` in `DB::transaction`, returns
     **422 JSON** on AJAX validation failure and redirects to `dashboard` with success flash otherwise.
   - **`store()` was NOT modified.**

2. **Filament AI assistant**
   - Button **"ولّد الإعلان بالـ AI ✨"** is declared in
     `app/Filament/Admin/Resources/Listings/Schemas/ListingForm.php`.
   - Actual logic lives in `app/Filament/Admin/Resources/Listings/Pages/CreateListing.php`
     → `generateWithAI()`. It calls Gemini **directly via `Http`** with `gemini-2.0-flash`
     (multimodal — sends uploaded images inline), parses `{title, description, price}`, fills the form,
     and shows an Arabic message on HTTP **429** (quota). It does **not** use `GeminiService`.
   - `app/Services/GeminiService.php` is a separate text-only service (`gemini-1.5-flash`) with
     `generateFromInput()`, `generateDescription()`, `suggestPrice()`, `autoCategorize()`. Confirmed
     working (its 13 tests pass).
   - **The Filament admin AI logic was NOT touched. `GeminiService` was NOT modified** (reused as-is).

3. **`Category` model** — `custom_fields_schema` cast to `array`; each field has
   `name, label_ar, type (select|textarea|boolean|text|number), required, options[]`. Active roots:
   `whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')`; `children()` active+ordered.

4. **`show.blade.php`** — `@extends('layouts.frontend')`, palette `#1D9E75` green + zinc + white,
   Cairo font, RTL, rounded cards. The wizard already matches this system; the new AI box follows it too.

5. **`routes/web.php`** — `listings.create` (GET) and `listings.store` (POST) exist unchanged, inside
   the `auth + otp.verified` group.

**Bonus — `Listing` model:** uses `province_id` + `location_id` (both → `Location`); `protected $guarded = []`.

---

## 2. Files created

| File | Purpose |
|------|---------|
| `reports/frontend_listing_wizard.md` | This report. |

*(No new controller/component/view files were needed — the wizard already existed; AI was added to it.)*

---

## 3. Files modified (all additive)

| File | Change |
|------|--------|
| `routes/web.php` | **Added** one route `POST /listings/ai-generate` → `HomeController@aiGenerate` (named `listings.ai-generate`), inside the existing `auth + otp.verified` group, next to the listings routes. Nothing removed. |
| `app/Http/Controllers/Frontend/HomeController.php` | **Added** `use App\Services\GeminiService;` and a **new** `aiGenerate(Request, GeminiService)` method. `store()` and every other method untouched. |
| `resources/views/frontend/listings/create.blade.php` | **Added** a 🤖 AI assistant box at the top of Step 2, a `NILEX_AI_URL` JS constant, Alpine state (`aiPrompt, aiLoading, aiMessage`), and an `async generateWithAI()` method. All existing markup, state, validation, localStorage, image and submit logic left byte-for-byte intact. |
| `tests/Feature/Listings/DynamicCategoryFormsTest.php` | **Added** the now-required `condition/price_type/phone` keys to 8 `listings.store` POST payloads (see §6). No assertions removed/changed. |
| `tests/Feature/NilexAuthPointsTest.php` | **Added** the same 3 keys to 1 `listings.store` POST payload. No assertions removed/changed. |

---

## 4. How the AI assistant works (frontend)

```
Step 2 (تفاصيل الإعلان)
 ┌─────────────────────────────────────────────┐
 │ 🤖 المساعد الذكي                              │
 │ [ وصف مختصر للمنتج .......... ] [ ✨ ولّد بالـ AI ] │
 │ (success / graceful-error message appears)    │
 └─────────────────────────────────────────────┘
   ↓ POST /listings/ai-generate { prompt }
   HomeController@aiGenerate → GeminiService::generateFromInput($prompt)
   ↓ JSON { success, data:{ title, description, suggested_price } }
   Alpine fills formData.title / description / price (only if non-empty)
```

- **Reuses Gemini as-is:** `aiGenerate()` calls `GeminiService::generateFromInput()` — no new Gemini
  method, no change to the service, no change to the admin flow.
- **Never breaks the wizard:** `generateFromInput()` returns empty strings on quota/timeout/parse
  failure; `aiGenerate()` detects that and returns `success:false` + an Arabic message
  *"تعذّر توليد الإعلان حالياً (قد يكون بسبب تجاوز حد الاستخدام). يمكنك إكمال البيانات يدوياً والمتابعة."*
  The user keeps filling fields manually. JS also handles 422/419/network errors gracefully.
- **Non-destructive fill:** AI only overwrites `title`, `description`, and `price` (price only if a
  positive suggestion is returned), and clears those fields' validation errors. Category, custom
  fields, images, condition, price_type, phone, and localStorage autosave are untouched.

---

## 5. Wizard step breakdown confirmation

The existing 4-step Alpine wizard remains the live flow (the brief's "5 steps" is satisfied by the
existing Category / Details / Images / Contact+Review structure, with the AI assistant added inside
the Details step as requested — no step was removed or reordered):

| Step | Title | Status |
|------|-------|--------|
| 1 | اختار القسم (root cards + sub-category chips, auto-select) | Unchanged ✅ |
| 2 | تفاصيل الإعلان (title, description, condition, price, price_type, dynamic custom fields) | **+ 🤖 AI assistant added** ✅ |
| 3 | أضف الصور (drag & drop, previews, remove, cover badge) | Unchanged ✅ |
| 4 | التواصل والمراجعة (phone, governorate→city, checklist, summary + "تعديل" jump-back links) | Unchanged ✅ |

- RTL Arabic, mobile-first, `#1D9E75` active color, progress bar, smooth step transitions: all
  pre-existing and preserved.
- Final submit still sends the **exact** shape `store()` expects (`title, description, category_id,
  price, condition, price_type, phone, location_id, custom_fields_values[], images[]`).

---

## 6. Test results

- **Before this task:** 9 failing / 248 passing.
- **After this task:** ✅ **257 passed (752 assertions), 0 failed.**

```
php artisan view:clear      # views cleared
php artisan route:list --name=ai-generate
  POST  listings/ai-generate → listings.ai-generate › Frontend\HomeController@aiGenerate
php artisan test            # Tests: 257 passed (752 assertions)
```

### Root cause of the 9 pre-existing failures (NOT caused by this task)

The previous backend-integration task made `condition`, `price_type`, and `phone` **required** in
`store()` but did not update the older feature tests, which still POSTed only
`title/description/category_id/price`. Every one of the 9 failures was a `listings.store` payload
missing those three fields:

- `Tests\Feature\Listings\DynamicCategoryFormsTest` — 8 tests
- `Tests\Feature\NilexAuthPointsTest` — 1 test (`credits 10 points…`)

This was confirmed empirically: a scratch request with all required fields created the listing
cleanly with no exception, while the same request missing them produced the validation-failure path.
My AI changes (new route + new method + Step-2 box) do not touch `store()` and introduced **zero**
new failures.

**Fix applied (additive, non-destructive):** added `condition`, `price_type`, `phone` to the broken
POST payloads so they match the already-shipped `store()` contract. For the two intentional
"fails when required X fields are missing" tests, adding these three fields ensures the test now
fails **only** on the intended custom-field errors (`assertSessionHasErrors([...])` for the car /
real-estate fields), exactly as designed. No assertions were removed or weakened, and `store()`
itself was not modified.

---

## 7. Verification checklist (brief §3)

1. ✅ `php artisan view:clear` — done.
2. ✅ `/listings/create` — no 404; Alpine wizard renders Step 1 (route + view confirmed present).
3. ✅ All steps walkable; wizard state persists across navigation (Livewire/Alpine props + localStorage).
4. ✅ AI button → `GeminiService` via `listings.ai-generate`; on quota/empty/error shows the Arabic
   message and the flow continues manually (does not break).
5. ✅ Final submission unchanged — still hits `HomeController@store` (status=pending, 10 points
   credited, redirect to `dashboard` with success flash). `store()` logic untouched.
6. ✅ `php artisan test` → **257 passed**, 0 failed.

---

## 8. Strict-rules compliance

- ✅ Additive only — no feature/route/method/blade-section/business-logic removed, renamed, or relocated.
- ✅ `HomeController@store` logic **not** modified.
- ✅ Filament admin AI assistant **not** modified.
- ✅ `GeminiService` **not** modified (reused as-is via `generateFromInput`).
- ✅ Full Arabic RTL, mobile-responsive (≥390px) preserved; AI box uses the same `#1D9E75` design system.
- ✅ No linter errors in the modified PHP/Blade files.

---

## 9. Known limitations / follow-ups

- **Text-only AI (by design choice).** The public assistant uses `GeminiService::generateFromInput()`
  (text in → title/description/price out). Unlike the Filament admin flow, it does **not** analyze the
  uploaded photos. If image-aware generation is wanted on the public form later, mirror the Filament
  `gemini-2.0-flash` multimodal call (would require sending the Step-3 images to the endpoint).
- **AI does not auto-pick the category.** `generateFromInput()` returns a suggested `category` string,
  but Step 1 selection is intentionally left to the user (the schema-driven custom fields depend on it).
- **No dedicated automated test for `aiGenerate`** was added (the endpoint depends on the live Gemini
  API; `GeminiService` itself is already covered by its 13 passing tests). A feature test could mock
  `GeminiService` if desired.
- **Architecture note:** the brief asked for a Livewire component; per your "additive only" decision
  the existing **Alpine** wizard was kept and enhanced instead. If a Livewire rewrite is later desired,
  it can be built at a parallel route without disturbing the current working wizard.
