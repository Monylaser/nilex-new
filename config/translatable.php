<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fallback locale for Spatie Translatable attributes
    |--------------------------------------------------------------------------
    |
    | When a translatable model attribute (e.g. LegalPage::title) has no value
    | stored for the active locale, fall back to this locale instead of showing
    | an empty string. This is a project-wide safety net so any future record
    | that is missing a translation (e.g. only Arabic seeded) still renders
    | readable text rather than a blank label.
    |
    | NOTE: The installed spatie/laravel-translatable version does NOT read this
    | file on its own — it is wired into the Spatie\Translatable\Translatable
    | singleton in AppServiceProvider::boot() so these keys actually take effect.
    |
    */

    'use_fallback_locale' => true,

    'fallback_locale' => 'ar',

];
