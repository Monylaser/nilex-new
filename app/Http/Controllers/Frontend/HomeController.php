<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\CarBrand;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Location;
use App\Models\PointPlan;
use App\Models\SaleConfirmation;
use App\Services\EntitlementService;
use App\Services\GeminiService;
use App\Services\PointService;
use App\Support\ListingSort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

        $heroCampaigns = AdCampaign::where('placement', 'hero_top')
            ->where('status', 'active')
            ->where('approval_status', 'approved')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereNull('deleted_at')
            ->orderByDesc('priority')
            ->with('media')
            ->get();

        return response()
            ->view('frontend.home', compact('categories', 'featuredListings', 'latestListings', 'heroCampaigns'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /**
     * عرض صفحة إضافة إعلان جديد
     */
    public function create()
    {
        $reference = $this->wizardReferenceData();
        $user = Auth::user();
        $isPhoneVerified = (bool) $user->is_phone_verified;
        $userPoints = (int) $user->points;

        // Pre-fill location from user profile (Task 2 — wizard auto-fill).
        $user->loadMissing('location');
        $prefillLocationId = $user->location_id ? (string) $user->location_id : '';
        $prefillGovernorateId = $user->location?->parent_id ? (string) $user->location->parent_id : '';

        return view('frontend.listings.create', array_merge($reference, [
            'isPhoneVerified' => $isPhoneVerified,
            'userPoints' => $userPoints,
            'mode' => 'create',
            'prefillLocationId' => $prefillLocationId,
            'prefillGovernorateId' => $prefillGovernorateId,
        ]));
    }

    /**
     * البيانات المرجعية المشتركة للويزارد (الأقسام + المحافظات + ماركات السيارات).
     * تُستخدم في وضعي الإنشاء (create) والتعديل (edit) دون تكرار.
     */
    private function wizardReferenceData(): array
    {
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            }])
            ->get();

        // Expose the locale-aware `name` accessor in the JSON payload the wizard
        // consumes (Alpine reads `cat.name` / `sub.name`) so category labels follow
        // the active locale instead of always rendering Arabic.
        $categories->each(function (Category $category) {
            $category->append('name');
            $category->children->each->append('name');
        });

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

        return compact('categories', 'governorates', 'carBrands');
    }

    /**
     * حفظ الإعلان الجديد ومنح النقاط
     */
    public function store(Request $request)
    {
        // القسم يُحدَّد مبكراً لجعل قاعدة "الحالة" مشروطة به: مطلوبة لكل الأقسام
        // عدا العقارات (لا معنى لجديد/مستعمل هناك)، حيث تصبح nullable.
        $earlyCategory = Category::find($request->input('category_id'));
        $isRealEstate = $earlyCategory?->slug === 'real-estate';

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            // max mirrors the listings.price column (decimal(12,2) → 9,999,999,999.99)
            // to return a friendly 422 instead of a DB out-of-range (22003) 500.
            'price' => 'required|numeric|min:0|max:9999999999.99',
            // ── Multi-Step Listing Wizard fields ──
            // الحالة مطلوبة إلا للعقارات (مشروطة بالقسم).
            'condition' => [$isRealEstate ? 'nullable' : 'required', 'string', 'max:50'],
            'price_type' => 'required|string|max:50',
            'phone' => 'required|string|max:20',
            // location is reused via the existing location_id relationship
            'location_id' => 'nullable|exists:locations,id',
            // car listings store brand/model in dedicated FK columns (cars category only)
            'car_brand_id' => 'nullable|exists:car_brands,id',
            'car_model_id' => 'nullable|exists:car_models,id',
            // optional featuring after creation (0 = none)
            'feature_days' => 'nullable|integer|in:0,1,3,7,14',
            // ── أمان الصور: تحقق خادمي من النوع والحجم (لا نعتمد على الـ client) ──
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,webp|max:5120',
        ], [
            'price.max' => __('wizard.server.price_max'),
            'images.max' => __('wizard.server.images_max'),
            'images.*.image' => __('wizard.server.image_invalid'),
            'images.*.mimes' => __('wizard.server.image_mimes'),
            'images.*.max' => __('wizard.server.image_max'),
        ]);

        // category_id تم التحقق من وجوده أعلاه؛ نعيد الاستخدام لتفادي استعلام مكرّر.
        $category = $earlyCategory ?? Category::findOrFail($validated['category_id']);

        // ── Category-specific validation (cars / real-estate / dynamic schema) ──
        // Extracted into a shared helper so the edit flow (HomeController::update)
        // reuses the exact same conditional rules without duplicating them.
        $this->validateCategorySpecificFields($request, $category);

        // Sanitize free-text input: trim and collapse duplicate spaces.
        $phone = preg_replace('/\s+/', ' ', trim($validated['phone']));

        $listing = DB::transaction(function () use ($request, $validated, $phone) {
            $listing = new Listing;
            $listing->title = $validated['title'];
            $listing->slug = Str::slug($validated['title']).'-'.Str::random(6);
            $listing->description = $validated['description'];
            $listing->price = $validated['price'];
            $listing->category_id = $validated['category_id'];
            $listing->user_id = Auth::id();
            $listing->status = Listing::STATUS_PENDING;
            // null للعقارات (لم تُرسَل الحالة)؛ القيمة المُدخَلة لباقي الأقسام.
            $listing->condition = $validated['condition'] ?? null;
            $listing->price_type = $validated['price_type'];
            $listing->phone = $phone;
            $listing->location_id = $validated['location_id'] ?? null;
            $listing->car_brand_id = $validated['car_brand_id'] ?? null;
            $listing->car_model_id = $validated['car_model_id'] ?? null;
            $listing->custom_fields_values = $request->input('custom_fields_values', []);
            $listing->save();

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $listing->addMedia($image)->toMediaCollection('images');
                }
            }

            $pointService = new PointService;
            $pointService->credit(Auth::user(), 3, 'مكافأة نشر إعلان جديد: '.$listing->title, $listing);

            return $listing;
        });

        // Attempt featuring after the listing is committed — best effort only.
        // A failure here must never block listing creation.
        $featured = false;
        $featureDays = (int) ($validated['feature_days'] ?? 0);
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
                'success' => true,
                'featured' => $featured,
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', __('wizard.server.created_success'));
    }

    /**
     * التحقق الشرطي حسب القسم (سيارات / عقارات / حقول مخصّصة ديناميكية).
     * مشترك بين الإنشاء (store) والتعديل (update) — القسم يُمرَّر كوسيط، لذا
     * في التعديل يأتي من الإعلان نفسه (مقفول) وليس من الطلب.
     */
    private function validateCategorySpecificFields(Request $request, Category $category): void
    {
        // ── Cars (cars category only) ──
        if ($category->slug === 'cars') {
            $request->validate([
                'car_brand_id' => 'required|exists:car_brands,id',
                'car_model_id' => [
                    'required',
                    Rule::exists('car_models', 'id')
                        ->where('car_brand_id', $request->input('car_brand_id')),
                ],
                'custom_fields_values.fuel' => 'required|string',
                'custom_fields_values.transmission' => 'required|string',
                'custom_fields_values.year' => 'required',
                'custom_fields_values.condition' => 'required|string|max:255',
            ], [
                'car_brand_id.required' => __('wizard.server.car_brand_required'),
                'car_model_id.required' => __('wizard.server.car_model_required'),
                'car_model_id.exists' => __('wizard.server.car_model_not_in_brand'),
                'custom_fields_values.fuel.required' => __('wizard.server.fuel_required'),
                'custom_fields_values.transmission.required' => __('wizard.server.transmission_required'),
                'custom_fields_values.year.required' => __('wizard.server.year_required'),
                'custom_fields_values.condition.required' => __('wizard.server.condition_required'),
            ]);

            $brand = CarBrand::find($request->input('car_brand_id'));
            if ($brand && $brand->slug === 'other') {
                $request->validate([
                    'custom_fields_values.car_brand_other' => 'required|string|max:255',
                ], [
                    'custom_fields_values.car_brand_other.required' => __('wizard.server.car_brand_other_required'),
                ]);
            }
        }

        // ── Real estate (real-estate category only) ──
        if ($category->slug === 'real-estate') {
            $request->validate([
                'custom_fields_values.property_type' => 'required|string',
                'custom_fields_values.listing_type' => 'required|string',
            ], [
                'custom_fields_values.property_type.required' => __('wizard.server.property_type_required'),
                'custom_fields_values.listing_type.required' => __('wizard.server.listing_type_required'),
            ]);
        }

        // ── Dynamic custom_fields_schema (any category that defines one) ──
        if (! empty($category->custom_fields_schema)) {
            $customRules = [];
            $customMessages = [];

            foreach ($category->custom_fields_schema as $field) {
                if (! empty($field['required'])) {
                    $key = "custom_fields_values.{$field['name']}";
                    $customRules[$key] = 'required';
                    // Locale-aware field label: prefer label_en in non-Arabic locales,
                    // falling back to label_ar then the raw field name.
                    $label = app()->getLocale() === 'ar'
                        ? ($field['label_ar'] ?? $field['name'])
                        : ($field['label_en'] ?? $field['label_ar'] ?? $field['name']);
                    $customMessages[$key.'.required'] = __('wizard.server.field_required', ['field' => $label]);
                }
            }

            if (! empty($customRules)) {
                $request->validate($customRules, $customMessages);
            }
        }
    }

    /**
     * عرض صفحة تعديل إعلان موجود (نفس الويزارد، وضع "تعديل").
     *
     * - الملكية مفروضة: 404 لغير المالك (لا نسرّب وجود السجل)، والإعلان المغلق
     *   (soft-deleted) مستبعَد تلقائياً بالـ global scope فيرجع 404 كذلك.
     * - حارس احترازي: يُمنع التعديل لو للإعلان عملية بيع قيد التأكيد (pending).
     * - القسم مقفول، فلا حاجة لاختياره — لكن نمرّر شجرة الأقسام كاملة ليتمكّن
     *   Alpine من اشتقاق القسم النشط + حقوله الديناميكية من category_id.
     */
    public function edit(Listing $listing)
    {
        abort_unless($listing->user_id === Auth::id(), 404);

        if ($this->hasPendingSale($listing)) {
            return redirect()->route('dashboard')
                ->with('error', __('server.listing.edit_blocked_sale_pending'));
        }

        $listing->loadMissing(['category', 'location']);

        // DTO مطابق لشكل formData في Alpine (تحويل DB → formData).
        $editData = [
            'category_id' => $listing->category_id,
            'title' => $listing->title,
            'description' => $listing->description,
            'condition' => $listing->condition,
            'price' => (string) $listing->price,
            'price_type' => $listing->price_type,
            // كائن (لا مصفوفة) ليعمل Object.assign + الوصول بالمفتاح في Alpine.
            'custom_fields' => $listing->custom_fields_values ?: (object) [],
            'car_brand_id' => $listing->car_brand_id ? (string) $listing->car_brand_id : '',
            'car_model_id' => $listing->car_model_id ? (string) $listing->car_model_id : '',
            'phone' => $listing->phone,
            // المحافظة مشتقّة من والد المدينة (الـ DB يخزّن المدينة فقط في location_id).
            'governorate_id' => $listing->location?->parent_id ? (string) $listing->location->parent_id : '',
            'location_id' => $listing->location_id ? (string) $listing->location_id : '',
            'feature_days' => 0, // التمييز مستثنى تماماً من التعديل
        ];

        // جذر القسم (لإظهار القسم الفرعي + اشتقاق الحقول الديناميكية client-side).
        $editRootId = $listing->category
            ? ($listing->category->parent_id ?? $listing->category->id)
            : null;

        // الصور الحالية (كتلة ثابتة أولى) — id + رابط النسخة المائية card.
        $editImages = $listing->getMedia('images')->map(fn ($media) => [
            'id' => $media->id,
            'url' => $media->getUrl('card'),
        ])->values();

        $reference = $this->wizardReferenceData();
        $user = Auth::user();
        $isPhoneVerified = (bool) $user->is_phone_verified;
        $userPoints = (int) $user->points;

        // In edit mode, location is already set from the listing itself (editData).
        // Pre-fill is only relevant for create mode, so pass empty strings here.
        $prefillLocationId = '';
        $prefillGovernorateId = '';

        return view('frontend.listings.create', array_merge($reference, [
            'isPhoneVerified' => $isPhoneVerified,
            'userPoints' => $userPoints,
            'mode' => 'edit',
            'listing' => $listing,
            'editData' => $editData,
            'editImages' => $editImages,
            'editRootId' => $editRootId,
            'prefillLocationId' => $prefillLocationId,
            'prefillGovernorateId' => $prefillGovernorateId,
        ]));
    }

    /**
     * حفظ تعديل إعلان موجود.
     *
     * فروق جوهرية عن store():
     *  - القسم مقفول: مصدره الإعلان نفسه، وأي category_id من الطلب يُتجاهَل.
     *  - لا منح نقاط (+3) — النقاط فقط عند الإنشاء الأول.
     *  - الـ slug يبقى كما هو (لا توليد جديد).
     *  - الحالة تعود pending دائماً (مراجعة أدمن لكل تعديل، بلا استثناء).
     *  - التمييز مستثنى: لا نلمس is_featured / featured_until.
     *  - الصور: حذف من الكتلة الحالية + إلحاق الجديدة (مع تحقق mime/حجم خادمي).
     *  - حارس احترازي ضد SaleConfirmation pending.
     */
    public function update(Request $request, Listing $listing)
    {
        abort_unless($listing->user_id === Auth::id(), 404);

        if ($this->hasPendingSale($listing)) {
            $message = __('server.listing.edit_blocked_sale_pending');

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : redirect()->route('dashboard')->with('error', $message);
        }

        // القسم مقفول: مصدره الإعلان نفسه — لا نثق بأي category_id قادم من الطلب.
        $listing->loadMissing('category');
        $category = $listing->category;
        // الحالة مشروطة بالقسم (المقفول): مطلوبة إلا للعقارات.
        $isRealEstate = $category?->slug === 'real-estate';

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            // max يطابق عمود السعر decimal(12,2) لتفادي خطأ خارج النطاق 22003.
            'price' => 'required|numeric|min:0|max:9999999999.99',
            'condition' => [$isRealEstate ? 'nullable' : 'required', 'string', 'max:50'],
            'price_type' => 'required|string|max:50',
            'phone' => 'required|string|max:20',
            'location_id' => 'nullable|exists:locations,id',
            'car_brand_id' => 'nullable|exists:car_brands,id',
            'car_model_id' => 'nullable|exists:car_models,id',
            // ── أمان الصور: تحقق خادمي من النوع والحجم (لا نعتمد على الـ client) ──
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,webp|max:5120',
            'removed_image_ids' => 'nullable|array',
            'removed_image_ids.*' => 'integer',
        ], [
            'price.max' => __('wizard.server.price_max'),
            'images.max' => __('wizard.server.images_max'),
            'images.*.image' => __('wizard.server.image_invalid'),
            'images.*.mimes' => __('wizard.server.image_mimes'),
            'images.*.max' => __('wizard.server.image_max'),
        ]);

        // التحقق الشرطي بالقسم المقفول (من الإعلان، ليس من الطلب).
        if ($category) {
            $this->validateCategorySpecificFields($request, $category);
        }

        $phone = preg_replace('/\s+/', ' ', trim($validated['phone']));

        DB::transaction(function () use ($request, $validated, $phone, $listing, $category) {
            $listing->title = $validated['title'];
            // الـ slug يبقى كما هو — لا توليد جديد.
            $listing->description = $validated['description'];
            $listing->price = $validated['price'];
            // null للعقارات (لم تُرسَل الحالة)؛ القيمة المُدخَلة لباقي الأقسام.
            $listing->condition = $validated['condition'] ?? null;
            $listing->price_type = $validated['price_type'];
            $listing->phone = $phone;
            $listing->location_id = $validated['location_id'] ?? null;

            // FK السيارات: تُحدَّث فقط لو القسم (المقفول) سيارات.
            if ($category && $category->slug === 'cars') {
                $listing->car_brand_id = $validated['car_brand_id'] ?? null;
                $listing->car_model_id = $validated['car_model_id'] ?? null;
            }

            $listing->custom_fields_values = $request->input('custom_fields_values', []);

            // الحالة تعود pending دائماً — مراجعة أدمن لكل تعديل بلا استثناء.
            $listing->status = Listing::STATUS_PENDING;

            // category_id مقفول، والتمييز (is_featured/featured_until) لا يُلمس.
            $listing->save();

            // حذف الصور الحالية المحدّدة (الكتلة الثابتة الأولى).
            $removedIds = $validated['removed_image_ids'] ?? [];
            if (! empty($removedIds)) {
                $listing->getMedia('images')
                    ->whereIn('id', $removedIds)
                    ->each
                    ->delete();
            }

            // إلحاق الصور الجديدة بعد الحالية (Spatie يرفع order_column تلقائياً).
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $listing->addMedia($image)->toMediaCollection('images');
                }
            }

            // لا منح نقاط في التعديل.
        });

        $message = __('server.listing.updated_success');

        if ($request->expectsJson()) {
            // فلاش للجلسة حتى تظهر الرسالة بعد انتقال المتصفح للوحة التحكم.
            session()->flash('success', $message);

            return response()->json([
                'success' => true,
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', $message);
    }

    /**
     * هل للإعلان عملية بيع قيد التأكيد (pending)؟ حارس احترازي رخيص قبل التعديل.
     */
    private function hasPendingSale(Listing $listing): bool
    {
        return SaleConfirmation::where('listing_id', $listing->id)
            ->where('status', SaleConfirmation::STATUS_PENDING)
            ->exists();
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
                'message' => __('wizard.server.ai_failed'),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'title' => $result['title'],
                'description' => $result['description'],
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
            'plans' => $plans,
            'featureMatrix' => config('pricing.feature_matrix', []),
            'planColumnKeys' => config('pricing.plan_column_keys', []),
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
     *   sort        - latest | oldest | price_asc | price_desc
     */
    public function search(Request $request)
    {
        // تحقق خادمي من كل مدخلات البحث: يمنع حقن فلاتر Meilisearch (lat/lng/radius
        // تُدرَج داخل filter string) ويقصّ الكلمة المفتاحية لطول معقول (ضد ReDoS/DoS).
        $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:1000'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer'],
            'province_id' => ['nullable', 'integer'],
            'sort' => ['nullable', 'string', Rule::in(ListingSort::OPTIONS)],
        ]);

        $sort = ListingSort::fromRequest($request->input('sort'));
        $query = (string) $request->input('q', '');
        // بعد التحقق أعلاه نضمن أنها أرقام بحتة قبل إدراجها في فلتر Meilisearch.
        $lat = $request->filled('lat') ? (float) $request->input('lat') : null;
        $lng = $request->filled('lng') ? (float) $request->input('lng') : null;
        $radius = (float) $request->input('radius', 50);
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $categoryId = $request->input('category_id');
        $provinceId = $request->input('province_id');

        $useGeoSort = $lat && $lng && $sort === ListingSort::DEFAULT;

        $searchBuilder = Listing::search(
            $query,
            function ($meiliSearch, $query, $options) use ($lat, $lng, $radius, $useGeoSort) {
                if ($meiliSearch instanceof Builder) {
                    return;
                }

                $filters = ['status = "'.Listing::STATUS_PUBLISHED.'"'];

                if ($lat && $lng) {
                    $radiusInMeters = $radius * 1000;
                    $filters[] = "_geoRadius({$lat}, {$lng}, {$radiusInMeters})";

                    if ($useGeoSort) {
                        $options['sort'] = ["_geoPoint({$lat}, {$lng}):asc"];
                    }
                }

                $options['filter'] = implode(' AND ', $filters);

                return $meiliSearch->search($query, $options);
            }
        );

        if ($sort === ListingSort::DEFAULT && ! $useGeoSort) {
            $matchingIds = $searchBuilder->keys();

            $listingsQuery = Listing::query()
                ->with(['category', 'location', 'user'])
                ->where('listings.status', Listing::STATUS_PUBLISHED)
                ->whereIn('listings.id', $matchingIds->all());

            if ($matchingIds->isEmpty()) {
                $listingsQuery->whereRaw('0 = 1');
            }

            if ($minPrice !== null && $minPrice !== '') {
                $listingsQuery->where('listings.price', '>=', (float) $minPrice);
            }
            if ($maxPrice !== null && $maxPrice !== '') {
                $listingsQuery->where('listings.price', '<=', (float) $maxPrice);
            }
            if ($categoryId) {
                $listingsQuery->where('listings.category_id', (int) $categoryId);
            }
            if ($provinceId) {
                $listingsQuery->where('listings.province_id', (int) $provinceId);
            }

            $listings = $listingsQuery
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

            ListingSort::apply($listings, $sort, qualify: true);

            $listings = $listings
                ->paginate(12)
                ->withQueryString();
        } else {
            $listings = $searchBuilder
                ->query(function ($q) use ($minPrice, $maxPrice, $categoryId, $provinceId, $sort, $useGeoSort) {
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

                    if (! $useGeoSort) {
                        ListingSort::apply($q, $sort, qualify: true);
                    }
                })
                ->paginate(12)
                ->withQueryString();
        }

        return view('frontend.search-results', compact(
            'listings', 'query', 'lat', 'lng', 'radius',
            'minPrice', 'maxPrice', 'categoryId', 'provinceId', 'sort'
        ));
    }
}
