<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Priority: authenticated user's saved locale → session → config default.
        $locale = (Auth::check() && Auth::user()->locale)
            ? Auth::user()->locale
            : session('locale', config('app.locale', 'ar'));

        if (!in_array($locale, config('app.available_locales', ['ar', 'en']))) {
            $locale = config('app.locale', 'ar');
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
