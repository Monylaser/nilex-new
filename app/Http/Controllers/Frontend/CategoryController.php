<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\ListingSort;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function show(Category $category, Request $request)
    {
        $request->validate([
            'sort' => ['nullable', 'string', Rule::in(ListingSort::OPTIONS)],
        ]);

        // الضربة القاضية: زيادة العداد تلقائياً عند الدخول
        $category->increment('views_count');

        $sort = ListingSort::fromRequest($request->input('sort'));

        // جلب الإعلانات التابعة للقسم مع الـ eager loading لتفادي LazyLoadingViolationException
        // (نفس نمط HomeController::index — البطاقة تستخدم category و user)
        $listingsQuery = $category->listings()
            ->with(['category', 'location', 'user'])
            ->where('status', 'published');

        ListingSort::apply($listingsQuery, $sort);

        $listings = $listingsQuery->paginate(12)->withQueryString();

        return view('frontend.category', compact('category', 'listings', 'sort'));
    }
}
