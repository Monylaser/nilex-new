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

        $featuredListings = Listing::with(['category', 'location', 'user'])
            ->active()
            ->featured()
            ->latest()
            ->take(3)
            ->get();

        $latestListings = Listing::with(['category', 'location', 'user'])
            ->active()
            ->latest()
            ->paginate(12);

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
     * حفظ الإعلان الجديد ومنح النقاط
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'price'       => 'required|numeric|min:0',
        ]);

        $category = Category::findOrFail($validated['category_id']);

        if (!empty($category->custom_fields_schema)) {
            $customRules    = [];
            $customMessages = [];

            foreach ($category->custom_fields_schema as $field) {
                if (!empty($field['required'])) {
                    $key                            = "custom_fields_values.{$field['name']}";
                    $customRules[$key]              = 'required';
                    $customMessages[$key . '.required'] = ($field['label_ar'] ?? $field['name']) . ' مطلوب';
                }
            }

            if (!empty($customRules)) {
                $request->validate($customRules, $customMessages);
            }
        }

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

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $listing->addMedia($image)->toMediaCollection('images');
            }
        }

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
            'plans'                     => $plans,
            'featureMatrix'             => config('pricing.feature_matrix', []),
            'planColumnKeys'            => config('pricing.plan_column_keys', []),
            'registrationWelcomePoints' => (int) config('pricing.registration_welcome_points', 0),
        ]);
    }

    /**
     * Advanced search (full-text + filters).
     *
     * Query params:
     *   q           - keyword
     *   min_price   - minimum price
     *   max_price   - maximum price
     *   category_id - category filter
     *   province_id - governorate filter
     *   lat / lng   - GPS for geo-sort (Meilisearch only)
     *   radius      - radius in km (default 50)
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

        $listings = Listing::search(
            $query,
            function ($meiliSearch, $query, $options) use ($lat, $lng, $radius) {
                if ($meiliSearch instanceof \Illuminate\Database\Eloquent\Builder) {
                    return;
                }

                $filters = ['status = "' . Listing::STATUS_PUBLISHED . '"'];

                if ($lat && $lng) {
                    $radiusInMeters  = $radius * 1000;
                    $filters[]       = "_geoRadius({$lat}, {$lng}, {$radiusInMeters})";
                    $options['sort'] = ["_geoPoint({$lat}, {$lng}):asc"];
                }

                $options['filter'] = implode(' AND ', $filters);

                return $meiliSearch->search($query, $options);
            }
        )
        ->query(function ($q) use ($minPrice, $maxPrice, $categoryId, $provinceId) {
            $q->with(['category', 'location', 'user'])
              ->where('listings.status', Listing::STATUS_PUBLISHED);

            if ($minPrice !== null && $minPrice !== '') {
                $q->where('listings.price', '>=', (float) $minPrice);
            }
            if ($maxPrice !== null && $maxPrice !== '') {
                $q->where('listings.price', '<=', (float) $maxPrice);
            }
            if ($categoryId) {
                $q->where('listings.category_id', (int) $categoryId);
            }
            if ($provinceId) {
                $q->where('listings.province_id', (int) $provinceId);
            }
        })
        ->paginate(12)
        ->withQueryString();

        $entitlements = app(EntitlementService::class);

        $listings->setCollection(
            $listings->getCollection()
                ->sortBy(fn (Listing $listing) => $entitlements->hasFeature(
                    $listing->user,
                    EntitlementService::FEATURE_SEARCH_PRIORITY,
                ) ? 0 : 1)
                ->values()
        );

        return view('frontend.search-results', compact(
            'listings', 'query', 'lat', 'lng', 'radius',
            'minPrice', 'maxPrice', 'categoryId', 'provinceId'
        ));
    }
}