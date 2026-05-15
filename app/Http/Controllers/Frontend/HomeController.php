<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Category;
use App\Services\PointService; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $featuredListings = Listing::active()->latest()->take(3)->get();
        $latestListings = Listing::active()->latest()->paginate(12);

        return view('frontend.home', compact('categories', 'featuredListings', 'latestListings'));
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
        // 1. التحقق من البيانات الأساسية اللي جاية من الفورم
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            // 'category_id' => 'required', 
            // 'price'       => 'required|numeric',
        ]);

        // 2. إنشاء الإعلان
        $listing = new Listing();
        $listing->title = $validated['title'];
        $listing->description = $validated['description'];
        $listing->price = 0; 
        $listing->category_id = 1; 
        $listing->user_id = Auth::id();
        $listing->status = Listing::STATUS_PENDING; 
        $listing->save();

        // 3. رفع الصور باستخدام Spatie Media Library
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $listing->addMedia($image)->toMediaCollection('images');
            }
        }

        // 4. 🎁 منح اليوزر 10 نقاط مكافأة النشر
        $pointService = new PointService();
        $pointService->credit(Auth::user(), 10, 'مكافأة نشر إعلان جديد: ' . $listing->title, $listing);

        return redirect()->route('dashboard')->with('success', 'تم حفظ الإعلان بنجاح، وكسبت 10 نقاط! 🚀');
    }

    /**
     * 🟢 البحث المتقدم (النصي + الجغرافي)
     */
    public function search(Request $request)
    {
        // استقبال معطيات البحث من الفورم
        $query = $request->input('q', ''); // الكلمة المفتاحية
        $lat = $request->input('lat'); // خط العرض
        $lng = $request->input('lng'); // خط الطول
        $radius = $request->input('radius', 50); // المسافة بالكيلومتر (الافتراضي 50 كيلو)

        // بناء استعلام Meilisearch
        $search = Listing::search($query, function ($meiliSearch, $query, $options) use ($lat, $lng, $radius) {
            
            // 1. فلترة أساسية: لازم الإعلان يكون منشور
            $filters = ['status = "' . Listing::STATUS_PUBLISHED . '"'];

            // 2. فلترة المسافة (لو المشتري سمح للبراوزر يحدد مكانه)
            if ($lat && $lng) {
                // Meilisearch بيحسب المسافة بالمتر، فهنضرب الكيلو في 1000
                $radiusInMeters = $radius * 1000;
                $filters[] = "_geoRadius({$lat}, {$lng}, {$radiusInMeters})";
                
                // ترتيب النتائج من الأقرب للأبعد
                $options['sort'] = ["_geoPoint({$lat}, {$lng}):asc"];
            }

            // دمج الفلاتر وإرسالها للمحرك
            $options['filter'] = implode(' AND ', $filters);

            return $meiliSearch->search($query, $options);
        });

        // جلب النتائج مع الـ Pagination
        $listings = $search->paginate(12)->withQueryString();

        // إرسال المتغيرات لملف العرض
        return view('search-results', compact('listings', 'query', 'lat', 'lng', 'radius'));
    }
}