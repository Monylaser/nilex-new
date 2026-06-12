<?php

namespace App\Livewire\Frontend;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\Listing;
use App\Models\Offer;
use App\Services\EntitlementService;
use App\Services\SellerListingAnalyticsService;

#[Layout('layouts.app')]
class UserDashboard extends Component
{
    use WithPagination;

    // ✅ قبول العرض (تم إضافة int)
    public function acceptOffer(int $id)
    {
        $offer = Offer::where('receiver_id', Auth::id())->findOrFail($id);
        $offer->update(['status' => 'accepted']);
        session()->flash('success', 'تم قبول العرض بنجاح! ✅');
    }

    // ✅ رفض العرض (تم إضافة int)
    public function rejectOffer(int $id)
    {
        $offer = Offer::where('receiver_id', Auth::id())->findOrFail($id);
        $offer->update(['status' => 'rejected']);
        session()->flash('error', 'تم رفض العرض. ❌');
    }

    // دالة لحذف الإعلان (تم إضافة int)
    public function deleteListing(int $id)
    {
        $listing = Listing::where('user_id', Auth::id())->findOrFail($id);
        
        // مسح الصور المرتبطة بالإعلان قبل مسحه (Spatie Media Library)
        $listing->clearMediaCollection('images'); 
        
        $listing->delete();

        session()->flash('success', 'تم حذف الإعلان بنجاح 🗑️');
    }

    // دالة تمييز الإعلان باستخدام النقاط (تم إضافة int)
    public function featureListing(int $id)
    {
        $listing = Listing::where('user_id', Auth::id())->findOrFail($id);

        if ($listing->is_featured) {
            session()->flash('error', 'هذا الإعلان مميز بالفعل!');
            return;
        }

        try {
            $listing->featureWithPoints(3);

            session()->flash(
                'success',
                'تم خصم النقاط وتمييز الإعلان بنجاح! 🚀'
            );
        } catch (\Exception $e) {
            session()->flash(
                'error',
                $e->getMessage()
            );
        }
    }

    public function render()
    {
        $user = Auth::user();
        $entitlements = app(EntitlementService::class);
        $analyticsService = app(SellerListingAnalyticsService::class);
        
        // جلب الإعلانات الخاصة بالمستخدم الحالي فقط
        $listings = Listing::where('user_id', $user->id)
            ->with('category')
            ->latest()
            ->paginate(10);

        // 🟢 جلب العروض المستلمة (التي تنتظر الرد)
        $incomingOffers = Offer::where('receiver_id', $user->id)
            ->with(['sender', 'listing'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $isLegacy = $entitlements->isLegacyGrandfathered($user);

        $access = [
            'analytics'       => $entitlements->hasFeature($user, EntitlementService::FEATURE_ANALYTICS_ACCESS) || $isLegacy,
            'charts'          => $entitlements->hasFeature($user, EntitlementService::FEATURE_ANALYTICS_CHARTS) || $isLegacy,
            'phone_clicks'    => $entitlements->hasFeature($user, EntitlementService::FEATURE_PHONE_CLICKS_ACCESS) || $isLegacy,
            'whatsapp_clicks' => $entitlements->hasFeature($user, EntitlementService::FEATURE_WHATSAPP_CLICKS_ACCESS) || $isLegacy,
            'event_views'     => $entitlements->hasFeature($user, EntitlementService::FEATURE_EVENT_VIEWS_ACCESS) || $isLegacy,
        ];

        // حساب إحصائيات لوحة التحكم
        $eventStats = $analyticsService->getDashboardStats($user);

        $stats = [
            'total'    => Listing::where('user_id', $user->id)->count(),
            'active'   => Listing::where('user_id', $user->id)->where('status', Listing::STATUS_PUBLISHED)->count(),
            'pending'  => Listing::where('user_id', $user->id)->where('status', Listing::STATUS_PENDING)->count(),
            'rejected' => Listing::where('user_id', $user->id)->where('status', Listing::STATUS_REJECTED)->count(),
            'views'    => Listing::where('user_id', $user->id)->sum('views_count'),
            'clicks'   => Listing::where('user_id', $user->id)->sum('whatsapp_clicks'),
            'views_events'           => $eventStats['views_events'],
            'phone_clicks'           => $eventStats['phone_clicks'],
            'whatsapp_clicks_events' => $eventStats['whatsapp_clicks_events'],
            'total_phone_reveals'    => $access['phone_clicks'] ? $eventStats['phone_clicks'] : null,
            'total_whatsapp_clicks'  => $access['whatsapp_clicks'] ? $eventStats['whatsapp_clicks_events'] : null,
            'conversion_rate'        => $access['analytics'] ? $analyticsService->getConversionRate($user) : null,
        ];

        $chartData = [
            'views_by_day'          => $access['event_views'] ? $analyticsService->getViewsByDay($user) : ['labels' => [], 'values' => []],
            'whatsapp_by_listing'   => $access['whatsapp_clicks'] ? $analyticsService->getWhatsappClicksByListing($user) : ['labels' => [], 'values' => []],
            'category_performance'  => $access['analytics'] ? $analyticsService->getCategoryPerformance($user) : ['labels' => [], 'values' => []],
        ];

        return view('livewire.frontend.user-dashboard', compact(
            'listings',
            'stats',
            'user',
            'incomingOffers',
            'access',
            'chartData',
        ));
    }
}