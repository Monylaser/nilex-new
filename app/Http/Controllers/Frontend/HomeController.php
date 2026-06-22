<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CarBrand;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Location;
use App\Models\PointPlan;
use App\Services\EntitlementService;
use App\Services\GeminiService;
use App\Services\PointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            }])
            ->get();

        // Governorates (level 0) eager-loaded with their active child cities (level 1)
        // for the dependent location dropdowns in the listing wizard.
        $governorates = Location::governorates()
            ->active()
            ->with(['children' => function ($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->get(['id', 'name_ar', 'parent_id', 'level', 'sort_order']);

        // Car brands eager-loaded with their active models for the dependent
        // brand → model dropdowns in the listing wizard (cars category only).
        $carBrands = CarBrand::active()
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->with(['models' => function ($query) {
                $query->where('is_active', true)->orderBy('name_ar');
            }])
            ->get(['id', 'name_ar', 'name_en', 'slug']);

        $user             = Auth::user();
        $isPhoneVerified  = (bool) $user->is_phone_verified;
        $userPoints       = (int)  $user->points;

        return view('frontend.listings.create', compact('categories', 'governorates', 'carBrands', 'isPhoneVerified', 'userPoints'));
    }

    /**
     * حفظ الإعلان الجديد ومنح النقاط
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'required|string',
            'category_id'  => 'required|exists:categories,id',
            'price'        => 'required|numeric|min:0',
            // ── Multi-Step Listing Wizard fields ──
            'condition'    => 'required|string|max:50',
            'price_type'   => 'required|string|max:50',
            'phone'        => 'required|string|max:20',
            // location is reused via the existing location_id relationship
            'location_id'  => 'nullable|exists:locations,id',
            // car listings store brand/model in dedicated FK columns (cars category only)
            'car_brand_id' => 'nullable|exists:car_brands,id',
            'car_model_id' => 'nullable|exists:car_models,id',
            // optional featuring after creation (0 = none)
            'feature_days' => 'nullable|integer|in:0,1,3,7,14',
        ]);

        $category = Category::findOrFail($validated['category_id']);

        // ── Car-specific validation (cars category only) ──
        // Brand & Model are stored as FK columns; fuel & transmission live in
        // custom_fields_values. When the "أخرى/Other" brand is chosen, a manual
        // brand name (car_brand_other) becomes required.
        if ($category->slug === 'cars') {
            $request->validate([
                'car_brand_id'                      => 'required|exists:car_brands,id',
                'car_model_id'                      => [
                    'required',
                    \Illuminate\Validation\Rule::exists('car_models', 'id')
                        ->where('car_brand_id', $request->input('car_brand_id')),
                ],
                'custom_fields_values.fuel'         => 'required|string',
                'custom_fields_values.transmission' => 'required|string',
                'custom_fields_values.year'         => 'required',
                'custom_fields_values.condition'    => 'required|string|max:255',
            ], [
                'car_brand_id.required'                      => 'الماركة مطلوبة',
                'car_model_id.required'                      => 'الموديل مطلوب',
                'car_model_id.exists'                        => 'الموديل المختار لا يتبع هذه الماركة',
                'custom_fields_values.fuel.required'         => 'نوع الوقود مطلوب',
                'custom_fields_values.transmission.required' => 'ناقل الحركة مطلوب',
                'custom_fields_values.year.required'         => 'سنة الصنع مطلوبة',
                'custom_fields_values.condition.required'    => 'حالة السيارة مطلوبة',
            ]);

            $brand = CarBrand::find($validated['car_brand_id'] ?? $request->input('car_brand_id'));
            if ($brand && $brand->slug === 'other') {
                $request->validate([
                    'custom_fields_values.car_brand_other' => 'required|string|max:255',
                ], [
                    'custom_fields_values.car_brand_other.required' => 'اكتب اسم الماركة',
                ]);
            }
        }

        // ── Real-estate-specific validation (real-estate category only) ──
        // Mirrors the admin RealEstateFields schema: property_type & listing_type
        // are required; the remaining property fields are optional. All values
        // are stored in custom_fields_values to stay consistent with the admin.
        if ($category->slug === 'real-estate') {
            $request->validate([
                'custom_fields_values.property_type' => 'required|string',
                'custom_fields_values.listing_type'  => 'required|string',
            ], [
                'custom_fields_values.property_type.required' => 'نوع العقار مطلوب',
                'custom_fields_values.listing_type.required'  => 'نوع العرض مطلوب',
            ]);
        }

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

        // Sanitize free-text input: trim and collapse duplicate spaces.
        $phone = preg_replace('/\s+/', ' ', trim($validated['phone']));

        $listing = DB::transaction(function () use ($request, $validated, $phone) {
            $listing                       = new Listing();
            $listing->title                = $validated['title'];
            $listing->slug                 = Str::slug($validated['title']) . '-' . Str::random(6);
            $listing->description          = $validated['description'];
            $listing->price                = $validated['price'];
            $listing->category_id          = $validated['category_id'];
            $listing->user_id              = Auth::id();
            $listing->status               = Listing::STATUS_PENDING;
            $listing->condition            = $validated['condition'];
            $listing->price_type           = $validated['price_type'];
            $listing->phone                = $phone;
            $listing->location_id          = $validated['location_id'] ?? null;
            $listing->car_brand_id         = $validated['car_brand_id'] ?? null;
            $listing->car_model_id         = $validated['car_model_id'] ?? null;
            $listing->custom_fields_values = $request->input('custom_fields_values', []);
            $listing->save();

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $listing->addMedia($image)->toMediaCollection('images');
                }
            }

            $pointService = new PointService();
            $pointService->credit(Auth::user(), 3, 'مكافأة نشر إعلان جديد: ' . $listing->title, $listing);

            return $listing;
        });

        // Attempt featuring after the listing is committed — best effort only.
        // A failure here must never block listing creation.
        $featured     = false;
        $featureDays  = (int) ($validated['feature_days'] ?? 0);
        if ($featureDays > 0) {
            try {
                $listing->featureWithPoints($featureDays);
                $featured = true;
            } catch (\Exception) {
                // Silent fail: insufficient points or plan limits
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'featured' => $featured,
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'تم حفظ الإعلان بنجاح، وكسبت 3 نقاط! 🚀');
    }

    /**
     * 🤖 المساعد الذكي لويزارد إضافة الإعلان.
     *
     * يعيد استخدام منطق Gemini الموجود (GeminiService::generateFromInput)
     * لتوليد عنوان ووصف وسعر مقترح من وصف مختصر يكتبه المستخدم.
     * لا يكسر تدفق الويزارد إطلاقاً: عند فشل/تجاوز حد Gemini يرجع رسالة
     * عربية واضحة (success=false) ويُكمل المستخدم يدوياً.
     */
    public function aiGenerate(Request $request, GeminiService $gemini)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|min:3|max:500',
        ]);

        $result = $gemini->generateFromInput($validated['prompt']);

        // generateFromInput يرجع قيماً فارغة عند فشل الاتصال أو تجاوز الحصة.
        if (empty($result['title']) && empty($result['description'])) {
            return response()->json([
                'success' => false,
                'message' => 'تعذّر توليد الإعلان حالياً (قد يكون بسبب تجاوز حد الاستخدام). يمكنك إكمال البيانات يدوياً والمتابعة.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'title'           => $result['title'],
                'description'     => $result['description'],
                'suggested_price' => $result['suggested_price'],
            ],
        ]);
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