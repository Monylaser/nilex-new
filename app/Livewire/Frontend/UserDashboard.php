<?php

namespace App\Livewire\Frontend;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\SaleConfirmation;
use App\Models\SellerLead;
use App\Notifications\SaleConfirmationRequested;
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

    // ── Step 2 (sold_platform): اختيار المشتري ────────────────────────────
    // الخطوة داخل نفس النافذة (1 = اختيار النوع، 2 = اختيار المشتري). لا نفتح
    // نافذة ثانية — نبدّل الجسم فقط عبر morph. `selectedBuyerId` مصدر الحقيقة
    // لمن اختاره البائع، ويُعاد التحقق منه خادمياً ضد SellerLead قبل الإنشاء.
    public int $closingStep = 1;

    public ?int $selectedBuyerId = null;

    // أنواع الإغلاق المسموحة (مصدر الحقيقة للتحقق + ترتيب العرض)
    public const CLOSING_TYPES = ['sold_platform', 'sold_external', 'canceled'];

    // مصادر التواصل المؤهَّلة لاعتبار المتواصل "مشترياً" محتملاً
    public const BUYER_LEAD_SOURCES = [SellerLead::SOURCE_PHONE_REVEAL, SellerLead::SOURCE_OFFER];

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
        $this->closingStep         = 1;
        $this->selectedBuyerId     = null;
        $this->closingModalOpen    = true;
    }

    // إغلاق النافذة وتصفير الحالة (يمنع تنفيذ إجراء على إعلان خاطئ لاحقاً)
    public function closeClosingModal()
    {
        $this->reset([
            'closingModalOpen',
            'closingListingId',
            'closingListingTitle',
            'closingType',
            'closingStep',
            'selectedBuyerId',
        ]);
    }

    // الرجوع من الخطوة 2 إلى الخطوة 1 (يُلغي اختيار المشتري)
    public function backToStep1()
    {
        $this->closingStep     = 1;
        $this->selectedBuyerId = null;
    }

    // تبديل اختيار المشتري (toggle): إعادة الضغط على نفس المشتري يُلغي اختياره
    public function selectBuyer(int $buyerId)
    {
        $this->selectedBuyerId = ($this->selectedBuyerId === $buyerId) ? null : $buyerId;
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
                // الانتقال للخطوة 2 (اختيار المشتري) داخل نفس النافذة.
                // لا حذف هنا — الحذف يتم فقط بعد تأكيد المشتري في confirmSaleToBuyer().
                $this->selectedBuyerId = null;
                $this->closingStep     = 2;
                break;

            default:
                // نوع غير صالح / لم يُختر — لا إجراء (الزر معطّل في الواجهة أصلاً)
                break;
        }
    }

    // قائمة المشترين المؤهَّلين لهذا الإعلان (كشفوا الهاتف أو بعتوا عرضاً).
    // مقيَّدة بصرامة بـ closingListingId، مشترٍ واحد لكل صف، مع تحميل اسم المشتري.
    public function buyerLeads()
    {
        if ($this->closingListingId === null) {
            return collect();
        }

        return SellerLead::query()
            ->where('listing_id', $this->closingListingId)
            ->whereIn('source_type', self::BUYER_LEAD_SOURCES)
            ->whereNotNull('buyer_id')
            ->with('buyer')
            ->latest()
            ->get()
            ->unique('buyer_id')
            ->values();
    }

    // تأكيد البيع لمشترٍ مختار: إنشاء SaleConfirmation (pending) + إغلاق الإعلان
    // داخل transaction واحدة. يعيد التحقق من الملكية ومن أهلية المشتري (منع IDOR).
    public function confirmSaleToBuyer()
    {
        if ($this->closingListingId === null || $this->selectedBuyerId === null) {
            return;
        }

        // 1) إعادة التحقق من الملكية خادمياً — لا نثق بأي قيمة من الواجهة
        $listing = Listing::where('user_id', Auth::id())->findOrFail($this->closingListingId);

        // 2) البائع لا يمكن أن يكون هو المشتري
        if ($this->selectedBuyerId === Auth::id()) {
            session()->flash('error', __('server.sale_confirmation.invalid_buyer'));
            return;
        }

        // 3) فحص IDOR: المشتري المختار يجب أن يكون فعلاً Lead مؤهَّل لهذا الإعلان
        $isEligibleBuyer = SellerLead::query()
            ->where('listing_id', $listing->id)
            ->where('buyer_id', $this->selectedBuyerId)
            ->whereIn('source_type', self::BUYER_LEAD_SOURCES)
            ->whereNotNull('buyer_id')
            ->exists();

        if (! $isEligibleBuyer) {
            session()->flash('error', __('server.sale_confirmation.invalid_buyer'));
            return;
        }

        // 4) حماية "مشتري واحد لكل إعلان": يُمنع لو يوجد بيع مكتمل (confirmed)
        if (! SaleConfirmation::canInitiateForListing($listing->id)) {
            session()->flash('error', __('server.sale_confirmation.already_confirmed'));
            $this->closeClosingModal();
            return;
        }

        // 5) الإنشاء + الإغلاق الناعم داخل transaction واحدة (ذرّية)
        DB::transaction(function () use ($listing) {
            SaleConfirmation::updateOrCreate(
                [
                    'listing_id' => $listing->id,
                    'buyer_id'   => $this->selectedBuyerId,
                ],
                [
                    'seller_id'           => Auth::id(),
                    'status'              => SaleConfirmation::STATUS_PENDING,
                    'seller_confirmed_at' => now(),
                    'buyer_confirmed_at'  => null,
                    'canceled_at'         => null,
                    'canceled_by'         => null,
                ],
            );

            // إغلاق الإعلان (soft delete) كجزء من نفس العملية
            $listing->delete();
        });

        // إشعار المشتري بطلب تأكيد الشراء (قابل للنقر إلى /dashboard/purchases)
        $saleConfirmation = SaleConfirmation::where('listing_id', $listing->id)
            ->where('buyer_id', $this->selectedBuyerId)
            ->first();

        if ($saleConfirmation && $saleConfirmation->buyer) {
            $saleConfirmation->buyer->notify(new SaleConfirmationRequested($saleConfirmation));
        }

        session()->flash('success', __('server.sale_confirmation.created'));
        $this->closeClosingModal();
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

        // قائمة المشترين تُجلب فقط عند فتح الخطوة 2 (sold_platform)
        $buyerLeads = ($this->closingModalOpen && $this->closingStep === 2)
            ? $this->buyerLeads()
            : collect();

        // عدد عمليات الشراء التي تنتظر تأكيد المستخدم الحالي (للـ badge على "مشترياتي")
        $pendingPurchasesCount = SaleConfirmation::where('buyer_id', $user->id)
            ->where('status', SaleConfirmation::STATUS_PENDING)
            ->count();

        return view('livewire.frontend.user-dashboard', compact(
            'listings',
            'stats',
            'user',
            'incomingOffers',
            'access',
            'chartData',
            'buyerLeads',
            'pendingPurchasesCount',
        ));
    }
}