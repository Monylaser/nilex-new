<?php

namespace App\Observers;

use App\Models\PointTransaction;
use Illuminate\Support\Facades\Session;

class PointTransactionObserver
{
    /**
     * يتم تنفيذها قبل حفظ العملية في قاعدة البيانات
     * وظيفته: حساب الرصيد التراكمي أوتوماتيكياً
     */
    public function creating(PointTransaction $transaction): void
    {
        // If current_balance was already set by the caller (e.g. PointService which
        // increments the user row first and then passes the post-transaction balance),
        // trust it. Otherwise fall back to the user's current balance as-is.
        if (! isset($transaction->current_balance)) {
            $transaction->loadMissing('user');
            $transaction->current_balance = (int) ($transaction->user?->points ?? 0);
        }
    }

    /**
     * يتم تنفيذها بعد حفظ العملية بنجاح
     * وظيفته: إرسال إشعار Flash للمتصفح
     *
     * NOTE: We do NOT touch user.points here.  PointService (or legacy direct
     * decrement code) is responsible for updating the balance.
     */
    public function created(PointTransaction $transaction): void
    {
        $message = $transaction->amount > 0
            ? "🏆 مبروك! حصلت على {$transaction->amount} نقطة جديدة."
            : '💸 تم خصم '.abs($transaction->amount).' نقطة من رصيدك.';

        Session::flash('points_added', $message);
    }
}
