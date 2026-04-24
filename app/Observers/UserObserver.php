<?php

namespace App\Observers;

use App\Models\User;
use App\Services\PointService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function created(User $user): void
    {
        // 1. القفل الأمني الأول: منع التكرار لنفس الحساب
        if ($user->points > 0) {
            return;
        }

        // 2. 🛡️ الاختبار الرابع: منع الغش بتعدد الحسابات من نفس الـ IP
        // نتحقق: هل فيه يوزر "تاني" أخد هدية من نفس عنوان الـ IP ده؟
        $alreadyGifted = User::where('id', '!=', $user->id)
            ->where('ip_address', request()->ip()) // تأكد إن حقل الـ IP موجود في قاعدة البيانات
            ->where('points', '>', 0)
            ->exists();

        if ($alreadyGifted) {
            Log::warning("محاولة تسجيل مكررة للحصول على نقاط من IP: " . request()->ip());
            return;
        }

        try {
            // 3. لو عدى من الاختبارين، ندي الهدية
            app(PointService::class)->credit(
                user: $user,
                amount: 50,
                description: 'هدية ترحيبية بمناسبة الانضمام للمنصة'
            );

        } catch (\Exception $e) {
            Log::error("فشل إضافة نقاط الترحيب للمستخدم {$user->id}: " . $e->getMessage());
        }
    }
}
