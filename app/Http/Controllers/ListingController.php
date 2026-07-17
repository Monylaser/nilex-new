<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Offer; // 🟢 استدعاء موديل العروض
use App\Services\ListingLeadTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class ListingController extends Controller
{
    /**
     * عرض صفحة تفاصيل الإعلان.
     */
    /**
     * الإعلان قابل للعرض إما لو كان منشوراً، أو لو المستخدم الحالي هو صاحبه،
     * أو لو كان من فريق الإدارة (super_admin / admin / moderator).
     * أي حالة أخرى (pending / rejected / flagged لغير المخوّلين) => 404.
     */
    private function canViewListing(Listing $listing): bool
    {
        if ($listing->status === Listing::STATUS_PUBLISHED) {
            return true;
        }

        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $listing->user_id === $user->id
            || $user->hasAnyRole(['super_admin', 'admin', 'moderator']);
    }

    public function show(Request $request, Listing $listing, ListingLeadTrackingService $leadTracking): View
    {
        abort_unless($this->canViewListing($listing), 404);

        if ($leadTracking->recordView($listing, auth()->user(), $request->ip())) {
            $listing->increment('views_count');
        }

        $listing->load([
            'carBrand',
            'carModel',
            'province',
            'location',
            'user',
            'category',
            'media',
        ]);

        // إعلانات مشابهة: نفس القسم، باستثناء الإعلان الحالي، المنشور فقط.
        // الترتيب: إعلانات نفس المحافظة (province) أولاً ثم الأحدث.
        // eager loading لـ category/location/user لمنع N+1 في بطاقة الإعلان
        // (نفس نمط CategoryController::show()).
        $similarListings = Listing::query()
            ->with(['category', 'location', 'user', 'media'])
            ->where('category_id', $listing->category_id)
            ->where('id', '!=', $listing->id)
            ->where('status', Listing::STATUS_PUBLISHED)
            ->orderByRaw('CASE WHEN province_id = ? THEN 0 ELSE 1 END', [$listing->province_id])
            ->latest()
            ->limit(6)
            ->get();

        return view('frontend.listings.show', [
            'listing' => $listing,
            'similarListings' => $similarListings,
        ]);
    }

    public function revealPhone(Listing $listing, ListingLeadTrackingService $leadTracking): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (! $this->canViewListing($listing)) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $listing->loadMissing('user');

        $phone = $listing->phone ?? $listing->user?->phone;
        $message = urlencode(__('listing.whatsapp_prefill', ['title' => $listing->title]));
        $whatsappUrl = null;

        if ($phone) {
            $phoneForWhatsapp = '2'.ltrim((string) $phone, '0');
            $whatsappUrl = "https://wa.me/{$phoneForWhatsapp}?text={$message}";
            $leadTracking->recordPhoneClick($listing, Auth::user());
        }

        return response()->json([
            'phone' => $phone,
            'whatsapp_url' => $whatsappUrl,
        ]);
    }

    public function trackWhatsappClick(Listing $listing, ListingLeadTrackingService $leadTracking): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($listing->status !== Listing::STATUS_PUBLISHED) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($leadTracking->recordWhatsappClick($listing, Auth::user())) {
            $listing->increment('whatsapp_clicks');
        }

        return response()->json(['success' => true]);
    }

    /**
     * اختيار اختياري: عرض كل الإعلانات في الصفحة الرئيسية
     */
    public function index()
    {
        $listings = Listing::with(['province', 'location'])
            ->where('status', 'active')
            ->latest()
            ->paginate(12);

        return view('listings.index', compact('listings'));
    }

    // 🟢 دالة استقبال العروض الجديدة (Make an Offer)
    public function makeOffer(Request $request, Listing $listing)
    {
        $rateLimitKey = 'offers|'.Auth::id();

        // 10 عروض/دقيقة لكل مستخدم — يمنع السبام مع السماح بعدة عروض متتالية شرعية.
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'error' => __('server.offer.rate_limit_exceeded', ['seconds' => $seconds]),
            ], 429);
        }

        // 0. لا يُسمح بتقديم عروض إلا على إعلان منشور (دفاع في العمق:
        //    زر العرض لا يظهر أصلاً على الإعلانات غير المنشورة).
        abort_unless($listing->status === Listing::STATUS_PUBLISHED, 404);

        // 1. التحقق من البيانات
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'message' => 'nullable|string|max:500',
        ]);

        // 2. منع المشتري من تقديم عرض على إعلانه الخاص
        if ($listing->user_id === Auth::id()) {
            return response()->json(['error' => __('server.offer.own_listing')], 400);
        }

        // 3. منع التكرار (عشان المشتري ميعملش سبام عروض)
        $existingOffer = Offer::where('listing_id', $listing->id)
            ->where('sender_id', Auth::id())
            ->where('status', 'pending')
            ->exists();

        if ($existingOffer) {
            return response()->json(['error' => __('server.offer.duplicate')], 400);
        }

        // 4. حفظ العرض في الداتابيز
        Offer::create([
            'listing_id' => $listing->id,
            'sender_id' => Auth::id(),
            'receiver_id' => $listing->user_id,
            'amount' => $request->amount,
            'message' => $request->message,
        ]);

        RateLimiter::hit($rateLimitKey, 60);

        return response()->json(['success' => __('server.offer.sent_success')]);
    }
}
