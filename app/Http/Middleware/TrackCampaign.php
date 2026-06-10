<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackCampaign
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('ref') && filled($request->query('ref'))) {
            session(['campaign_code' => $request->query('ref')]);
        }

        return $next($request);
    }
}
