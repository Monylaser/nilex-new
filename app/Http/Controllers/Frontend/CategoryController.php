<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\EntitlementService;
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
            ->with(['category', 'location', 'user', 'media'])
            ->where('status', 'published');

        if ($sort === ListingSort::DEFAULT) {
            $listingsQuery
                ->leftJoin('users', 'users.id', '=', 'listings.user_id')
                ->leftJoin('user_entitlements', function ($join) {
                    $join->on('user_entitlements.user_id', '=', 'users.id')
                        ->where('user_entitlements.feature_key', EntitlementService::FEATURE_SEARCH_PRIORITY)
                        ->whereIn('user_entitlements.value', ['1', 'true'])
                        ->where(function ($entitlementQuery) {
                            $entitlementQuery->whereNull('user_entitlements.expires_at')
                                ->orWhere('user_entitlements.expires_at', '>', now());
                        });
                })
                ->select('listings.*')
                ->orderByRaw('CASE WHEN user_entitlements.id IS NULL THEN 1 ELSE 0 END ASC');

            ListingSort::apply($listingsQuery, $sort, qualify: true);
        } else {
            ListingSort::apply($listingsQuery, $sort);
        }

        $listings = $listingsQuery->paginate(12)->withQueryString();

        return view('frontend.category', compact('category', 'listings', 'sort'));
    }
}
