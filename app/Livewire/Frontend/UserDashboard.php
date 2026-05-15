<?php

namespace App\Livewire\Frontend;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\Listing;
use App\Models\Offer;

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

        if ($listing->featureWithPoints(3)) {
            session()->flash('success', 'تم خصم النقاط وتمييز الإعلان بنجاح! 🚀');
        } else {
            session()->flash('error', 'عذراً، ليس لديك نقاط كافية.');
        }
    }

    public function render()
    {
        $user = Auth::user();
        
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

        // حساب إحصائيات لوحة التحكم
        $stats = [
            'total'    => Listing::where('user_id', $user->id)->count(),
            'active'   => Listing::where('user_id', $user->id)->where('status', Listing::STATUS_PUBLISHED)->count(),
            'pending'  => Listing::where('user_id', $user->id)->where('status', Listing::STATUS_PENDING)->count(),
            'rejected' => Listing::where('user_id', $user->id)->where('status', Listing::STATUS_REJECTED)->count(),
            'views'    => Listing::where('user_id', $user->id)->sum('views_count'),
            'clicks'   => Listing::where('user_id', $user->id)->sum('whatsapp_clicks'),
        ];

        return view('livewire.frontend.user-dashboard', compact('listings', 'stats', 'user', 'incomingOffers'));
    }
}