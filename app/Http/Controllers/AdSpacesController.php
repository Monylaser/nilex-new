<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdSpacesController extends Controller
{
    public function index(): View
    {
        return view('frontend.ads.pricing');
    }
}
