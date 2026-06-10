<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;

class LegalPageController extends Controller
{
    public function show(string $slug): \Illuminate\View\View
    {
        // بندور بالـ slug سواء جه بـ /pages/ أو بدونها
        $page = LegalPage::where('slug', $slug)
            ->orWhere('slug', '/pages/' . $slug)
            ->orWhere('slug', 'pages/' . $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('pages.show', compact('page'));
    }
}