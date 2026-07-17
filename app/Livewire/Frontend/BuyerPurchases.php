<?php

namespace App\Livewire\Frontend;

use App\Models\Review;
use App\Models\SaleConfirmation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * شاشة "مشترياتي" (/dashboard/purchases): يرى المشتري عمليات البيع التي سجّلها
 * البائع له ويؤكدها، ثم يقيّم البائع inline. ثلاث حالات لكل صف:
 *   1) pending                  → زر "تأكيد الشراء"  (confirmByBuyer)
 *   2) confirmed بلا تقييم      → نموذج التقييم inline (نجوم 1–5 + تعليق اختياري)
 *   3) confirmed مع تقييم       → حالة شكر للقراءة فقط
 * كل إجراء يُعيد التحقق خادمياً أن الصف يخص المشتري الحالي (منع IDOR).
 */
#[Layout('layouts.app')]
class BuyerPurchases extends Component
{
    use WithPagination;

    // قيم التقييم المؤقتة قبل الإرسال — مفهرسة بمعرّف عملية البيع لدعم عدة صفوف
    public array $ratingValues = [];

    public array $ratingComments = [];

    // المشتري يؤكد عملية الشراء. مسموح فقط لصف يخصّه وحالته pending.
    public function confirmPurchase(int $id): void
    {
        $sale = SaleConfirmation::where('id', $id)
            ->where('buyer_id', Auth::id())
            ->firstOrFail();

        if ($sale->confirmByBuyer()) {
            session()->flash('success', __('server.sale_confirmation.buyer_confirmed'));
        } else {
            session()->flash('error', __('server.sale_confirmation.confirm_failed'));
        }
    }

    // تخزين تقييم النجوم مؤقتاً قبل الإرسال (1–5 فقط)
    public function setRating(int $id, int $value): void
    {
        if ($value < 1 || $value > 5) {
            return;
        }

        $this->ratingValues[$id] = $value;
    }

    // إرسال التقييم: يتحقق من الملكية + اكتمال البيع + عدم وجود تقييم سابق
    public function submitReview(int $id): void
    {
        $rateLimitKey = 'reviews|'.Auth::id();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            session()->flash('error', __('server.review.rate_limit_exceeded', ['seconds' => $seconds]));
            abort(429, __('server.review.rate_limit_exceeded', ['seconds' => $seconds]));
        }

        $sale = SaleConfirmation::where('id', $id)
            ->where('buyer_id', Auth::id())
            ->with('review')
            ->firstOrFail();

        // التقييم مسموح فقط بعد اكتمال البيع، ومرة واحدة فقط
        if (! $sale->isFullyConfirmed() || $sale->review !== null) {
            session()->flash('error', __('server.review.not_allowed'));
            return;
        }

        $rating = (int) ($this->ratingValues[$id] ?? 0);

        if ($rating < 1 || $rating > 5) {
            session()->flash('error', __('server.review.rating_required'));
            return;
        }

        $comment = trim((string) ($this->ratingComments[$id] ?? ''));

        Review::create([
            'sale_confirmation_id' => $sale->id,
            'reviewer_id'          => Auth::id(),
            'reviewee_id'          => $sale->seller_id,
            'listing_id'           => $sale->listing_id,
            'rating'               => $rating,
            'comment'              => $comment !== '' ? $comment : null,
        ]);

        RateLimiter::hit($rateLimitKey, 60);

        unset($this->ratingValues[$id], $this->ratingComments[$id]);

        session()->flash('success', __('server.review.submitted'));
    }

    public function render()
    {
        $purchases = SaleConfirmation::query()
            ->where('buyer_id', Auth::id())
            ->with(['listing', 'seller', 'review'])
            ->latest()
            ->paginate(10);

        return view('livewire.frontend.buyer-purchases', [
            'purchases' => $purchases,
        ]);
    }
}
