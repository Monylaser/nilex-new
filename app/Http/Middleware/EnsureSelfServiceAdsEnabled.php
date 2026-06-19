<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSelfServiceAdsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('features.self_service_ads', false)) {
            abort(404);
        }

        return $next($request);
    }
}
