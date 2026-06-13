<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Services\AdCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdTrackingController extends Controller
{
    public function impression(AdCampaign $campaign, Request $request): JsonResponse
    {
        app(AdCampaignService::class)->trackImpression($campaign, $request);

        return response()->json(['ok' => true]);
    }

    public function click(AdCampaign $campaign, Request $request): RedirectResponse
    {
        $url = app(AdCampaignService::class)->trackClick($campaign, $request);

        return redirect($url);
    }
}
