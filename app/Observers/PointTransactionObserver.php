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
        // جلب آخر رصيد حالي للمستخدم من جدول الـ users
        $currentPoints = $transaction->user->points ?? 0;

        // حساب الرصيد الجديد بعد العملية وتخزينه في الحقل المطلوب
        $transaction->current_balance = $currentPoints + $transaction->amount;
    }

    /**
     * يتم تنفيذها بعد حفظ العملية بنجاح
     * وظيفته: تحديث جدول المستخدمين وإرسال إشعار للمتصفح
     */
    public function created(PointTransaction $transaction): void
    {
        // 1. تحديث إجمالي نقاط المستخدم في جدول users
        $user = $transaction->user;
        $user->increment('points', $transaction->amount);

        // 2. تجهيز رسالة الإشعار بناءً على نوع العملية (إضافة أو خصم)
        $message = $transaction->amount > 0
            ? "🏆 مبروك! حصلت على {$transaction->amount} نقطة جديدة."
            : "💸 تم خصم " . abs($transaction->amount) . " نقطة من رصيدك.";

        // 3. إرسال الإشعار لمرة واحدة (Flash Session)
        Session::flash('points_added', $message);
    }
}
