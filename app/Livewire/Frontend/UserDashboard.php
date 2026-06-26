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

    // ── Listing-closing modal state (Step 1: type selection) ──────────────
    // Foundation for the upcoming sale-confirmation flow. Before a seller
    // "closes" (soft-deletes) a listing they must pick HOW it was closed.
    // `closingListingId` is the single source of truth for which listing is
    // being closed and (re)verified server-side on every action.
    public bool $closingModalOpen = false;

    public ?int $closingListingId = null;

    public ?string $closingListingTitle = null;

    public ?string $closingType = null;

    // أنواع الإغلاق المسموحة (مصدر الحقيقة للتحقق + ترتيب العرض)
    public const CLOSING_TYPES = ['sold_platform', 'sold_external', 'canceled'];

    // ✅ قبول العرض (تم إضافة int)
    public function acceptOffer(int $id)
    {
        $offer = Offer::where('receiver_id', Auth::id())->findOrFail($id);
        $offer->update(['status' => 'accepted', 'responded_at' => $offer->responded_at ?? now()]);
        session()->flash('success', __('server.dashboard.offer_accepted'));
    }

    // ✅ رفض العرض (تم إضافة int)
    public function rejectOffer(int $id)
    {
        $offer = Offer::where('receiver_id', Auth::id())->findOrFail($id);
        $offer->update(['status' => 'rejected', 'responded_at' => $offer->responded_at ?? now()]);
        session()->flash('error', __('server.dashboard.offer_rejected'));
    }

    // دالة لحذف الإعلان (تم إضافة int)
    public function deleteListing(int $id)
    {
        $listing = Listing::where('user_id', Auth::id())->findOrFail($id);

        // حذف ناعم (soft delete): يبقى الصف في الجدول مع deleted_at.
        // الصور تبقى محفوظة عمداً (لا نستدعي clearMediaCollection) حتى
        // يظل الإعلان المحذوف قابلاً للعرض في سجلّات لاحقة (تأكيد البيع/التقييمات).
        $listing->delete();

        session()->flash('success', __('server.dashboard.listing_deleted'));
    }

    // فتح نافذة "نوع الإغلاق" — تتحقق من الملكية وتُثبّت هوية الإعلان المحدد
    public function openClosingModal(int $id)
    {
        $listing = Listing::where('user_id', Auth::id())->findOrFail($id);

        $this->closingListingId    = $listing->id;
        $this->closingListingTitle = $listing->title;
        $this->closingType         = null;
        $this->closingModalOpen    = true;
    }

    // إغلاق النافذة وتصفير الحالة (يمنع تنفيذ إجراء على إعلان خاطئ لاحقاً)
    public function closeClosingModal()
    {
        $this->reset(['closingModalOpen', 'closingListingId', 'closingListingTitle', 'closingType']);
    }

    // تبديل نوع الإغلاق (toggle): إن كان النوع نفسه مختاراً يُلغى، وإلا يُضبط
    public function toggleClosingType(string $type)
    {
        if (! in_array($type, self::CLOSING_TYPES, true)) {
            return;
        }

        $this->closingType = ($this->closingType === $type) ? null : $type;
    }

    // تأكيد الإغلاق حسب النوع المختار
    public function confirmClosing()
    {
        if ($this->closingListingId === null) {
            return;
        }

        // إعادة التحقق من الملكية خادمياً — لا نثق بأي قيمة من الواجهة
        $listing = Listing::where('user_id', Auth::id())->findOrFail($this->closingListingId);

        switch ($this->closingType) {
            case 'sold_external':
            case 'canceled':
                // حذف ناعم عادي (الإعلان يبقى في النظام، الاسترجاع للأدمن فقط)
                $listing->delete();
                session()->flash('success', __('server.dashboard.listing_deleted'));
                $this->closeClosingModal();
                break;

            case 'sold_platform':
                // TODO: Step 2 — buyer list filtered strictly by $this->closingListingId
                // (SellerLead::where('listing_id', $this->closingListingId)
                //   ->whereIn('source_type', ['phone_reveal','offer'])->whereNotNull('buyer_id')).
                // Intentionally no delete in this step.
                break;

            default:
                // نوع غير صالح / لم يُختر — لا إجراء (الزر معطّل في الواجهة أصلاً)
                break;
        }
    }

    // دالة تمييز الإعلان باستخدام النقاط (تم إضافة int)
    public function featureListing(int $id)
    {
        $listing = Listing::where('user_id', Auth::id())->findOrFail($id);

        if ($listing->is_featured) {
            session()->flash('error', __('server.dashboard.already_featured'));
            return;
        }

        try {
            $listing->featureWithPoints(3);

            session()->flash(
                'success',
                __('server.dashboard.featured_success')
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