<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Category;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * عرض الصفحة الرئيسية للموقع
     */
    public function index()
    {
        // 1. جلب كل الأقسام
        $categories = Category::all();

        // 2. جلب الإعلانات المميزة (أول 3 إعلانات نشطة)
        $featuredListings = Listing::where('status', 'active')
            ->latest()
            ->take(3)
            ->get();

        // 3. جلب أحدث الإعلانات مع الترقيم (Pagination)
        // استخدمنا paginate(12) عشان زرار links() اللي عندك يشتغل صح
        $latestListings = Listing::where('status', 'active')
            ->latest()
            ->paginate(12);

        // إرسال كل المتغيرات بالأسماء اللي الـ Blade مستنيها
        return view('frontend.home', compact('categories', 'featuredListings', 'latestListings'));
    }
}
