<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Offer; // 🟢 استدعاء موديل العروض
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth; // 🟢 استدعاء Auth عشان نعرف مين اليوزر

class ListingController extends Controller
{
    /**
     * عرض صفحة تفاصيل الإعلان.
     *
     * @param Listing $listing
     * @return View
     */
    public function show(Listing $listing): \Illuminate\View\View
    {
        // 📈 زيادة عداد المشاهدات
        $listing->increment('views_count');

        $listing->load([
            'carBrand', 
            'carModel', 
            'province', 
            'location'
        ]);

        return view('listings.show', [
            'listing' => $listing
        ]);
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
            return response()->json(['error' => 'لا يمكنك تقديم عرض على إعلانك الخاص!'], 400);
        }

        // 3. منع التكرار (عشان المشتري ميعملش سبام عروض)
        $existingOffer = Offer::where('listing_id', $listing->id)
            ->where('sender_id', Auth::id())
            ->where('status', 'pending')
            ->exists();

        if ($existingOffer) {
            return response()->json(['error' => 'لديك عرض قيد الانتظار بالفعل لهذا الإعلان.'], 400);
        }

        // 4. حفظ العرض في الداتابيز
        Offer::create([
            'listing_id'  => $listing->id,
            'sender_id'   => Auth::id(),
            'receiver_id' => $listing->user_id,
            'amount'      => $request->amount,
            'message'     => $request->message,
        ]);

        return response()->json(['success' => 'تم إرسال عرضك للبائع بنجاح! 🚀']);
    }
}