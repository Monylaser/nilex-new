<?php

namespace App\Http\Controllers;

use App\Services\AdCampaignService;
use Illuminate\View\View;

class AdSpacesController extends Controller
{
    public function index(): View
    {
        return view('frontend.ads.pricing', [
            'selfServiceEnabled' => AdCampaignService::selfServiceEnabled(),
            'pricing'            => config('ad_pricing', []),
        ]);
    }
}
