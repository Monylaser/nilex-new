<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * تبديل حالة الإعلان في مفضلة المستخدم (إضافة / إزالة) عبر AJAX.
     * يقلّد نمط ListingController::revealPhone — يتحقق من تسجيل الدخول
     * داخلياً ويرجّع 401 للزائر (الواجهة توجّهه لصفحة /login).
     */
    public function toggle(Listing $listing): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $userId = Auth::id();

        $existing = Favorite::where('user_id', $userId)
            ->where('listing_id', $listing->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['favorited' => false]);
        }

        Favorite::create([
            'user_id'    => $userId,
            'listing_id' => $listing->id,
        ]);

        return response()->json(['favorited' => true]);
    }

    /**
     * صفحة "مفضلتي" داخل لوحة التحكم — تعرض الإعلانات المحفوظة فقط.
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $listings = $user->favoriteListings()
            ->with(['category', 'location', 'user'])
            ->latest('favorites.created_at')
            ->paginate(12);

        return view('dashboard.favorites', [
            'listings' => $listings,
        ]);
    }
}
