<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Offer; // 🟢 استدعاء موديل العروض
use App\Services\ListingLeadTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth; // 🟢 استدعاء Auth عشان نعرف مين اليوزر

class ListingController extends Controller
{
    /**
     * عرض صفحة تفاصيل الإعلان.
     */
    public function show(Request $request, Listing $listing, ListingLeadTrackingService $leadTracking): View
    {
        if ($leadTracking->recordView($listing, auth()->user(), $request->ip())) {
            $listing->increment('views_count');
        }

        $listing->load([
            'carBrand',
            'carModel',
            'province',
            'location',
            'user',
        ]);

        return view('frontend.listings.show', [
            'listing' => $listing,
        ]);
    }

    public function revealPhone(Listing $listing, ListingLeadTrackingService $leadTracking): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $listing->loadMissing('user');

        $phone = $listing->phone ?? $listing->user->phone;
        $phoneForWhatsapp = '2'.ltrim($phone, '0');
        $message = urlencode("مرحباً، بخصوص إعلانك: {$listing->title} على منصة Nilex. هل ما زال متاحاً؟");

        if ($phone) {
            $leadTracking->recordPhoneClick($listing, Auth::user());
        }

        return response()->json([
            'phone'        => $phone,
            'whatsapp_url' => "https://wa.me/{$phoneForWhatsapp}?text={$message}",
        ]);
    }

    public function trackWhatsappClick(Listing $listing, ListingLeadTrackingService $leadTracking): JsonResponse
    {
        if ($leadTracking->recordWhatsappClick($listing, auth()->user())) {
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
            'listing_id'  => $listing->id,
            'sender_id'   => Auth::id(),
            'receiver_id' => $listing->user_id,
            'amount'      => $request->amount,
            'message'     => $request->message,
        ]);

        return response()->json(['success' => __('server.offer.sent_success')]);
    }
}