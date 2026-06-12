<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Listing;
use App\Models\PointPlan;
use App\Services\EntitlementService;
use App\Services\PointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HomeController extends Controller
{
    /**
     * عرض الصفحة الرئيسية للموقع
     */
    public function index()
    {
        $categories = Category::whereNull('parent_id')
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get();

        $featuredListings = Listing::with(['category', 'location', 'user'])->active()->featured()->latest()->take(3)->get();
        $latestListings   = Listing::with(['category', 'location', 'user'])->active()->latest()->paginate(12);

    return response()
        ->view('frontend.home', compact('categories', 'featuredListings', 'latestListings'))
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /**
     * عرض صفحة إضافة إعلان جديد
     */
    public function create()
    {
        return view('frontend.listings.create'); 
    }

    /**
     * حفظ الإعلان الجديد ومنح النقاط 🎁
     */
    public function store(Request $request)
    {
        // 1. التحقق من البيانات الأساسية
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'price'       => 'required|numeric|min:0',
        ]);

        // 2. التحقق من الحقول المخصصة المطلوبة حسب القسم
        $category = Category::findOrFail($validated['category_id']);

        if (!empty($category->custom_fields_schema)) {
            $customRules    = [];
            $customMessages = [];

            foreach ($category->custom_fields_schema as $field) {
                if (!empty($field['required'])) {
                    $key = "custom_fields_values.{$field['name']}";
                    $customRules[$key]              = 'required';
                    $customMessages[$key . '.required'] = ($field['label_ar'] ?? $field['name']) . ' مطلوب';
                }
            }

            if (!empty($customRules)) {
                $request->validate($customRules, $customMessages);
            }
        }

        // 3. إنشاء الإعلان
        $listing                       = new Listing();
        $listing->title                = $validated['title'];
        $listing->slug                 = Str::slug($validated['title']) . '-' . Str::random(6);
        $listing->description          = $validated['description'];
        $listing->price                = $validated['price'];
        $listing->category_id          = $validated['category_id'];
        $listing->user_id              = Auth::id();
        $listing->status               = Listing::STATUS_PENDING;
        $listing->custom_fields_values = $request->input('custom_fields_values', []);
        $listing->save();

        // 4. رفع الصور باستخدام Spatie Media Library
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $listing->addMedia($image)->toMediaCollection('images');
            }
        }

        // 5. 🎁 منح اليوزر 10 نقاط مكافأة النشر
        $pointService = new PointService();
        $pointService->credit(Auth::user(), 10, 'مكافأة نشر إعلان جديد: ' . $listing->title, $listing);

        return redirect()->route('dashboard')->with('success', 'تم حفظ الإعلان بنجاح، وكسبت 10 نقاط! 🚀');
    }

    /**
     * عرض صفحة الأسعار وخطط النقاط
     */
    public function pricing()
    {
        $plans = PointPlan::active()->orderBy('price')->get();

        return view('frontend.pricing', [
            'plans'                  => $plans,
            'featureMatrix'          => config('pricing.feature_matrix', []),
            'planColumnKeys'         => config('pricing.plan_column_keys', []),
            'registrationWelcomePoints' => (int) config('pricing.registration_welcome_points', 0),
        ]);
    }

    /**
     * Advanced search (full-text + filters).
     *
     * Query params accepted:
     *   q          - search keyword
     *   min_price  - minimum price (EGP)
     *   max_price  - maximum price (EGP)
     *   category_id - category ID filter
     *   province_id - governorate (province) ID filter
     *   lat / lng  - GPS coordinates for geo-sort (Meilisearch only)
     *   radius     - radius in km for geo-filter (default 50, Meilisearch only)
     */
    public function search(Request $request)
    {
        $query      = $request->input('q', '');
        $lat        = $request->input('lat');
        $lng        = $request->input('lng');
        $radius     = $request->input('radius', 50);
        $minPrice   = $request->input('min_price');
        $maxPrice   = $request->input('max_price');
        $categoryId = $request->input('category_id');
        $provinceId = $request->input('province_id');

        $search = Listing::search(
            $query,
            // Meilisearch-specific callback.
            // The CollectionEngine also invokes this callback (passing an Eloquent Builder
            // as the first arg), so we guard against that to avoid a TypeError.
            function ($meiliSearch, $query, $options) use ($lat, $lng, $radius) {
                if ($meiliSearch instanceof \Illuminate\Database\Eloquent\Builder) {
                    return; // CollectionEngine path — Eloquent filters handled via .query() below
                }

                $filters = ['status = "' . Listing::STATUS_PUBLISHED . '"'];

                if ($lat && $lng) {
                    $radiusInMeters   = $radius * 1000;
                    $filters[]        = "_geoRadius({$lat}, {$lng}, {$radiusInMeters})";
                    $options['sort']  = ["_geoPoint({$lat}, {$lng}):asc"];
                }

                $options['filter'] = implode(' AND ', $filters);

                return $meiliSearch->search($query, $options);
            }
        )
        // Eloquent constraints — run on every Scout driver (collection, database, meilisearch)
        ->query(function ($q) use ($minPrice, $maxPrice, $categoryId, $provinceId) {
            $q->with(['category', 'location', 'user'])->where('status', Listing::STATUS_PUBLISHED);

            if ($minPrice !== null && $minPrice !== '') {
                $q->where('price', '>=', (float) $minPrice);
            }
            if ($maxPrice !== null && $maxPrice !== '') {
                $q->where('price', '<=', (float) $maxPrice);
            }
            if ($categoryId) {
                $q->where('category_id', (int) $categoryId);
            }
            if ($provinceId) {
                $q->where('province_id', (int) $provinceId);
            }
        });

        $listings = $search->paginate(12)->withQueryString();

        $entitlementService = app(EntitlementService::class);
        $listings->setCollection(
            $listings->getCollection()
                ->sortByDesc(fn (Listing $listing) => $listing->user && $entitlementService->hasFeature(
                    $listing->user,
                    EntitlementService::FEATURE_SEARCH_PRIORITY,
                ) ? 1 : 0)
                ->values()
        );

        return view('frontend.search-results', compact(
            'listings', 'query', 'lat', 'lng', 'radius',
            'minPrice', 'maxPrice', 'categoryId', 'provinceId'
        ));
    }
}